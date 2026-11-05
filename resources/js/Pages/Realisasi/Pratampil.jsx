import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Kartu, Tombol, Lencana, rupiah } from '../../umum/ui';
import { DialogKonfirmasi } from '@/components/ui/dialog-konfirmasi';

export default function Pratampil({ token, periode, mode, template, berkas, jml_cocok, jml_sel, tak_cocok, kolom_tak_ditemukan, contoh }) {
    const [dialogTerbuka, setDialogTerbuka] = useState(false);

    const simpan = () => {
        router.post('/realisasi/impor/simpan', { token });
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-3">
                <Link href="/realisasi/impor" className="text-sm text-blue-600 hover:underline">← Impor</Link>
                <h1 className="text-2xl font-bold">Pratampil Impor</h1>
            </div>

            <Kartu judul="Ringkasan">
                <dl className="text-sm space-y-2">
                    <div className="flex"><dt className="w-40 text-slate-500">Berkas</dt><dd>{berkas}</dd></div>
                    <div className="flex"><dt className="w-40 text-slate-500">Template</dt><dd>{template}</dd></div>
                    <div className="flex"><dt className="w-40 text-slate-500">Periode</dt><dd>{periode}</dd></div>
                    <div className="flex"><dt className="w-40 text-slate-500">Mode</dt><dd><Lencana tipe={mode === 'timpa' ? 'kuning' : 'biru'}>{mode}</Lencana></dd></div>
                    <div className="flex"><dt className="w-40 text-slate-500">Baris cocok</dt><dd className="font-semibold">{jml_cocok} SKPD → {jml_sel} sel</dd></div>
                </dl>
            </Kartu>

            {kolom_tak_ditemukan.length > 0 && (
                <Kartu judul="Kolom template tidak ditemukan di berkas">
                    <ul className="text-sm text-yellow-800 list-disc ml-5">
                        {kolom_tak_ditemukan.map((k) => <li key={k}>{k}</li>)}
                    </ul>
                </Kartu>
            )}

            {tak_cocok.length > 0 && (
                <Kartu judul={`SKPD tidak cocok (${tak_cocok.length}) — akan dilewati`}>
                    <div className="overflow-x-auto max-h-48 overflow-y-auto">
                        <table className="w-full text-sm">
                            <tbody>
                                {tak_cocok.map((t, i) => (
                                    <tr key={i} className="border-t">
                                        <td className="py-1 pr-4 text-slate-500">Baris {t.baris}</td>
                                        <td className="py-1">{t.penanda}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Kartu>
            )}

            <Kartu judul="Contoh baris (10 pertama)">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="py-2 pr-4 font-medium text-slate-600">SKPD</th>
                                <th className="py-2 pr-4 font-medium text-slate-600 text-right">Rekening terisi</th>
                                <th className="py-2 pr-4 font-medium text-slate-600 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {contoh.map((c) => (
                                <tr key={c.skpd_id} className="border-t">
                                    <td className="py-2 pr-4">{c.skpd_kode} - {c.skpd_nama}</td>
                                    <td className="py-2 pr-4 text-right">{c.jml_rekening}</td>
                                    <td className="py-2 pr-4 text-right">{rupiah(c.total)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Kartu>

            <div className="flex gap-2">
                <Tombol disabled={jml_cocok === 0} onClick={() => setDialogTerbuka(true)}>Simpan Impor</Tombol>
                <Link href="/realisasi/impor"><Tombol varian="sekunder">Batal</Tombol></Link>
            </div>
            <DialogKonfirmasi
                terbuka={dialogTerbuka}
                onTerbukaBerubah={setDialogTerbuka}
                judul="Simpan impor realisasi?"
                deskripsi={`Simpan ${jml_sel} sel realisasi periode ${periode} (mode: ${mode}).`}
                labelAksi="Simpan Impor"
                onKonfirmasi={simpan}
            />
        </div>
    );
}
