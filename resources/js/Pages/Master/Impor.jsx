import { Link, useForm } from '@inertiajs/react';
import { Kartu, Tombol } from '../../umum/ui';

export default function Impor({ jenis, judul }) {
    const { data, setData, post, processing, errors } = useForm({
        berkas: null,
    });

    const kirim = (e) => {
        e.preventDefault();
        post(`/${jenis}/impor/pratampil`);
    };

    return (
        <div className="space-y-4 max-w-2xl">
            <div className="flex items-center gap-3">
                <Link href={`/${jenis}`} className="text-sm text-blue-600 hover:underline">← {judul}</Link>
                <h1 className="text-2xl font-bold">Impor {judul} dari Excel</h1>
            </div>

            <Kartu>
                <form onSubmit={kirim} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium mb-1">Berkas Excel (.xlsx / .xls / .csv)</label>
                        <input
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            onChange={(e) => setData('berkas', e.target.files[0])}
                            className="text-sm"
                        />
                        {errors.berkas && <div className="text-red-600 text-xs mt-1">{errors.berkas}</div>}
                    </div>

                    <div className="flex gap-2">
                        <Tombol disabled={processing}>Pratampil</Tombol>
                        <a href={`/${jenis}/impor/template`}>
                            <Tombol varian="sekunder" type="button">Unduh template</Tombol>
                        </a>
                    </div>
                </form>
            </Kartu>

            <p className="text-xs text-slate-500">
                Baris pertama berkas dianggap judul kolom. Pada langkah pratampil Anda memetakan kolom Kode dan kolom Nama.
                Baris dengan kode kosong, kode lebih dari 30 karakter, atau nama kosong akan dilewati;
                kode yang sudah ada akan diperbarui namanya.
                {jenis === 'rekening' && ' Template memiliki kolom Kode Induk (opsional): bila diisi dengan kode yang dikenal, rekening menjadi sub-rekening dari induk tersebut; bila kosong menjadi rekening top-level. Baris dengan kode induk tak dikenal akan dilewati.'}
            </p>
        </div>
    );
}
