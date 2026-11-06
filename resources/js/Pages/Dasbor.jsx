import { Link } from '@inertiajs/react';
import { Kartu, lencanaStatus, tanggal, tanggalWaktu } from '../umum/ui';

export default function Dasbor({ ringkas, tahap_terbaru, audit_terbaru }) {
    const stat = [
        { label: 'SKPD aktif', nilai: ringkas.jml_skpd },
        { label: 'Tahap', nilai: ringkas.jml_tahap },
        { label: 'Tahap terkunci', nilai: ringkas.jml_tahap_terkunci },
    ];

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold">Dasbor</h1>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {stat.map((s) => (
                    <Kartu key={s.label}>
                        <div className="text-3xl font-bold tabular-nums">{s.nilai}</div>
                        <div className="text-sm text-slate-500">{s.label}</div>
                    </Kartu>
                ))}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <Kartu
                    judul="Tahap terbaru"
                    aksi={<Link href="/tahap" className="text-sm text-blue-600 hover:underline">Lihat semua</Link>}
                >
                    <table className="w-full text-sm">
                        <tbody>
                            {tahap_terbaru.map((t) => (
                                <tr key={t.id} className="border-t">
                                    <td className="py-2">
                                        <Link href={`/tahap/${t.id}`} className="text-blue-600 hover:underline">
                                            {t.tahun} - {t.nama}
                                        </Link>
                                        <div className="text-xs text-slate-500">
                                            {tanggal(t.tgl_mulai)} s/d {tanggal(t.tgl_selesai)}
                                        </div>
                                    </td>
                                    <td className="py-2 text-right">{lencanaStatus(t.status)}</td>
                                </tr>
                            ))}
                            {tahap_terbaru.length === 0 && (
                                <tr>
                                    <td className="py-4 text-slate-500 text-sm">Belum ada tahap.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </Kartu>

                <Kartu judul="Aktivitas terbaru">
                    <table className="w-full text-sm">
                        <tbody>
                            {audit_terbaru.map((a) => (
                                <tr key={a.id} className="border-t">
                                    <td className="py-2">
                                        <span className="font-medium">{a.pengguna?.nama || '-'}</span>{' '}
                                        <span className="text-slate-600">
                                            {a.aksi} {a.entitas}
                                        </span>
                                        {a.keterangan && <div className="text-xs text-slate-500">{a.keterangan}</div>}
                                    </td>
                                    <td className="py-2 text-right text-xs text-slate-500 whitespace-nowrap">
                                        {tanggalWaktu(a.waktu)}
                                    </td>
                                </tr>
                            ))}
                            {audit_terbaru.length === 0 && (
                                <tr>
                                    <td className="py-4 text-slate-500 text-sm">Belum ada aktivitas.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </Kartu>
            </div>
        </div>
    );
}
