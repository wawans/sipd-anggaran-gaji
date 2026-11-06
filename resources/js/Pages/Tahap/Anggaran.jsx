import { useState, Fragment } from 'react';
import { Link } from '@inertiajs/react';
import { Kartu, rupiah, lencanaStatus, Lencana, SelKosong } from '../../umum/ui';

export default function Anggaran({ tahap, ringkasan_skpd, total_tahap, bisa_ubah }) {
    const [bukaId, setBukaId] = useState(null);
    const [cari, setCari] = useState('');

    const tampil = ringkasan_skpd.filter((s) => {
        if (!cari) return true;
        const q = cari.toLowerCase();
        return s.kode.toLowerCase().includes(q) || s.nama.toLowerCase().includes(q);
    });

    const toggle = (id) => setBukaId(bukaId === id ? null : id);
    const jmlKolom = bisa_ubah ? 4 : 3;

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-3 flex-wrap">
                <Link href={`/tahap/${tahap.id}`} className="text-sm text-blue-600 hover:underline">← {tahap.nama}</Link>
                <h1 className="text-2xl font-bold">Matriks Anggaran — {tahap.tahun} {tahap.nama}</h1>
                {lencanaStatus(tahap.status)}
            </div>

            <div className="flex items-center justify-between flex-wrap gap-3">
                <input
                    type="text" placeholder="Cari SKPD…" value={cari}
                    onChange={(e) => setCari(e.target.value)}
                    className="border rounded px-3 py-2 text-sm w-64"
                />
                <div className="text-sm">
                    Total tahap: <span className="font-bold text-lg tabular-nums">{rupiah(total_tahap)}</span>
                </div>
            </div>

            <Kartu>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="py-2 pr-3 font-medium text-slate-600">Kode</th>
                                <th className="py-2 pr-3 font-medium text-slate-600">Nama SKPD</th>
                                <th className="py-2 pr-3 font-medium text-slate-600 text-right">Anggaran</th>
                                {bisa_ubah && <th className="py-2 font-medium text-slate-600">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody>
                            {tampil.map((s) => (
                                <Fragment key={s.id}>
                                    <tr className="border-t hover:bg-slate-50">
                                        <td className="py-2 pr-3 font-medium">{s.kode}</td>
                                        <td className="py-2 pr-3">
                                            <button onClick={() => toggle(s.id)} className="text-blue-600 hover:underline text-left font-medium">
                                                {s.nama}
                                            </button>
                                            <span className="text-xs text-slate-400 ml-1">{bukaId === s.id ? '▾' : '▸'}</span>
                                            {s.jml_baris > 0 && (
                                                <span className="text-xs text-slate-500 ml-2">({s.jml_baris} baris)</span>
                                            )}
                                        </td>
                                        <td className="py-2 pr-3 text-right font-semibold tabular-nums">{rupiah(s.total_pagu)}</td>
                                        {bisa_ubah && (
                                            <td className="py-2 whitespace-nowrap">
                                                <Link
                                                    href={`/tahap/${tahap.id}/skpd/${s.id}/matriks`}
                                                    className="inline-block px-3 py-1 rounded text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white"
                                                >
                                                    Input Matriks
                                                </Link>
                                            </td>
                                        )}
                                    </tr>
                                    {bukaId === s.id && (
                                        <tr key={`r-${s.id}`}>
                                            <td colSpan={jmlKolom} className="py-2 px-4 bg-slate-50">
                                                {s.rincian.length > 0 ? (
                                                    <table className="w-full text-sm">
                                                        <thead>
                                                            <tr className="border-b text-left">
                                                                <th className="py-1 pr-3 font-medium text-slate-500">Kode</th>
                                                                <th className="py-1 pr-3 font-medium text-slate-500">Rekening</th>
                                                                <th className="py-1 pr-3 font-medium text-slate-500 text-right">Nilai/bln</th>
                                                                <th className="py-1 pr-3 font-medium text-slate-500 text-right">Volume</th>
                                                                <th className="py-1 pr-3 font-medium text-slate-500 text-right">Total Pagu</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            {s.rincian.map((r) => (
                                                                <tr key={r.id} className={`border-t ${r.volume_nol ? 'bg-yellow-50' : ''}`}>
                                                                    <td className="py-1 pr-3 tabular-nums">{r.rekening_kode}</td>
                                                                    <td className="py-1 pr-3">{r.rekening_nama}</td>
                                                                    <td className="py-1 pr-3 text-right tabular-nums">{rupiah(r.nilai)}</td>
                                                                    <td className="py-1 pr-3 text-right tabular-nums">
                                                                        {r.volume}
                                                                        {r.volume_nol && <Lencana tipe="kuning">vol 0</Lencana>}
                                                                    </td>
                                                                    <td className="py-1 pr-3 text-right font-medium tabular-nums">{rupiah(r.total_pagu)}</td>
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    </table>
                                                ) : (
                                                    <p className="text-sm text-slate-500 py-2">
                                                        Belum ada baris anggaran untuk SKPD ini di tahap ini.
                                                    </p>
                                                )}
                                            </td>
                                        </tr>
                                    )}
                                </Fragment>
                            ))}
                            {tampil.length === 0 && <SelKosong colspan={jmlKolom} teks="Tidak ada SKPD yang cocok dengan pencarian." />}
                        </tbody>
                    </table>
                </div>
            </Kartu>

            <p className="text-xs text-slate-500">
                Klik nama SKPD untuk melihat rincian rekening yang sudah diinput.
                Anggaran = total pagu (Σ kebutuhan/bulan × volume + Σ kebutuhan tambahan) per SKPD di tahap ini.
            </p>
        </div>
    );
}
