import { Link, useForm } from '@inertiajs/react';
import { Kartu, Tombol } from '../../umum/ui';

export default function Impor({ template }) {
    const { data, setData, post, processing, errors } = useForm({
        template_id: template[0]?.id || '',
        periode: new Date().toISOString().slice(0, 7),
        mode: 'timpa',
        berkas: null,
    });

    const kirim = (e) => {
        e.preventDefault();
        post('/realisasi/impor/pratampil');
    };

    const input = 'w-full border rounded px-3 py-2 text-sm';

    return (
        <div className="space-y-4 max-w-2xl">
            <div className="flex items-center gap-3">
                <Link href="/realisasi" className="text-sm text-blue-600 hover:underline">← Realisasi</Link>
                <h1 className="text-2xl font-bold">Impor Realisasi dari Excel</h1>
            </div>

            <Kartu>
                <form onSubmit={kirim} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium mb-1">Template mapping</label>
                        <select value={data.template_id} onChange={(e) => setData('template_id', e.target.value)} className={input}>
                            {template.map((t) => <option key={t.id} value={t.id}>{t.nama}</option>)}
                        </select>
                        {errors.template_id && <div className="text-red-600 text-xs mt-1">{errors.template_id}</div>}
                        {template.length === 0 && (
                            <p className="text-xs text-red-600 mt-1">
                                Belum ada template. <Link href="/mapping-template" className="underline">Buat template mapping</Link> dulu.
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-1">Periode (bulan)</label>
                        <input type="month" value={data.periode} onChange={(e) => setData('periode', e.target.value)} className={input} />
                        {errors.periode && <div className="text-red-600 text-xs mt-1">{errors.periode}</div>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-1">Jika periode ini sudah ada datanya</label>
                        <div className="space-y-2 text-sm">
                            <label className="flex items-start gap-2">
                                <input type="radio" name="mode" value="timpa" checked={data.mode === 'timpa'} onChange={() => setData('mode', 'timpa')} className="mt-1" />
                                <span><span className="font-medium">Timpa</span> — data lama diganti dengan data berkas ini.</span>
                            </label>
                            <label className="flex items-start gap-2">
                                <input type="radio" name="mode" value="jumlahkan" checked={data.mode === 'jumlahkan'} onChange={() => setData('mode', 'jumlahkan')} className="mt-1" />
                                <span><span className="font-medium">Dijumlahkan</span> — nilai berkas ditambahkan ke data yang sudah ada.</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-1">Berkas Excel (.xlsx / .xls / .csv)</label>
                        <input type="file" accept=".xlsx,.xls,.csv" onChange={(e) => setData('berkas', e.target.files[0])} className="text-sm" />
                        {errors.berkas && <div className="text-red-600 text-xs mt-1">{errors.berkas}</div>}
                    </div>

                    <Tombol disabled={processing || template.length === 0}>Pratampil</Tombol>
                </form>
            </Kartu>

            <p className="text-xs text-slate-500">
                Format berkas: 1 baris per SKPD, kolom-kolom berisi nominal per rekening sesuai template mapping.
                Baris SKPD yang tidak cocok dilewati dan dilaporkan.
            </p>
        </div>
    );
}
