import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Kartu, Tombol, Lencana, rupiah } from '../../umum/ui';

const badgeStatus = (status) => {
    if (status === 'baru') return <Lencana tipe="hijau">Baru</Lencana>;
    if (status === 'ubah') return <Lencana tipe="kuning">Ubah</Lencana>;
    return <Lencana tipe="merah">Lewati</Lencana>;
};

const tampilAngka = (v) => (v === null || v === undefined ? <span className="text-slate-300">—</span> : v);

export default function ImporPratampil({ tahap, token, berkas, header, jml_baris, medan, pemetaan, hasil, ringkas }) {
    const [peta, setPeta] = useState(() => {
        const awal = {};
        medan.forEach((m) => {
            awal[m.kunci] = pemetaan[m.kunci] ?? '';
        });
        return awal;
    });

    const input = 'w-full border rounded px-3 py-2 text-sm';
    const urlPratampil = `/tahap/${tahap.id}/impor/pratampil`;
    const urlSimpan = `/tahap/${tahap.id}/impor/simpan`;

    const setKolom = (kunci, nilai) => setPeta((p) => ({ ...p, [kunci]: nilai }));

    const validasiUlang = () => {
        const muatan = { token, pemetaan: {} };
        Object.entries(peta).forEach(([k, v]) => {
            muatan.pemetaan[k] = v === '' ? null : parseInt(v, 10);
        });
        router.post(urlPratampil, muatan);
    };

    const simpan = () => {
        if (peta.kode_skpd === '' || peta.kode_rekening === '') {
            alert('Pilih kolom Kode SKPD dan Kode Rekening terlebih dahulu.');
            return;
        }
        if (!confirm(`Simpan impor matriks tahap ${tahap.nama}? ${ringkas.baru} baris baru, ${ringkas.ubah} diperbarui, ${ringkas.lewati} dilewati.`)) {
            return;
        }
        const muatan = { token, pemetaan: {} };
        Object.entries(peta).forEach(([k, v]) => {
            muatan.pemetaan[k] = v === '' ? null : parseInt(v, 10);
        });
        router.post(urlSimpan, muatan);
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-3">
                <Link href={`/tahap/${tahap.id}`} className="text-sm text-blue-600 hover:underline">← {tahap.nama}</Link>
                <h1 className="text-2xl font-bold">Pratampil Impor Matriks — {tahap.tahun} {tahap.nama}</h1>
            </div>

            <Kartu judul="Berkas">
                <dl className="text-sm space-y-2">
                    <div className="flex"><dt className="w-40 text-slate-500">Nama berkas</dt><dd>{berkas}</dd></div>
                    <div className="flex"><dt className="w-40 text-slate-500">Jumlah baris data</dt><dd className="font-semibold">{jml_baris}</dd></div>
                    <div className="flex"><dt className="w-40 text-slate-500">Hasil validasi</dt>
                        <dd>
                            <span className="text-green-700 font-medium">{ringkas.baru} baru</span>{', '}
                            <span className="text-yellow-700 font-medium">{ringkas.ubah} ubah</span>{', '}
                            <span className="text-red-700 font-medium">{ringkas.lewati} lewati</span>
                        </dd>
                    </div>
                </dl>
            </Kartu>

            <Kartu judul="Pemetaan kolom">
                <p className="text-xs text-slate-500 mb-3">
                    Pemetaan terisi otomatis dari judul kolom. Ubah bila tidak cocok, lalu klik Validasi Ulang.
                    Kolom Kode SKPD dan Kode Rekening wajib dipetakan.
                </p>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {medan.map((m) => (
                        <div key={m.kunci}>
                            <label className="block text-sm font-medium mb-1">
                                {m.label} {m.wajib && <span className="text-red-600">*</span>}
                            </label>
                            <select value={peta[m.kunci]} onChange={(e) => setKolom(m.kunci, e.target.value)} className={input}>
                                <option value="">— Tidak dipakai —</option>
                                {header.map((h, i) => (
                                    <option key={i} value={i}>Kolom {i + 1}: {h || '(kosong)'}</option>
                                ))}
                            </select>
                        </div>
                    ))}
                </div>
                <div className="mt-4">
                    <Tombol varian="sekunder" onClick={validasiUlang}>Validasi Ulang</Tombol>
                </div>
            </Kartu>

            <Kartu judul={`Hasil validasi per baris (${hasil.length})`}>
                <div className="overflow-x-auto max-h-[480px] overflow-y-auto">
                    <table className="w-full text-sm">
                        <thead className="sticky top-0 bg-white">
                            <tr className="border-b text-left">
                                <th className="py-2 pr-3 font-medium text-slate-600">Baris</th>
                                <th className="py-2 pr-3 font-medium text-slate-600">Kode SKPD</th>
                                <th className="py-2 pr-3 font-medium text-slate-600">Kode Rekening</th>
                                <th className="py-2 pr-3 font-medium text-slate-600">Status</th>
                                <th className="py-2 pr-3 font-medium text-slate-600 text-right">Nilai/Bulan</th>
                                <th className="py-2 pr-3 font-medium text-slate-600 text-right">Volume</th>
                                <th className="py-2 pr-3 font-medium text-slate-600 text-right">Acress</th>
                                <th className="py-2 font-medium text-slate-600">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            {hasil.map((h, i) => (
                                <tr key={i} className={`border-t ${h.status === 'lewati' ? 'bg-red-50' : ''}`}>
                                    <td className="py-1 pr-3">{h.no}</td>
                                    <td className="py-1 pr-3">{h.kode_skpd || <span className="text-slate-300">—</span>}</td>
                                    <td className="py-1 pr-3">{h.kode_rekening || <span className="text-slate-300">—</span>}</td>
                                    <td className="py-1 pr-3">{badgeStatus(h.status)}</td>
                                    <td className="py-1 pr-3 text-right">{h.nilai === null ? <span className="text-slate-300">—</span> : rupiah(h.nilai)}</td>
                                    <td className="py-1 pr-3 text-right">{tampilAngka(h.volume)}</td>
                                    <td className="py-1 pr-3 text-right">
                                        {h.nilai_acress === null && h.tipe_acress === null
                                            ? <span className="text-slate-300">—</span>
                                            : `${h.tipe_acress === 'persen' ? '%' : 'Rp'} ${h.nilai_acress ?? 0}`}
                                    </td>
                                    <td className="py-1 text-xs text-slate-600">{h.pesan.join(' ')}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Kartu>

            <div className="flex gap-2">
                <Tombol onClick={simpan}>Simpan Impor</Tombol>
                <Link href={`/tahap/${tahap.id}`}><Tombol varian="sekunder">Batal</Tombol></Link>
            </div>

            <p className="text-xs text-slate-500">
                Baris "Ubah": hanya sel yang terisi yang diperbarui; sel kosong tidak mengubah data lama.
                Baris "Lewati": kode tidak dikenal atau data tidak valid — tidak disimpan.
            </p>
        </div>
    );
}
