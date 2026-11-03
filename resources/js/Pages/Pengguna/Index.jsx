import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Kartu, Tombol, Lencana, Paginasi, SelKosong } from '../../umum/ui';

function Formulir({ awal, aksi, metode, daftar_peran, onSelesai }) {
    const { data, setData, post, put, processing, errors } = useForm(awal);

    const kirim = (e) => {
        e.preventDefault();
        const opsi = { onSuccess: () => onSelesai && onSelesai() };
        if (metode === 'put') put(aksi, opsi);
        else post(aksi, opsi);
    };

    const togglePeran = (id) => {
        setData('peran', data.peran.includes(id) ? data.peran.filter((x) => x !== id) : [...data.peran, id]);
    };

    const input = 'w-full border rounded px-3 py-2 text-sm';

    return (
        <form onSubmit={kirim} className="space-y-3">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label className="block text-sm font-medium mb-1">Nama <span className="text-red-600">*</span></label>
                    <input type="text" value={data.nama} onChange={(e) => setData('nama', e.target.value)} className={input} />
                    {errors.nama && <div className="text-red-600 text-xs mt-1">{errors.nama}</div>}
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">Username <span className="text-red-600">*</span></label>
                    <input type="text" value={data.username} onChange={(e) => setData('username', e.target.value)} className={input} />
                    {errors.username && <div className="text-red-600 text-xs mt-1">{errors.username}</div>}
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">
                        Kata sandi {metode === 'post' ? <span className="text-red-600">*</span> : '(kosongkan jika tidak diubah)'}
                    </label>
                    <input type="password" value={data.kata_sandi} onChange={(e) => setData('kata_sandi', e.target.value)} className={input} />
                    {errors.kata_sandi && <div className="text-red-600 text-xs mt-1">{errors.kata_sandi}</div>}
                </div>
                <div className="flex items-end pb-2">
                    <label className="flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={!!data.aktif} onChange={(e) => setData('aktif', e.target.checked)} className="h-4 w-4" />
                        Aktif
                    </label>
                </div>
            </div>
            <div>
                <label className="block text-sm font-medium mb-1">Peran <span className="text-red-600">*</span></label>
                <div className="flex gap-4 flex-wrap">
                    {daftar_peran.map((p) => (
                        <label key={p.id} className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={data.peran.includes(p.id)} onChange={() => togglePeran(p.id)} className="h-4 w-4" />
                            {p.name}
                        </label>
                    ))}
                </div>
                {errors.peran && <div className="text-red-600 text-xs mt-1">{errors.peran}</div>}
            </div>
            <Tombol disabled={processing}>Simpan</Tombol>
        </form>
    );
}

export default function Index({ data, daftar_peran }) {
    const [tambah, setTambah] = useState(false);
    const [ubah, setUbah] = useState(null);

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Pengguna</h1>
                {!tambah && !ubah && <Tombol onClick={() => setTambah(true)}>Tambah Pengguna</Tombol>}
            </div>

            {tambah && (
                <Kartu judul="Tambah Pengguna">
                    <Formulir
                        awal={{ nama: '', username: '', kata_sandi: '', aktif: true, peran: [] }}
                        aksi="/pengguna" metode="post" daftar_peran={daftar_peran}
                        onSelesai={() => setTambah(false)}
                    />
                    <button onClick={() => setTambah(false)} className="text-sm text-slate-500 hover:underline mt-3">Batal</button>
                </Kartu>
            )}

            {ubah && (
                <Kartu judul="Ubah Pengguna">
                    <Formulir
                        awal={{
                            nama: ubah.nama, username: ubah.username, kata_sandi: '',
                            aktif: ubah.aktif, peran: ubah.daftar_peran.map((p) => p.id),
                        }}
                        aksi={`/pengguna/${ubah.id}`} metode="put" daftar_peran={daftar_peran}
                        onSelesai={() => setUbah(null)}
                    />
                    <button onClick={() => setUbah(null)} className="text-sm text-slate-500 hover:underline mt-3">Batal</button>
                </Kartu>
            )}

            <Kartu>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="py-2 pr-4 font-medium text-slate-600">Nama</th>
                                <th className="py-2 pr-4 font-medium text-slate-600">Username</th>
                                <th className="py-2 pr-4 font-medium text-slate-600">Peran</th>
                                <th className="py-2 pr-4 font-medium text-slate-600">Status</th>
                                <th className="py-2 font-medium text-slate-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.map((p) => (
                                <tr key={p.id} className="border-t hover:bg-slate-50">
                                    <td className="py-2 pr-4 font-medium">{p.nama}</td>
                                    <td className="py-2 pr-4">{p.username}</td>
                                    <td className="py-2 pr-4">{p.daftar_peran.map((r) => r.name).join(', ')}</td>
                                    <td className="py-2 pr-4">
                                        {p.aktif ? <Lencana tipe="hijau">Aktif</Lencana> : <Lencana tipe="abu">Nonaktif</Lencana>}
                                    </td>
                                    <td className="py-2">
                                        <button onClick={() => { setUbah(p); setTambah(false); window.scrollTo(0, 0); }} className="text-blue-600 hover:underline text-sm">
                                            Ubah
                                        </button>
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
