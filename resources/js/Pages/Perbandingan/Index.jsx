import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Kartu, Tombol, rupiah, tanggal, SelKosong } from '../../umum/ui';

export default function Index({ daftar_tahap, dipilih, hasil }) {
    const [pilih, setPilih] = useState(dipilih || []);

    const toggle = (id) => {
        setPilih((p) => (p.includes(id) ? p.filter((x) => x !== id) : [...p, id]));
    };

    const bandingkan = () => {
        router.get('/perbandingan', { tahap: pilih });
    };

    return (
        <div className="space-y-4">
            <h1 className="text-2xl font-bold">Perbandingan Antar Tahap</h1>

            <Kartu judul="Pilih 2 tahap atau lebih">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 mb-4">
                    {daftar_tahap.map((t) => (
                        <label key={t.id} className="flex items-start gap-2 text-sm border rounded p-2 hover:bg-slate-50">
                            <input
                                type="checkbox"
                                checked={pilih.includes(t.id)}
                                onChange={() => toggle(t.id)}
                                className="h-4 w-4 mt-0.5"
                            />
                            <span>
                                <span className="font-medium">{t.tahun} - {t.nama}</span>
                                <span className="text-slate-500"> ({t.status})</span>
                                <br />
                                <span className="text-xs text-slate-500">
                                    {tanggal(t.tgl_mulai)} s/d {tanggal(t.tgl_selesai)}
                                </span>
                            </span>
                        </label>
                    ))}
                </div>
                <Tombol disabled={pilih.length < 2} onClick={bandingkan}>
                    Bandingkan ({pilih.length})
                </Tombol>
            </Kartu>

            {hasil && (
                <>
                    <Kartu judul="Ringkasan per Tahap">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left">
                                        <th className="py-2 pr-4 font-medium text-slate-600">Tahap</th>
                                        <th className="py-2 pr-4 font-medium text-slate-600 text-right">Total Anggaran</th>
                                        <th className="py-2 pr-4 font-medium text-slate-600 text-right">Total Realisasi</th>
                                        <th className="py-2 pr-4 font-medium text-slate-600 text-right">Selisih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {hasil.tahap.map((t) => (
                                        <tr key={t.id} className="border-t">
                                            <td className="py-2 pr-4 font-medium">{t.label}</td>
                                            <td className="py-2 pr-4 text-right">{rupiah(t.total_anggaran)}</td>
                                            <td className="py-2 pr-4 text-right">{rupiah(t.total_realisasi)}</td>
                                            <td className={`py-2 pr-4 text-right ${t.selisih < 0 ? 'text-red-600' : ''}`}>
                                                {rupiah(t.selisih)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Kartu>

                    <Kartu judul="Matriks per SKPD (total pagu)">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left">
                                        <th className="py-2 pr-4 font-medium text-slate-600">SKPD</th>
                                        {hasil.tahap.map((t) => (
                                            <th key={t.id} className="py-2 pr-4 font-medium text-slate-600 text-right">{t.label}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {hasil.matriks_skpd.map((r, i) => (
                                        <tr key={i} className="border-t hover:bg-slate-50">
                                            <td className="py-2 pr-4">
                                                <span className="font-medium">{r.kode}</span>{' '}
                                                <span className="text-slate-500 text-xs">{r.nama}</span>
                                            </td>
                                            {hasil.tahap.map((t) => (
                                                <td key={t.id} className="py-2 pr-4 text-right">
                                                    {r.nilai[t.id] ? rupiah(r.nilai[t.id]) : <span className="text-slate-300">-</span>}
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                    {hasil.matriks_skpd.length === 0 && <SelKosong colspan={hasil.tahap.length + 1} />}
                                </tbody>
                            </table>
                        </div>
                    </Kartu>
                </>
            )}
        </div>
    );
}
