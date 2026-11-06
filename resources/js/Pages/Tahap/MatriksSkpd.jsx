import { useState, Fragment } from 'react';
import { Link, useForm, router } from '@inertiajs/react';
import { Kartu, Tombol, Lencana, rupiah, lencanaStatus, SelKosong, PilihCari } from '../../umum/ui';
import { DialogKonfirmasi } from '@/components/ui/dialog-konfirmasi';

function ToggleAcress({ tipe, onUbah }) {
    const aktif = 'bg-blue-600 text-white';
    const mati = 'bg-white text-slate-600';
    return (
        <div className="flex rounded border overflow-hidden shrink-0">
            <button type="button" onClick={() => onUbah('nominal')}
                className={`px-2 py-2 text-sm ${tipe === 'nominal' ? aktif : mati}`}>Rp</button>
            <button type="button" onClick={() => onUbah('persen')}
                className={`px-2 py-2 text-sm ${tipe === 'persen' ? aktif : mati}`}>%</button>
        </div>
    );
}

function FormulirBaris({ baris, jenis_kebutuhan, jenis_tambahan, onSelesai }) {
    const awal = {
        nilai: baris.nilai,
        volume: baris.volume,
        acress_type: baris.acress_type ?? 'nominal',
        acress: baris.acress ?? 0,
        kebutuhan: Object.fromEntries(jenis_kebutuhan.map((j) => [j.id, baris.kebutuhan?.[j.id] ?? 0])),
        tambahan: Object.fromEntries(jenis_tambahan.map((j) => [j.id, baris.tambahan?.[j.id] ?? 0])),
    };
    const { data, setData, put, processing, errors } = useForm(awal);

    const kirim = (e) => {
        e.preventDefault();
        put(`/anggaran/${baris.id}`, {
            data: {
                nilai: data.nilai,
                volume: data.volume,
                acress_type: data.acress_type,
                acress: Number(data.acress) || 0,
                kebutuhan: Object.entries(data.kebutuhan).map(([id, nominal]) => ({
                    jenis_kebutuhan_id: Number(id),
                    nominal: Number(nominal) || 0,
                })),
                tambahan: Object.entries(data.tambahan).map(([id, nominal]) => ({
                    jenis_kebutuhan_tambahan_id: Number(id),
                    nominal: Number(nominal) || 0,
                })),
            },
            onSuccess: () => onSelesai && onSelesai(),
        });
    };

    const input = 'w-full border rounded px-2 py-1 text-sm';

    return (
        <form onSubmit={kirim} className="bg-blue-50/50 p-4 rounded border border-blue-200 space-y-4">
            <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label className="block text-xs font-medium mb-1">Nilai / bulan</label>
                    <input type="number" min="0" step="0.01" value={data.nilai} onChange={(e) => setData('nilai', e.target.value)} className={input} />
                    {errors.nilai && <div className="text-red-600 text-xs mt-1">{errors.nilai}</div>}
                </div>
                <div>
                    <label className="block text-xs font-medium mb-1">Volume (pengali)</label>
                    <input type="number" min="0" step="0.01" value={data.volume} onChange={(e) => setData('volume', e.target.value)} className={input} />
                    {errors.volume && <div className="text-red-600 text-xs mt-1">{errors.volume}</div>}
                    <p className="text-xs text-slate-500 mt-1">Volume 0 → total baris = 0.</p>
                </div>
                <div>
                    <label className="block text-xs font-medium mb-1">Acress</label>
                    <div className="flex gap-1">
                        <ToggleAcress tipe={data.acress_type} onUbah={(t) => setData('acress_type', t)} />
                        <input type="number" min="0" step="0.01" value={data.acress} onChange={(e) => setData('acress', e.target.value)} className={input} />
                    </div>
                    {errors.acress && <div className="text-red-600 text-xs mt-1">{errors.acress}</div>}
                </div>
            </div>

            {jenis_kebutuhan.length > 0 && (
                <div>
                    <div className="text-xs font-medium mb-2">Jenis kebutuhan (nominal / bulan, ikut dikali volume)</div>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                        {jenis_kebutuhan.map((j) => (
                            <div key={j.id}>
                                <label className="block text-xs mb-1">{j.nama}</label>
                                <input
                                    type="number" min="0" step="0.01"
                                    value={data.kebutuhan[j.id] ?? 0}
                                    onChange={(e) => setData('kebutuhan', { ...data.kebutuhan, [j.id]: e.target.value })}
                                    className={input}
                                />
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {jenis_tambahan.length > 0 && (
                <div>
                    <div className="text-xs font-medium mb-2">Kebutuhan tambahan (nominal langsung, tidak dikali volume)</div>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                        {jenis_tambahan.map((j) => (
                            <div key={j.id}>
                                <label className="block text-xs mb-1">{j.nama}</label>
                                <input
                                    type="number" min="0" step="0.01"
                                    value={data.tambahan[j.id] ?? 0}
                                    onChange={(e) => setData('tambahan', { ...data.tambahan, [j.id]: e.target.value })}
                                    className={input}
                                />
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <div className="flex gap-2">
                <Tombol disabled={processing}>Simpan</Tombol>
                <Tombol varian="sekunder" type="button" onClick={onSelesai}>Batal</Tombol>
            </div>
        </form>
    );
}

function FormulirTambah({ tahapId, skpd, rekening, volumeBawaan, onSelesai }) {
    const { data, setData, post, processing, errors } = useForm({
        skpd_id: skpd.id, rekening_id: '', nilai: 0, volume: volumeBawaan,
        acress_type: 'persen', acress: 2.5,
    });

    const kirim = (e) => {
        e.preventDefault();
        post(`/tahap/${tahapId}/anggaran`, { onSuccess: () => onSelesai && onSelesai() });
    };

    const input = 'w-full border rounded px-3 py-2 text-sm';

    return (
        <div>
            <p className="text-sm mb-3"><span className="font-medium">SKPD:</span> {skpd.kode} - {skpd.nama}</p>
            <form onSubmit={kirim}>
                <div className="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    <div>
                        <label className="block text-xs font-medium mb-1">Rekening</label>
                        <PilihCari
                            opsi={rekening.map((r) => ({ id: r.id, label: `${r.kode} - ${r.nama}` }))}
                            nilai={data.rekening_id}
                            onPilih={(id) => setData('rekening_id', id)}
                            placeholder="Ketik kode/nama rekening…"
                        />
                        {errors.rekening_id && <div className="text-red-600 text-xs mt-1">{errors.rekening_id}</div>}
                    </div>
                    <div>
                        <label className="block text-xs font-medium mb-1">Nilai / bulan</label>
                        <input type="number" min="0" step="0.01" value={data.nilai} onChange={(e) => setData('nilai', e.target.value)} className={input} />
                    </div>
                    <div>
                        <label className="block text-xs font-medium mb-1">Volume</label>
                        <input type="number" min="0" step="0.01" value={data.volume} onChange={(e) => setData('volume', e.target.value)} className={input} />
                    </div>
                    <div>
                        <label className="block text-xs font-medium mb-1">Acress</label>
                        <div className="flex gap-1">
                            <ToggleAcress tipe={data.acress_type} onUbah={(t) => setData('acress_type', t)} />
                            <input type="number" min="0" step="0.01" value={data.acress} onChange={(e) => setData('acress', e.target.value)} className={input} />
                        </div>
                        {errors.acress && <div className="text-red-600 text-xs mt-1">{errors.acress}</div>}
                    </div>
                </div>
                <div className="flex gap-2 mt-3">
                    <Tombol disabled={processing}>Tambah</Tombol>
                    <Tombol varian="sekunder" type="button" onClick={onSelesai}>Batal</Tombol>
                </div>
            </form>
        </div>
    );
}

export default function MatriksSkpd({ tahap, skpd, baris, total_skpd, rekening, jenis_kebutuhan, jenis_tambahan, bisa_ubah, volume_bawaan, volume_keterangan }) {
    const [tambah, setTambah] = useState(false);
    const [ubahId, setUbahId] = useState(null);
    const [cari, setCari] = useState('');

    const tampil = baris.filter((b) => {
        if (!cari) return true;
        const q = cari.toLowerCase();
        return b.rekening_kode.toLowerCase().includes(q) || b.rekening_nama.toLowerCase().includes(q);
    });

    const [hapusId, setHapusId] = useState(null);

    const hapus = () => {
        if (hapusId === null) return;
        router.delete(`/anggaran/${hapusId}`);
        setHapusId(null);
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-3 flex-wrap">
                <Link href={`/tahap/${tahap.id}/anggaran`} className="text-sm text-blue-600 hover:underline">← Matriks Anggaran</Link>
                <h1 className="text-2xl font-bold">Input Matriks — {skpd.kode} {skpd.nama}</h1>
                {lencanaStatus(tahap.status)}
            </div>
            <p className="text-sm text-slate-500">Tahap {tahap.tahun} - {tahap.nama}</p>

            <div className="flex items-center justify-between flex-wrap gap-3">
                <input
                    type="text" placeholder="Cari rekening…" value={cari}
                    onChange={(e) => setCari(e.target.value)}
                    className="border rounded px-3 py-2 text-sm w-64"
                />
                <div className="text-sm">
                    Total SKPD: <span className="font-bold text-lg tabular-nums">{rupiah(total_skpd)}</span>
                </div>
                {bisa_ubah && !tambah && <Tombol onClick={() => setTambah(true)}>Tambah Baris</Tombol>}
            </div>

            {bisa_ubah && tambah && (
                <Kartu judul="Tambah Baris Anggaran">
                    <FormulirTambah tahapId={tahap.id} skpd={skpd} rekening={rekening} volumeBawaan={volume_bawaan} onSelesai={() => setTambah(false)} />
                </Kartu>
            )}

            <Kartu>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="py-2 pr-3 font-medium text-slate-600">Rekening</th>
                                <th className="py-2 pr-3 font-medium text-slate-600 text-right">Nilai/bln</th>
                                <th className="py-2 pr-3 font-medium text-slate-600 text-right">Volume</th>
                                <th className="py-2 pr-3 font-medium text-slate-600 text-right">Kebutuhan/bln</th>
                                <th className="py-2 pr-3 font-medium text-slate-600 text-right">Total Kebutuhan</th>
                                <th className="py-2 pr-3 font-medium text-slate-600 text-right">Total Tambahan</th>
                                <th className="py-2 pr-3 font-medium text-slate-600 text-right">Subtotal</th>
                                <th className="py-2 pr-3 font-medium text-slate-600 text-right">Acress</th>
                                <th className="py-2 pr-3 font-medium text-slate-600 text-right">Total Pagu</th>
                                {bisa_ubah && <th className="py-2 font-medium text-slate-600">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody>
                            {tampil.map((b) => (
                                <Fragment key={b.id}>
                                    <tr className={`border-t hover:bg-slate-50 ${b.volume_nol ? 'bg-yellow-50' : ''}`}>
                                        <td className="py-2 pr-3">
                                            <div className="font-medium tabular-nums">{b.rekening_kode}</div>
                                            <div className="text-xs text-slate-500">{b.rekening_nama}</div>
                                        </td>
                                        <td className="py-2 pr-3 text-right tabular-nums">{rupiah(b.nilai)}</td>
                                        <td className="py-2 pr-3 text-right tabular-nums">
                                            {b.volume}
                                            {b.volume_nol && <Lencana tipe="kuning">vol 0</Lencana>}
                                        </td>
                                        <td className="py-2 pr-3 text-right tabular-nums">{rupiah(b.kebutuhan_bulan)}</td>
                                        <td className="py-2 pr-3 text-right tabular-nums">{rupiah(b.total_kebutuhan)}</td>
                                        <td className="py-2 pr-3 text-right tabular-nums">{rupiah(b.total_kebutuhan_tambahan)}</td>
                                        <td className="py-2 pr-3 text-right tabular-nums">{rupiah(b.subtotal)}</td>
                                        <td className="py-2 pr-3 text-right tabular-nums">{b.acress_type === 'persen' ? `${b.acress}%` : rupiah(b.acress)}</td>
                                        <td className="py-2 pr-3 text-right font-semibold tabular-nums">{rupiah(b.total_pagu)}</td>
                                        {bisa_ubah && (
                                            <td className="py-2 whitespace-nowrap">
                                                <button onClick={() => setUbahId(ubahId === b.id ? null : b.id)} className="text-blue-600 hover:underline text-sm mr-2">
                                                    Ubah
                                                </button>
                                                <button onClick={() => setHapusId(b.id)} className="text-red-600 hover:underline text-sm">
                                                    Hapus
                                                </button>
                                            </td>
                                        )}
                                    </tr>
                                    {ubahId === b.id && bisa_ubah && (
                                        <tr key={`f-${b.id}`}>
                                            <td colSpan={10} className="py-2">
                                                <FormulirBaris
                                                    baris={b}
                                                    jenis_kebutuhan={jenis_kebutuhan}
                                                    jenis_tambahan={jenis_tambahan}
                                                    onSelesai={() => setUbahId(null)}
                                                />
                                            </td>
                                        </tr>
                                    )}
                                </Fragment>
                            ))}
                            {tampil.length === 0 && <SelKosong colspan={10} teks="Belum ada baris anggaran untuk SKPD ini. Tambahkan baris di atas." />}
                        </tbody>
                    </table>
                </div>
            </Kartu>

            <p className="text-xs text-slate-500">
                Kebutuhan/bulan = nilai + Σ jenis kebutuhan · Total kebutuhan = kebutuhan/bulan × volume ·
                Subtotal = total kebutuhan + Σ kebutuhan tambahan ·
                Total pagu = subtotal + acress (Rp langsung, atau % dari subtotal). Baris bervolume 0 ditandai kuning.
            </p>
            <DialogKonfirmasi
                terbuka={hapusId !== null}
                onTerbukaBerubah={(v) => { if (!v) setHapusId(null); }}
                judul="Hapus baris anggaran?"
                deskripsi="Hapus baris anggaran ini beserta rinciannya? Tindakan ini tidak bisa dibatalkan."
                labelAksi="Hapus"
                varianAksi="destructive"
                onKonfirmasi={hapus}
            />
        </div>
    );
}
