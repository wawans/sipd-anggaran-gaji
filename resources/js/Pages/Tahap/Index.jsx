import { useState } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { Kartu, Tombol, lencanaStatus, rupiah, tanggal, Paginasi, SelKosong } from '../../umum/ui';

function FormulirTahap({ awal, aksi, metode, onSelesai }) {
    const { data, setData, post, put, processing, errors } = useForm(awal);

    const kirim = (e) => {
        e.preventDefault();
        const opsi = { onSuccess: () => onSelesai && onSelesai() };
        if (metode === 'put') put(aksi, opsi);
        else post(aksi, opsi);
    };

    const input = 'w-full border rounded px-3 py-2 text-sm';

    return (
        <form onSubmit={kirim} className="space-y-3">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label className="block text-sm font-medium mb-1">Tahun <span className="text-red-600">*</span></label>
                    <input type="number" value={data.tahun} onChange={(e) => setData('tahun', e.target.value)} className={input} />
                    {errors.tahun && <div className="text-red-600 text-xs mt-1">{errors.tahun}</div>}
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">Nama tahap <span className="text-red-600">*</span></label>
                    <input type="text" value={data.nama} onChange={(e) => setData('nama', e.target.value)} className={input} placeholder="mis. Triwulan 1" />
                    {errors.nama && <div className="text-red-600 text-xs mt-1">{errors.nama}</div>}
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">Tanggal mulai <span className="text-red-600">*</span></label>
                    <input type="date" value={data.tgl_mulai} onChange={(e) => setData('tgl_mulai', e.target.value)} className={input} />
                    {errors.tgl_mulai && <div className="text-red-600 text-xs mt-1">{errors.tgl_mulai}</div>}
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">Tanggal selesai <span className="text-red-600">*</span></label>
                    <input type="date" value={data.tgl_selesai} onChange={(e) => setData('tgl_selesai', e.target.value)} className={input} />
                    {errors.tgl_selesai && <div className="text-red-600 text-xs mt-1">{errors.tgl_selesai}</div>}
                </div>
            </div>
            <div>
                <label className="block text-sm font-medium mb-1">Catatan</label>
                <textarea value={data.catatan || ''} onChange={(e) => setData('catatan', e.target.value)} className={input} rows={2} />
            </div>
            <Tombol disabled={processing}>Simpan</Tombol>
        </form>
    );
}

export default function Index({ data, bisa_buat }) {
    const [tambah, setTambah] = useState(false);
    const tahunIni = new Date().getFullYear();

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Tahap</h1>
                {bisa_buat && !tambah && <Tombol onClick={() => setTambah(true)}>Buat Tahap</Tombol>}
            </div>

            {tambah && (
                <Kartu judul="Buat Tahap Baru">
                    <FormulirTahap
                        awal={{ tahun: tahunIni, nama: '', tgl_mulai: '', tgl_selesai: '', catatan: '' }}
                        aksi="/tahap"
                        metode="post"
                        onSelesai={() => setTambah(false)}
                    />
                    <button onClick={() => setTambah(false)} className="text-sm text-slate-500 hover:underline mt-3">Batal</button>
                </Kartu>
            )}

            <Kartu>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="py-2 pr-4 font-medium text-slate-600">Tahun</th>
                                <th className="py-2 pr-4 font-medium text-slate-600">Nama</th>
                                <th className="py-2 pr-4 font-medium text-slate-600">Rentang</th>
                                <th className="py-2 pr-4 font-medium text-slate-600">Status</th>
                                <th className="py-2 pr-4 font-medium text-slate-600 text-right">Baris</th>
                                <th className="py-2 pr-4 font-medium text-slate-600 text-right">Total Anggaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.map((t) => (
                                <tr key={t.id} className="border-t hover:bg-slate-50">
                                    <td className="py-2 pr-4 tabular-nums">{t.tahun}</td>
                                    <td className="py-2 pr-4">
                                        <Link href={`/tahap/${t.id}`} className="text-blue-600 hover:underline font-medium">
                                            {t.nama}
                                        </Link>
                                    </td>
                                    <td className="py-2 pr-4 text-slate-600">
                                        {tanggal(t.tgl_mulai)} s/d {tanggal(t.tgl_selesai)}
                                    </td>
                                    <td className="py-2 pr-4">{lencanaStatus(t.status)}</td>
                                    <td className="py-2 pr-4 text-right tabular-nums">{t.jml_baris}</td>
                                    <td className="py-2 pr-4 text-right tabular-nums">{rupiah(t.total_anggaran)}</td>
                                </tr>
                            ))}
                            {data.data.length === 0 && <SelKosong colspan={6} teks="Belum ada tahap. Buat tahap untuk tahun berjalan (N) dan tahun berikutnya (N+1)." />}
                        </tbody>
                    </table>
                </div>
                <Paginasi data={data} />
            </Kartu>
        </div>
    );
}
