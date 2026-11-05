import { useState } from 'react';
import { useForm, usePage, Link, router } from '@inertiajs/react';
import { Kartu, Tombol, Lencana, Paginasi, SelKosong, PilihCari } from '../../umum/ui';
import {
    AlertDialog,
    AlertDialogTrigger,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogCancel,
    AlertDialogAction,
} from '@/components/ui/alert-dialog';

function Formulir({ field, awal, aksi, metode, tombolSimpan, onSelesai, opsiInduk = [] }) {
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
                    ) : f.tipe === 'induk' ? (
                        <PilihCari
                            opsi={opsiInduk}
                            nilai={data[f.nama] ?? ''}
                            onPilih={(id) => setData(f.nama, id === '' ? null : id)}
                            placeholder="Ketik untuk mencari induk... (kosong = top-level)"
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

export default function Index({ judul, rute, kolom, field, data, semua_rekening = [] }) {
    const { auth } = usePage().props;
    const kelolaHak = { skpd: 'skpd.manage', rekening: 'rekening.manage', 'jenis-kebutuhan': 'jenis_kebutuhan.manage', 'jenis-kebutuhan-tambahan': 'kebutuhan_tambahan.manage' }[rute];
    const bisaKelola = (auth.hak_akses || []).includes(kelolaHak);

    const [tambah, setTambah] = useState(false);
    const [ubah, setUbah] = useState(null);

    // Kandidat induk: semua rekening kecuali diri sendiri & keturunannya (cegah siklus)
    const kandidatInduk = (kecuali) => {
        const terlarang = new Set();
        if (kecuali) {
            terlarang.add(kecuali.id);
            const peta = new Map(semua_rekening.map((r) => [r.id, r.parent_rekening_id]));
            let berubah = true;
            while (berubah) {
                berubah = false;
                for (const [id, pid] of peta) {
                    if (pid && terlarang.has(pid) && !terlarang.has(id)) {
                        terlarang.add(id);
                        berubah = true;
                    }
                }
            }
        }
        return semua_rekening
            .filter((r) => !terlarang.has(r.id))
            .map((r) => ({ id: r.id, label: r.kode + ' \u2014 ' + r.nama }));
    };

    const awalKosong = Object.fromEntries(field.map((f) => [f.nama, f.tipe === 'cek' ? true : f.tipe === 'angka' ? 0 : '']));

    const nilaiSel = (baris, k) => {
        if (k.tipe === 'aktif') {
            return baris[k.kunci] ? <Lencana tipe="hijau">Aktif</Lencana> : <Lencana tipe="abu">Nonaktif</Lencana>;
        }
        return baris[k.kunci];
    };

    const hapus = (baris) => {
        router.delete(`/${rute}/${baris.id}`);
    };

    const namaBaris = (baris) => (baris.kode ? `${baris.kode} - ${baris.nama}` : baris.nama);

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Master {judul}</h1>
                {bisaKelola && !tambah && (
                    <div className="flex gap-2">
                        {(rute === 'skpd' || rute === 'rekening') && (
                            <Link href={`/${rute}/impor`}><Tombol varian="sekunder">Impor Excel</Tombol></Link>
                        )}
                        <Tombol onClick={() => { setTambah(true); setUbah(null); }}>Tambah {judul}</Tombol>
                    </div>
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
                        opsiInduk={kandidatInduk(null)}
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
                        opsiInduk={kandidatInduk(ubah)}
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
                                            <div className="flex gap-3">
                                                <button
                                                    onClick={() => { setUbah(baris); setTambah(false); window.scrollTo(0, 0); }}
                                                    className="text-blue-600 hover:underline text-sm"
                                                >
                                                    Ubah
                                                </button>
                                                <AlertDialog>
                                                    <AlertDialogTrigger asChild>
                                                        <button className="text-red-600 hover:underline text-sm">
                                                            Hapus
                                                        </button>
                                                    </AlertDialogTrigger>
                                                    <AlertDialogContent>
                                                        <AlertDialogHeader>
                                                            <AlertDialogTitle>Hapus {judul}?</AlertDialogTitle>
                                                            <AlertDialogDescription>
                                                                Hapus {judul} &quot;{namaBaris(baris)}&quot;?
                                                                Tindakan ini tidak bisa dibatalkan.
                                                            </AlertDialogDescription>
                                                        </AlertDialogHeader>
                                                        <AlertDialogFooter>
                                                            <AlertDialogCancel>Batal</AlertDialogCancel>
                                                            <AlertDialogAction
                                                                variant="destructive"
                                                                onClick={() => hapus(baris)}
                                                            >
                                                                Hapus
                                                            </AlertDialogAction>
                                                        </AlertDialogFooter>
                                                    </AlertDialogContent>
                                                </AlertDialog>
                                            </div>
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
