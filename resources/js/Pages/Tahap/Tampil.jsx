import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import { Kartu, Tombol, lencanaStatus, rupiah, tanggal } from '../../umum/ui';

export default function Tampil({ tahap, total_anggaran, jml_baris, tahap_lain, bisa_ubah, bisa_kunci, bisa_salin, bisa_impor, jenis_kebutuhan = [], jenis_tambahan = [] }) {
    const [ubah, setUbah] = useState(false);
    const [salin, setSalin] = useState(false);
    const [impor, setImpor] = useState(false);
    const [modeTemplate, setModeTemplate] = useState('kosong');
    const formImpor = useForm({
        berkas: null,
        kebutuhan: jenis_kebutuhan.map((j) => j.id),
        tambahan: jenis_tambahan.map((j) => j.id),
    });
    const formUbah = useForm({
        tahun: tahap.tahun,
        nama: tahap.nama,
        tgl_mulai: tahap.tgl_mulai?.slice(0, 10),
        tgl_selesai: tahap.tgl_selesai?.slice(0, 10),
        catatan: tahap.catatan || '',
    });
    const formSalin = useForm({ sumber_id: '' });

    const kirimUbah = (e) => {
        e.preventDefault();
        formUbah.put(`/tahap/${tahap.id}`, { onSuccess: () => setUbah(false) });
    };

    const kirimSalin = (e) => {
        e.preventDefault();
        if (!formSalin.data.sumber_id) return;
        formSalin.post(`/tahap/${tahap.id}/salin`, { onSuccess: () => setSalin(false) });
    };

    const toggleJenis = (kelompok, id) => {
        const daftar = formImpor.data[kelompok];
        formImpor.setData(kelompok, daftar.includes(id) ? daftar.filter((x) => x !== id) : [...daftar, id]);
    };

    const unduhTemplate = () => {
        const q = new URLSearchParams();
        q.append('mode', modeTemplate);
        formImpor.data.kebutuhan.forEach((id) => q.append('kebutuhan[]', id));
        formImpor.data.tambahan.forEach((id) => q.append('tambahan[]', id));
        window.location.href = `/tahap/${tahap.id}/impor/template?${q.toString()}`;
    };

    const kirimImpor = (e) => {
        e.preventDefault();
        if (!formImpor.data.berkas) {
            alert('Pilih berkas Excel terlebih dahulu.');
            return;
        }
        formImpor.post(`/tahap/${tahap.id}/impor/pratampil`);
    };

    const aksi = (url, label, konfirmasi) => {
        if (confirm(konfirmasi)) router.post(url);
    };

    const input = 'w-full border rounded px-3 py-2 text-sm';

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-3">
                <Link href="/tahap" className="text-sm text-blue-600 hover:underline">← Tahap</Link>
                <h1 className="text-2xl font-bold">
                    {tahap.tahun} - {tahap.nama}
                </h1>
                {lencanaStatus(tahap.status)}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <Kartu
                    judul="Detail Tahap"
                    aksi={bisa_ubah && !ubah && (
                        <button onClick={() => setUbah(true)} className="text-sm text-blue-600 hover:underline">Ubah</button>
                    )}
                >
                    {ubah ? (
                        <form onSubmit={kirimUbah} className="space-y-3">
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-sm font-medium mb-1">Tahun</label>
                                    <input type="number" value={formUbah.data.tahun} onChange={(e) => formUbah.setData('tahun', e.target.value)} className={input} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">Nama</label>
                                    <input type="text" value={formUbah.data.nama} onChange={(e) => formUbah.setData('nama', e.target.value)} className={input} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">Tanggal mulai</label>
                                    <input type="date" value={formUbah.data.tgl_mulai} onChange={(e) => formUbah.setData('tgl_mulai', e.target.value)} className={input} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">Tanggal selesai</label>
                                    <input type="date" value={formUbah.data.tgl_selesai} onChange={(e) => formUbah.setData('tgl_selesai', e.target.value)} className={input} />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-1">Catatan</label>
                                <textarea value={formUbah.data.catatan} onChange={(e) => formUbah.setData('catatan', e.target.value)} className={input} rows={2} />
                            </div>
                            <div className="flex gap-2">
                                <Tombol disabled={formUbah.processing}>Simpan</Tombol>
                                <Tombol varian="sekunder" type="button" onClick={() => setUbah(false)}>Batal</Tombol>
                            </div>
                        </form>
                    ) : (
                        <dl className="text-sm space-y-2">
                            <div className="flex"><dt className="w-32 text-slate-500">Rentang</dt><dd>{tanggal(tahap.tgl_mulai)} s/d {tanggal(tahap.tgl_selesai)}</dd></div>
                            <div className="flex"><dt className="w-32 text-slate-500">Status</dt><dd>{lencanaStatus(tahap.status)}</dd></div>
                            <div className="flex"><dt className="w-32 text-slate-500">Baris anggaran</dt><dd>{jml_baris}</dd></div>
                            <div className="flex"><dt className="w-32 text-slate-500">Total anggaran</dt><dd className="font-semibold">{rupiah(total_anggaran)}</dd></div>
                            {tahap.catatan && <div className="flex"><dt className="w-32 text-slate-500">Catatan</dt><dd>{tahap.catatan}</dd></div>}
                        </dl>
                    )}
                </Kartu>

                <Kartu judul="Aksi">
                    <div className="space-y-3 text-sm">
                        <Link href={`/tahap/${tahap.id}/anggaran`} className="block">
                            <Tombol className="w-full">Buka Matriks Anggaran</Tombol>
                        </Link>
                        <Link href={`/laporan?tahap_id=${tahap.id}`} className="block">
                            <Tombol varian="sekunder" className="w-full">Lihat Laporan</Tombol>
                        </Link>
                        {bisa_ubah && tahap.status === 'draf' && (
                            <Tombol varian="hijau" className="w-full"
                                onClick={() => aksi(`/tahap/${tahap.id}/finalkan`, 'Finalkan', 'Finalkan tahap ini?')}>
                                Finalkan Tahap
                            </Tombol>
                        )}
                        {bisa_kunci && (
                            <Tombol varian="kuning" className="w-full"
                                onClick={() => aksi(`/tahap/${tahap.id}/kunci`, 'Kunci', 'KUNCI tahap ini? Tahap yang terkunci tidak bisa diubah lagi oleh siapa pun.')}>
                                Kunci Tahap (Sahkan)
                            </Tombol>
                        )}
                        {tahap.status === 'terkunci' && (
                            <p className="text-xs text-slate-500">
                                Tahap terkunci tidak bisa diubah. Untuk koreksi, buat tahap baru (boleh overlap tanggal).
                            </p>
                        )}
                    </div>
                </Kartu>
            </div>

            {bisa_salin && (
                <Kartu judul="Salin Anggaran dari Tahap Lain">
                    {!salin ? (
                        <Tombol varian="sekunder" onClick={() => setSalin(true)}>Salin…</Tombol>
                    ) : (
                        <form onSubmit={kirimSalin} className="space-y-3 max-w-md">
                            <div>
                                <label className="block text-sm font-medium mb-1">Tahap sumber</label>
                                <select
                                    value={formSalin.data.sumber_id}
                                    onChange={(e) => formSalin.setData('sumber_id', e.target.value)}
                                    className={input}
                                >
                                    <option value="">— pilih —</option>
                                    {tahap_lain.map((t) => (
                                        <option key={t.id} value={t.id}>
                                            {t.tahun} - {t.nama} ({t.status})
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <p className="text-xs text-slate-500">
                                Menyalin seluruh baris (nilai, volume, rincian kebutuhan) sebagai titik awal.
                                Baris yang sudah ada di tahap ini dilewati.
                            </p>
                            <div className="flex gap-2">
                                <Tombol disabled={formSalin.processing}>Salin</Tombol>
                                <Tombol varian="sekunder" type="button" onClick={() => setSalin(false)}>Batal</Tombol>
                            </div>
                        </form>
                    )}
                </Kartu>
            )}

            {bisa_impor && (
                <Kartu judul="Impor Matriks dari Excel">
                    {!impor ? (
                        <Tombol varian="sekunder" onClick={() => setImpor(true)}>Impor&hellip;</Tombol>
                    ) : (
                        <div className="space-y-4">
                            <div>
                                <p className="text-sm font-medium mb-1">Jenis kebutuhan yang diikutsertakan</p>
                                {jenis_kebutuhan.length === 0 && <p className="text-xs text-slate-400">Tidak ada.</p>}
                                <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                                    {jenis_kebutuhan.map((j) => (
                                        <label key={j.id} className="inline-flex items-center gap-1">
                                            <input type="checkbox" checked={formImpor.data.kebutuhan.includes(j.id)} onChange={() => toggleJenis('kebutuhan', j.id)} />
                                            {j.nama}
                                        </label>
                                    ))}
                                </div>
                            </div>
                            <div>
                                <p className="text-sm font-medium mb-1">Jenis kebutuhan tambahan yang diikutsertakan</p>
                                {jenis_tambahan.length === 0 && <p className="text-xs text-slate-400">Tidak ada.</p>}
                                <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                                    {jenis_tambahan.map((j) => (
                                        <label key={j.id} className="inline-flex items-center gap-1">
                                            <input type="checkbox" checked={formImpor.data.tambahan.includes(j.id)} onChange={() => toggleJenis('tambahan', j.id)} />
                                            {j.nama}
                                        </label>
                                    ))}
                                </div>
                            </div>
                            <div>
                                <p className="text-sm font-medium mb-1">Template</p>
                                <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                                    <label className="inline-flex items-center gap-1">
                                        <input type="radio" name="modeTemplate" value="kosong" checked={modeTemplate === 'kosong'} onChange={() => setModeTemplate('kosong')} />
                                        Kosong (hanya judul kolom)
                                    </label>
                                    <label className="inline-flex items-center gap-1">
                                        <input type="radio" name="modeTemplate" value="data" checked={modeTemplate === 'data'} onChange={() => setModeTemplate('data')} />
                                        Berisi data tahap ini (untuk revisi)
                                    </label>
                                    <Tombol varian="sekunder" type="button" onClick={unduhTemplate}>Unduh Template</Tombol>
                                </div>
                            </div>
                            <form onSubmit={kirimImpor} className="space-y-3">
                                <div>
                                    <label className="block text-sm font-medium mb-1">Berkas Excel (.xlsx / .xls / .csv)</label>
                                    <input type="file" accept=".xlsx,.xls,.csv" onChange={(e) => formImpor.setData('berkas', e.target.files[0])} className="text-sm" />
                                </div>
                                <p className="text-xs text-slate-500">
                                    Satu baris = satu kombinasi SKPD &times; rekening. Baris berkode tak dikenal dilewati.
                                    Sel kosong tidak mengubah data lama; baris baru memakai 0.
                                </p>
                                <div className="flex gap-2">
                                    <Tombol disabled={formImpor.processing}>Unggah &amp; Pratampil</Tombol>
                                    <Tombol varian="sekunder" type="button" onClick={() => setImpor(false)}>Batal</Tombol>
                                </div>
                            </form>
                        </div>
                    )}
                </Kartu>
            )}
        </div>
    );
}
