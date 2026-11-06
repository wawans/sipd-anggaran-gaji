import { useState } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { Kartu, Tombol, Paginasi, SelKosong } from '../../umum/ui';

export default function Index({ data }) {
    const { auth } = usePage().props;
    const bisaKelola = (auth.hak_akses || []).includes('mapping.manage');
    const [tambah, setTambah] = useState(false);
    const form = useForm({ nama: '', kolom_skpd: '' });

    const kirim = (e) => {
        e.preventDefault();
        form.post('/mapping-template', { onSuccess: () => { setTambah(false); form.reset(); } });
    };

    const input = 'w-full border rounded px-3 py-2 text-sm';

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Template Mapping</h1>
                {bisaKelola && !tambah && <Tombol onClick={() => setTambah(true)}>Buat Template</Tombol>}
            </div>

            {tambah && (
                <Kartu judul="Buat Template">
                    <form onSubmit={kirim} className="space-y-3 max-w-md">
                        <div>
                            <label className="block text-sm font-medium mb-1">Nama template</label>
                            <input type="text" value={form.data.nama} onChange={(e) => form.setData('nama', e.target.value)} className={input} placeholder="mis. Format BKD" />
                            {form.errors.nama && <div className="text-red-600 text-xs mt-1">{form.errors.nama}</div>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Nama kolom penanda SKPD di Excel</label>
                            <input type="text" value={form.data.kolom_skpd} onChange={(e) => form.setData('kolom_skpd', e.target.value)} className={input} placeholder="mis. SKPD" />
                            {form.errors.kolom_skpd && <div className="text-red-600 text-xs mt-1">{form.errors.kolom_skpd}</div>}
                        </div>
                        <div className="flex gap-2">
                            <Tombol disabled={form.processing}>Simpan</Tombol>
                            <Tombol varian="sekunder" type="button" onClick={() => setTambah(false)}>Batal</Tombol>
                        </div>
                    </form>
                </Kartu>
            )}

            <Kartu>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="py-2 pr-4 font-medium text-slate-600">Nama</th>
                                <th className="py-2 pr-4 font-medium text-slate-600">Kolom SKPD</th>
                                <th className="py-2 pr-4 font-medium text-slate-600 text-right">Jml Kolom</th>
                                {bisaKelola && <th className="py-2 font-medium text-slate-600">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.map((t) => (
                                <tr key={t.id} className="border-t hover:bg-slate-50">
                                    <td className="py-2 pr-4 font-medium">{t.nama}</td>
                                    <td className="py-2 pr-4">{t.kolom_skpd}</td>
                                    <td className="py-2 pr-4 text-right tabular-nums">{t.kolom_count}</td>
                                    {bisaKelola && (
                                        <td className="py-2">
                                            <Link href={`/mapping-template/${t.id}`} className="text-blue-600 hover:underline text-sm">
                                                Kelola Kolom
                                            </Link>
                                        </td>
                                    )}
                                </tr>
                            ))}
                            {data.data.length === 0 && <SelKosong colspan={4} />}
                        </tbody>
                    </table>
                </div>
                <Paginasi data={data} />
            </Kartu>
        </div>
    );
}
