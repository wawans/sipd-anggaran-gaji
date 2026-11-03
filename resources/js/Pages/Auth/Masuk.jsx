import { useForm } from '@inertiajs/react';
import { Tombol } from '../../umum/ui';

export default function Masuk() {
    const { data, setData, post, processing, errors } = useForm({
        username: '',
        kata_sandi: '',
        ingat: false,
    });

    const kirim = (e) => {
        e.preventDefault();
        post('/masuk');
    };

    return (
        <div className="min-h-screen bg-slate-100 flex items-center justify-center p-4">
            <div className="bg-white rounded-lg shadow p-8 w-full max-w-sm">
                <h1 className="text-xl font-bold mb-1">SIPD Anggaran Gaji</h1>
                <p className="text-sm text-slate-500 mb-6">Masuk untuk mengelola anggaran gaji pegawai.</p>
                <form onSubmit={kirim} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium mb-1">Username</label>
                        <input
                            type="text"
                            value={data.username}
                            onChange={(e) => setData('username', e.target.value)}
                            className="w-full border rounded px-3 py-2 text-sm"
                            autoFocus
                        />
                        {errors.username && <div className="text-red-600 text-xs mt-1">{errors.username}</div>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium mb-1">Kata sandi</label>
                        <input
                            type="password"
                            value={data.kata_sandi}
                            onChange={(e) => setData('kata_sandi', e.target.value)}
                            className="w-full border rounded px-3 py-2 text-sm"
                        />
                        {errors.kata_sandi && <div className="text-red-600 text-xs mt-1">{errors.kata_sandi}</div>}
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={data.ingat}
                            onChange={(e) => setData('ingat', e.target.checked)}
                            className="h-4 w-4"
                        />
                        Ingat saya
                    </label>
                    <Tombol className="w-full" disabled={processing}>
                        Masuk
                    </Tombol>
                </form>
            </div>
        </div>
    );
}

Masuk.layout = (page) => page;
