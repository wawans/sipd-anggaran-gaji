import { Link, router, usePage } from '@inertiajs/react';
import { Kartu, Tombol, rupiah, tanggal, Paginasi, SelKosong } from '../../umum/ui';

export default function Index({ data, daftar_periode, daftar_skpd, filter }) {
    const { auth } = usePage().props;
    const bisaImpor = (auth.hak_akses || []).includes('realisasi.import');

    const ubahFilter = (kunci, nilai) => {
        router.get('/realisasi', { ...filter, [kunci]: nilai || undefined }, { preserveState: true });
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Realisasi</h1>
                {bisaImpor && (
                    <Link href="/realisasi/impor">
                        <Tombol>Impor dari Excel</Tombol>
                    </Link>
                )}
            </div>

            <Kartu>
                <div className="flex gap-3 mb-4 flex-wrap">
                    <div>
                        <label className="block text-xs font-medium mb-1">Periode</label>
                        <select
                            value={filter.periode || ''}
                            onChange={(e) => ubahFilter('periode', e.target.value)}
                            className="border rounded px-3 py-2 text-sm"
                        >
                            <option value="">Semua</option>
                            {daftar_periode.map((p) => <option key={p} value={p}>{p}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs font-medium mb-1">SKPD</label>
                        <select
                            value={filter.skpd_id || ''}
                            onChange={(e) => ubahFilter('skpd_id', e.target.value)}
                            className="border rounded px-3 py-2 text-sm"
                        >
                            <option value="">Semua</option>
                            {daftar_skpd.map((s) => <option key={s.id} value={s.id}>{s.kode} - {s.nama}</option>)}
                        </select>
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="py-2 pr-4 font-medium text-slate-600">Periode</th>
                                <th className="py-2 pr-4 font-medium text-slate-600">SKPD</th>
                                <th className="py-2 pr-4 font-medium text-slate-600">Rekening</th>
                                <th className="py-2 pr-4 font-medium text-slate-600 text-right">Jumlah</th>
                                <th className="py-2 pr-4 font-medium text-slate-600">Diimpor</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.map((r) => (
                                <tr key={r.id} className="border-t hover:bg-slate-50">
                                    <td className="py-2 pr-4">{r.periode}</td>
                                    <td className="py-2 pr-4">{r.skpd.kode} - {r.skpd.nama}</td>
                                    <td className="py-2 pr-4">{r.rekening.kode} - {r.rekening.nama}</td>
                                    <td className="py-2 pr-4 text-right tabular-nums">{rupiah(r.jumlah)}</td>
                                    <td className="py-2 pr-4 text-xs text-slate-500">
                                        {r.pengimpor?.nama || '-'} · {tanggal(r.diimpor_pada)}
                                    </td>
                                </tr>
                            ))}
                            {data.data.length === 0 && <SelKosong colspan={5} />}
                        </tbody>
                    </table>
                </div>
                <Paginasi data={data} />
            </Kartu>
        </div>
    );
}
