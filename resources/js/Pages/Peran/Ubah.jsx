import { Link, useForm } from '@inertiajs/react';
import { Kartu, Tombol } from '../../umum/ui';

export default function Ubah({ peran, hak_milik, semua_hak }) {
    const { data, setData, put, processing } = useForm({
        hak_akses: hak_milik || [],
    });

    const toggle = (kode) => {
        setData('hak_akses', data.hak_akses.includes(kode)
            ? data.hak_akses.filter((x) => x !== kode)
            : [...data.hak_akses, kode]);
    };

    const kirim = (e) => {
        e.preventDefault();
        put(`/peran/${peran.id}`);
    };

    // Kelompokkan per area (sebelum titik pertama)
    const kelompok = {};
    semua_hak.forEach((h) => {
        const area = h.kode.split('.')[0];
        kelompok[area] = kelompok[area] || [];
        kelompok[area].push(h);
    });

    return (
        <div className="space-y-4 max-w-3xl">
            <div className="flex items-center gap-3">
                <Link href="/peran" className="text-sm text-blue-600 hover:underline">← Peran</Link>
                <h1 className="text-2xl font-bold">Hak Akses — {peran.nama}</h1>
            </div>

            <form onSubmit={kirim}>
                <div className="space-y-4">
                    {Object.entries(kelompok).map(([area, daftar]) => (
                        <Kartu key={area} judul={area}>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                                {daftar.map((h) => (
                                    <label key={h.kode} className="flex items-start gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={data.hak_akses.includes(h.kode)}
                                            onChange={() => toggle(h.kode)}
                                            className="h-4 w-4 mt-0.5"
                                        />
                                        <span>
                                            <span className="font-mono text-xs text-slate-500">{h.kode}</span>
                                            <br />
                                            {h.nama}
                                        </span>
                                    </label>
                                ))}
                            </div>
                        </Kartu>
                    ))}
                </div>
                <div className="mt-4">
                    <Tombol disabled={processing}>Simpan Hak Akses</Tombol>
                </div>
            </form>
        </div>
    );
}
