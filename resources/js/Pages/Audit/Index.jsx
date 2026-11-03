import { router } from '@inertiajs/react';
import { Kartu, Tombol, tanggalWaktu, Paginasi, SelKosong } from '../../umum/ui';

export default function Index({ data, filter, daftar_aksi, daftar_entitas, daftar_pengguna }) {
    const ubah = (kunci, nilai) => {
        router.get('/audit', { ...filter, [kunci]: nilai || undefined }, { preserveState: true });
    };

    const reset = () => router.get('/audit');

    const input = 'border rounded px-3 py-2 text-sm';

    return (
        <div className="space-y-4">
            <h1 className="text-2xl font-bold">Log Audit</h1>

            <Kartu>
                <div className="flex gap-3 mb-4 flex-wrap items-end">
                    <div>
                        <label className="block text-xs font-medium mb-1">Aksi</label>
                        <select value={filter.aksi || ''} onChange={(e) => ubah('aksi', e.target.value)} className={input}>
                            <option value="">Semua</option>
                            {daftar_aksi.map((a) => <option key={a} value={a}>{a}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs font-medium mb-1">Entitas</label>
                        <select value={filter.entitas || ''} onChange={(e) => ubah('entitas', e.target.value)} className={input}>
                            <option value="">Semua</option>
                            {daftar_entitas.map((a) => <option key={a} value={a}>{a}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs font-medium mb-1">Pengguna</label>
                        <select value={filter.pengguna_id || ''} onChange={(e) => ubah('pengguna_id', e.target.value)} className={input}>
                            <option value="">Semua</option>
                            {daftar_pengguna.map((p) => <option key={p.id} value={p.id}>{p.nama}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs font-medium mb-1">Dari</label>
                        <input type="date" value={filter.dari || ''} onChange={(e) => ubah('dari', e.target.value)} className={input} />
                    </div>
                    <div>
                        <label className="block text-xs font-medium mb-1">Sampai</label>
                        <input type="date" value={filter.sampai || ''} onChange={(e) => ubah('sampai', e.target.value)} className={input} />
                    </div>
                    <Tombol varian="sekunder" onClick={reset}>Reset</Tombol>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="py-2 pr-4 font-medium text-slate-600">Waktu</th>
                                <th className="py-2 pr-4 font-medium text-slate-600">Pengguna</th>
                                <th className="py-2 pr-4 font-medium text-slate-600">Aksi</th>
                                <th className="py-2 pr-4 font-medium text-slate-600">Entitas</th>
                                <th className="py-2 pr-4 font-medium text-slate-600">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.map((a) => (
                                <tr key={a.id} className="border-t hover:bg-slate-50">
                                    <td className="py-2 pr-4 whitespace-nowrap text-xs">{tanggalWaktu(a.waktu)}</td>
                                    <td className="py-2 pr-4">{a.pengguna?.nama || '-'}</td>
                                    <td className="py-2 pr-4">{a.aksi}</td>
                                    <td className="py-2 pr-4">{a.entitas}{a.entitas_id ? ` #${a.entitas_id}` : ''}</td>
                                    <td className="py-2 pr-4 text-slate-600">{a.keterangan || '-'}</td>
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
