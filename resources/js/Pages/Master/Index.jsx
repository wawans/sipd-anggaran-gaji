import { useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { Kartu, Tombol, Lencana, Paginasi, SelKosong } from '../../umum/ui';

function Formulir({ field, awal, aksi, metode, tombolSimpan, onSelesai }) {
    const { data, setData, post, put, processing, errors } = useForm(awal);

    const kirim = (e) => {
        e.preventDefault();
        const opsi = { onSuccess: () => onSelesai && onSelesai() };
        if (metode === 'put') put(aksi, opsi);
        else post(aksi, opsi);
    };

    return (
        <form onSubmit={kirim} className="space-y-3">
            {field.map((f) => (
                <div key={f.nama}>
                    <label className="block text-sm font-medium mb-1">
                        {f.label} {f.wajib && <span className="text-red-600">*</span>}
                    </label>
                    {f.tipe === 'cek' ? (
                        <input
                            type="checkbox"
                            checked={!!data[f.nama]}
                            onChange={(e) => setData(f.nama, e.target.checked)}
                            className="h-4 w-4"
                        />
                    ) : (
                        <input
                            type={f.tipe === 'angka' ? 'number' : 'text'}
                            value={data[f.nama] ?? ''}
                            onChange={(e) => setData(f.nama, e.target.value)}
                            className="w-full border rounded px-3 py-2 text-sm"
                        />
                    )}
                    {errors[f.nama] && <div className="text-red-600 text-xs mt-1">{errors[f.nama]}</div>}
                </div>
            ))}
            <Tombol disabled={processing}>{tombolSimpan || 'Simpan'}</Tombol>
        </form>
    );
}

export default function Index({ judul, rute, kolom, field, data }) {
    const { auth } = usePage().props;
    const kelolaHak = { skpd: 'skpd.manage', rekening: 'rekening.manage', 'jenis-kebutuhan': 'jenis_kebutuhan.manage', 'jenis-kebutuhan-tambahan': 'kebutuhan_tambahan.manage' }[rute];
    const bisaKelola = (auth.hak_akses || []).includes(kelolaHak);

    const [tambah, setTambah] = useState(false);
    const [ubah, setUbah] = useState(null);

    const awalKosong = Object.fromEntries(field.map((f) => [f.nama, f.tipe === 'cek' ? true : f.tipe === 'angka' ? 0 : '']));

    const nilaiSel = (baris, k) => {
        if (k.tipe === 'aktif') {
            return baris[k.kunci] ? <Lencana tipe="hijau">Aktif</Lencana> : <Lencana tipe="abu">Nonaktif</Lencana>;
        }
        return baris[k.kunci];
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Master {judul}</h1>
                {bisaKelola && !tambah && (
                    <Tombol onClick={() => { setTambah(true); setUbah(null); }}>Tambah {judul}</Tombol>
                )}
            </div>

            {bisaKelola && tambah && (
                <Kartu judul={`Tambah ${judul}`}>
                    <Formulir
                        field={field}
                        awal={awalKosong}
                        aksi={`/${rute}`}
                        metode="post"
                        tombolSimpan="Simpan"
                        onSelesai={() => setTambah(false)}
                    />
                    <button onClick={() => setTambah(false)} className="text-sm text-slate-500 hover:underline mt-3">
                        Batal
                    </button>
                </Kartu>
            )}

            {bisaKelola && ubah && (
                <Kartu judul={`Ubah ${judul}`}>
                    <Formulir
                        field={field}
                        awal={Object.fromEntries(field.map((f) => [f.nama, ubah[f.nama] ?? (f.tipe === 'cek' ? false : '')]))}
                        aksi={`/${rute}/${ubah.id}`}
                        metode="put"
                        tombolSimpan="Perbarui"
                        onSelesai={() => setUbah(null)}
                    />
                    <button onClick={() => setUbah(null)} className="text-sm text-slate-500 hover:underline mt-3">
                        Batal
                    </button>
                </Kartu>
            )}

            <Kartu>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                {kolom.map((k) => (
                                    <th key={k.kunci} className="py-2 pr-4 font-medium text-slate-600">{k.label}</th>
                                ))}
                                {bisaKelola && <th className="py-2 font-medium text-slate-600">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.map((baris) => (
                                <tr key={baris.id} className="border-t hover:bg-slate-50">
                                    {kolom.map((k) => (
                                        <td key={k.kunci} className="py-2 pr-4">{nilaiSel(baris, k)}</td>
                                    ))}
                                    {bisaKelola && (
                                        <td className="py-2">
                                            <button
                                                onClick={() => { setUbah(baris); setTambah(false); window.scrollTo(0, 0); }}
                                                className="text-blue-600 hover:underline text-sm"
                                            >
                                                Ubah
                                            </button>
                                        </td>
                                    )}
                                </tr>
                            ))}
                            {data.data.length === 0 && <SelKosong colspan={kolom.length + 1} />}
                        </tbody>
                    </table>
                </div>
                <Paginasi data={data} />
            </Kartu>
        </div>
    );
}
