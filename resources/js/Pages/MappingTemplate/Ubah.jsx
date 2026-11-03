import { Link, useForm, router } from '@inertiajs/react';
import { Kartu, Tombol, SelKosong } from '../../umum/ui';

export default function Ubah({ template, rekening }) {
    const form = useForm({ nama: template.nama, kolom_skpd: template.kolom_skpd });
    const formKolom = useForm({ kolom_excel: '', rekening_id: '' });

    const kirim = (e) => {
        e.preventDefault();
        form.put(`/mapping-template/${template.id}`);
    };

    const tambahKolom = (e) => {
        e.preventDefault();
        formKolom.post(`/mapping-template/${template.id}/kolom`, { onSuccess: () => formKolom.reset() });
    };

    const hapusKolom = (id) => {
        if (confirm('Hapus pemetaan kolom ini?')) router.delete(`/mapping-template/kolom/${id}`);
    };

    const input = 'w-full border rounded px-3 py-2 text-sm';

    return (
        <div className="space-y-4 max-w-3xl">
            <div className="flex items-center gap-3">
                <Link href="/mapping-template" className="text-sm text-blue-600 hover:underline">← Template</Link>
                <h1 className="text-2xl font-bold">Ubah Template</h1>
            </div>

            <Kartu>
                <form onSubmit={kirim} className="space-y-3">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label className="block text-sm font-medium mb-1">Nama template</label>
                            <input type="text" value={form.data.nama} onChange={(e) => form.setData('nama', e.target.value)} className={input} />
                            {form.errors.nama && <div className="text-red-600 text-xs mt-1">{form.errors.nama}</div>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Kolom penanda SKPD</label>
                            <input type="text" value={form.data.kolom_skpd} onChange={(e) => form.setData('kolom_skpd', e.target.value)} className={input} />
                            {form.errors.kolom_skpd && <div className="text-red-600 text-xs mt-1">{form.errors.kolom_skpd}</div>}
                        </div>
                    </div>
                    <Tombol disabled={form.processing}>Simpan</Tombol>
                </form>
            </Kartu>

            <Kartu judul="Pemetaan Kolom Excel → Rekening">
                <div className="overflow-x-auto mb-4">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="py-2 pr-4 font-medium text-slate-600">Kolom Excel</th>
                                <th className="py-2 pr-4 font-medium text-slate-600">Rekening</th>
                                <th className="py-2 font-medium text-slate-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {template.kolom.map((k) => (
                                <tr key={k.id} className="border-t">
                                    <td className="py-2 pr-4">{k.kolom_excel}</td>
                                    <td className="py-2 pr-4">{k.rekening.kode} - {k.rekening.nama}</td>
                                    <td className="py-2">
                                        <button onClick={() => hapusKolom(k.id)} className="text-red-600 hover:underline text-sm">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            {template.kolom.length === 0 && <SelKosong colspan={3} teks="Belum ada pemetaan kolom." />}
                        </tbody>
                    </table>
                </div>

                <form onSubmit={tambahKolom} className="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <div>
                        <label className="block text-xs font-medium mb-1">Nama kolom di Excel</label>
                        <input
                            type="text" value={formKolom.data.kolom_excel}
                            onChange={(e) => formKolom.setData('kolom_excel', e.target.value)}
                            className={input} placeholder="mis. Gaji Pokok"
                        />
                        {formKolom.errors.kolom_excel && <div className="text-red-600 text-xs mt-1">{formKolom.errors.kolom_excel}</div>}
                    </div>
                    <div>
                        <label className="block text-xs font-medium mb-1">Rekening</label>
                        <select value={formKolom.data.rekening_id} onChange={(e) => formKolom.setData('rekening_id', e.target.value)} className={input}>
                            <option value="">— pilih —</option>
                            {rekening.map((r) => <option key={r.id} value={r.id}>{r.kode} - {r.nama}</option>)}
                        </select>
                        {formKolom.errors.rekening_id && <div className="text-red-600 text-xs mt-1">{formKolom.errors.rekening_id}</div>}
                    </div>
                    <div>
                        <Tombol disabled={formKolom.processing}>Tambah</Tombol>
                    </div>
                </form>
            </Kartu>
        </div>
    );
}
