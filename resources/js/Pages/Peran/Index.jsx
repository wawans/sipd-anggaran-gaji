import { Link } from '@inertiajs/react';
import { Kartu, SelKosong } from '../../umum/ui';

export default function Index({ data }) {
    return (
        <div className="space-y-4">
            <h1 className="text-2xl font-bold">Peran & Hak Akses</h1>
            <Kartu>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="py-2 pr-4 font-medium text-slate-600">Peran</th>
                                <th className="py-2 pr-4 font-medium text-slate-600 text-right">Jml Hak Akses</th>
                                <th className="py-2 pr-4 font-medium text-slate-600 text-right">Jml Pengguna</th>
                                <th className="py-2 font-medium text-slate-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.map((p) => (
                                <tr key={p.id} className="border-t hover:bg-slate-50">
                                    <td className="py-2 pr-4 font-medium">{p.nama}</td>
                                    <td className="py-2 pr-4 text-right tabular-nums">{p.jml_hak}</td>
                                    <td className="py-2 pr-4 text-right tabular-nums">{p.jml_pengguna}</td>
                                    <td className="py-2">
                                        {p.nama === 'Superadmin' ? (
                                            <span className="text-xs text-slate-400">Selalu semua hak akses</span>
                                        ) : (
                                            <Link href={`/peran/${p.id}/ubah`} className="text-blue-600 hover:underline text-sm">
                                                Atur Hak Akses
                                            </Link>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {data.length === 0 && <SelKosong colspan={4} />}
                        </tbody>
                    </table>
                </div>
            </Kartu>
        </div>
    );
}
