import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Kartu, Tombol } from '../../umum/ui';

export default function Pratampil({ jenis, judul, token, berkas, header, contoh, jml_baris }) {
    const [kolomKode, setKolomKode] = useState('');
    const [kolomNama, setKolomNama] = useState('');

    const input = 'w-full border rounded px-3 py-2 text-sm';

    const simpan = () => {
        if (kolomKode === '' || kolomNama === '') {
            alert('Pilih kolom Kode dan kolom Nama terlebih dahulu.');
            return;
        }
        if (kolomKode === kolomNama) {
            alert('Kolom Kode dan kolom Nama harus berbeda.');
            return;
        }
        if (confirm(`Simpan impor ${judul}: ${jml_baris} baris dari ${berkas}?`)) {
            router.post(`/${jenis}/impor/simpan`, {
                token,
                kolom_kode: parseInt(kolomKode, 10),
                kolom_nama: parseInt(kolomNama, 10),
            });
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-3">
                <Link href={`/${jenis}/impor`} className="text-sm text-blue-600 hover:underline">← Impor</Link>
                <h1 className="text-2xl font-bold">Pratampil Impor {judul}</h1>
            </div>

            <Kartu judul="Berkas">
                <dl className="text-sm space-y-2">
                    <div className="flex"><dt className="w-40 text-slate-500">Nama berkas</dt><dd>{berkas}</dd></div>
                    <div className="flex"><dt className="w-40 text-slate-500">Jumlah baris data</dt><dd className="font-semibold">{jml_baris}</dd></div>
                    <div className="flex"><dt className="w-40 text-slate-500">Kolom terdeteksi</dt><dd>{header.join(', ')}</dd></div>
                </dl>
            </Kartu>

            <Kartu judul="Contoh isi berkas (5 baris pertama)">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                {header.map((h, i) => (
                                    <th key={i} className="py-2 pr-4 font-medium text-slate-600">
                                        Kolom {i + 1}: {h || <span className="text-slate-400">(kosong)</span>}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {contoh.map((baris, i) => (
                                <tr key={i} className="border-t">
                                    {header.map((_, j) => (
                                        <td key={j} className="py-2 pr-4">{String(baris[j] ?? '')}</td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Kartu>

            <Kartu judul="Pemetaan kolom">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium mb-1">Kolom Kode <span className="text-red-600">*</span></label>
                        <select value={kolomKode} onChange={(e) => setKolomKode(e.target.value)} className={input}>
                            <option value="">— Pilih kolom —</option>
                            {header.map((h, i) => (
                                <option key={i} value={i}>Kolom {i + 1}: {h || '(kosong)'}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium mb-1">Kolom Nama <span className="text-red-600">*</span></label>
                        <select value={kolomNama} onChange={(e) => setKolomNama(e.target.value)} className={input}>
                            <option value="">— Pilih kolom —</option>
                            {header.map((h, i) => (
                                <option key={i} value={i}>Kolom {i + 1}: {h || '(kosong)'}</option>
                            ))}
                        </select>
                    </div>
                </div>
                <p className="text-xs text-slate-500 mt-3">
                    Kode maksimal 30 karakter. Baris dengan kode kosong dilewati; kode yang sudah ada akan diperbarui namanya (upsert).
                </p>
            </Kartu>

            <div className="flex gap-2">
                <Tombol onClick={simpan}>Simpan Impor</Tombol>
                <Link href={`/${jenis}/impor`}><Tombol varian="sekunder">Batal</Tombol></Link>
            </div>
        </div>
    );
}
