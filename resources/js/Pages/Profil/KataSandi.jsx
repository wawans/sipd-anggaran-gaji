import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Kartu, Tombol } from '../../umum/ui';

export default function KataSandi() {
    const { data, setData, put, processing, errors, reset } = useForm({
        kata_sandi_lama: '',
        kata_sandi: '',
        kata_sandi_confirmation: '',
    });
    const [galatKonfirmasi, setGalatKonfirmasi] = useState('');

    const kirim = (e) => {
        e.preventDefault();
        if (data.kata_sandi !== data.kata_sandi_confirmation) {
            setGalatKonfirmasi('Konfirmasi kata sandi tidak sama.');
            return;
        }
        setGalatKonfirmasi('');
        put('/kata-sandi', { onSuccess: () => reset() });
    };

    const input = 'w-full border rounded px-3 py-2 text-sm';

    return (
        <div className="max-w-xl">
            <h1 className="text-xl font-bold mb-4">Ubah Kata Sandi</h1>
            <Kartu>
                <form onSubmit={kirim} className="space-y-3">
                    <div>
                        <label className="block text-sm font-medium mb-1">
                            Kata sandi lama <span className="text-red-600">*</span>
                        </label>
                        <input
                            type="password"
                            value={data.kata_sandi_lama}
                            onChange={(e) => setData('kata_sandi_lama', e.target.value)}
                            className={input}
                            autoComplete="current-password"
                        />
                        {errors.kata_sandi_lama && <div className="text-red-600 text-xs mt-1">{errors.kata_sandi_lama}</div>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium mb-1">
                            Kata sandi baru <span className="text-red-600">*</span>
                        </label>
                        <input
                            type="password"
                            value={data.kata_sandi}
                            onChange={(e) => { setData('kata_sandi', e.target.value); setGalatKonfirmasi(''); }}
                            className={input}
                            autoComplete="new-password"
                        />
                        {errors.kata_sandi && <div className="text-red-600 text-xs mt-1">{errors.kata_sandi}</div>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium mb-1">
                            Konfirmasi kata sandi <span className="text-red-600">*</span>
                        </label>
                        <input
                            type="password"
                            value={data.kata_sandi_confirmation}
                            onChange={(e) => { setData('kata_sandi_confirmation', e.target.value); setGalatKonfirmasi(''); }}
                            className={input}
                            autoComplete="new-password"
                        />
                        {galatKonfirmasi && <div className="text-red-600 text-xs mt-1">{galatKonfirmasi}</div>}
                    </div>
                    <div>
                        <Tombol type="submit" disabled={processing}>Simpan</Tombol>
                    </div>
                </form>
            </Kartu>
        </div>
    );
}
