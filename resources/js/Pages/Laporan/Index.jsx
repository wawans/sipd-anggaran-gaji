import { router, usePage } from '@inertiajs/react';
import { Kartu, Tombol, rupiah, lencanaStatus, SelKosong } from '../../umum/ui';

function TabelSelisih({ judul, baris }) {
    return (
        <Kartu judul={judul}>
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left">
                            <th className="py-2 pr-4 font-medium text-slate-600">Kode</th>
                            <th className="py-2 pr-4 font-medium text-slate-600">Nama</th>
                            <th className="py-2 pr-4 font-medium text-slate-600 text-right">Anggaran</th>
                            <th className="py-2 pr-4 font-medium text-slate-600 text-right">Realisasi</th>
                            <th className="py-2 pr-4 font-medium text-slate-600 text-right">Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        {baris.map((r, i) => (
                            <tr key={i} className="border-t hover:bg-slate-50">
                                <td className="py-2 pr-4">{r.kode}</td>
                                <td className="py-2 pr-4">{r.nama}</td>
                                <td className="py-2 pr-4 text-right">{rupiah(r.total_anggaran)}</td>
                                <td className="py-2 pr-4 text-right">{rupiah(r.total_realisasi)}</td>
                                <td className={`py-2 pr-4 text-right font-medium ${r.selisih < 0 ? 'text-red-600' : ''}`}>
                                    {rupiah(r.selisih)}
                                </td>
                            </tr>
                        ))}
                        {baris.length === 0 && <SelKosong colspan={5} />}
                    </tbody>
                </table>
            </div>
        </Kartu>
    );
}

export default function Index({ daftar_tahap, tahap_aktif, laporan }) {
    const { auth } = usePage().props;
    const bisaEkspor = (auth.hak_akses || []).includes('laporan.export');

    const pilih = (id) => {
        router.get('/laporan', { tahap_id: id || undefined });
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between flex-wrap gap-3">
                <h1 className="text-2xl font-bold">Laporan</h1>
                <div className="flex gap-2 items-center">
                    <select
                        value={tahap_aktif?.id || ''}
                        onChange={(e) => pilih(e.target.value)}
                        className="border rounded px-3 py-2 text-sm"
                    >
                        {daftar_tahap.map((t) => (
                            <option key={t.id} value={t.id}>
                                {t.tahun} - {t.nama} ({t.status})
                            </option>
                        ))}
                    </select>
                    {bisaEkspor && tahap_aktif && (
                        <a href={`/laporan/ekspor?tahap_id=${tahap_aktif.id}`}>
                            <Tombol varian="hijau">Ekspor Excel</Tombol>
                        </a>
                    )}
                </div>
            </div>

            {!tahap_aktif && (
                <Kartu>
                    <p className="text-sm text-slate-500">Belum ada tahap. Buat tahap terlebih dahulu.</p>
                </Kartu>
            )}

            {tahap_aktif && laporan && (
                <>
                    <div className="flex items-center gap-3">
                        <h2 className="text-lg font-semibold">
                            {tahap_aktif.tahun} - {tahap_aktif.nama}
                        </h2>
                        {lencanaStatus(tahap_aktif.status)}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Kartu>
                            <div className="text-2xl font-bold">{rupiah(laporan.ringkas.total_anggaran)}</div>
                            <div className="text-sm text-slate-500">Total anggaran (Σ total pagu)</div>
                        </Kartu>
                        <Kartu>
                            <div className="text-2xl font-bold">{rupiah(laporan.ringkas.total_realisasi)}</div>
                            <div className="text-sm text-slate-500">Total realisasi</div>
                        </Kartu>
                        <Kartu>
                            <div className={`text-2xl font-bold ${laporan.ringkas.selisih < 0 ? 'text-red-600' : ''}`}>
                                {rupiah(laporan.ringkas.selisih)}
                            </div>
                            <div className="text-sm text-slate-500">Selisih (anggaran − realisasi)</div>
                        </Kartu>
                    </div>

                    <TabelSelisih judul="Per SKPD" baris={laporan.per_skpd} />
                    <TabelSelisih judul="Per Rekening" baris={laporan.per_rekening} />

                    <p className="text-xs text-slate-500">
                        Realisasi per tahap = jumlah realisasi bulanan yang periodenya masuk rentang tanggal tahap.
                        Selisih positif berarti sisa anggaran (mis. porsi jabatan kosong yang belum terisi).
                    </p>
                </>
            )}
        </div>
    );
}
