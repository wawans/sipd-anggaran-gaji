import { Link, usePage, router } from '@inertiajs/react';

const MENU = [
    {
        grup: 'Utama',
        item: [
            { label: 'Dasbor', href: '/dasbor', hak: 'laporan.view' },
            { label: 'Tahap', href: '/tahap', hak: 'tahap.view' },
            { label: 'Laporan', href: '/laporan', hak: 'laporan.view' },
            { label: 'Perbandingan Tahap', href: '/perbandingan', hak: 'laporan.view' },
        ],
    },
    {
        grup: 'Transaksi',
        item: [
            { label: 'Realisasi', href: '/realisasi', hak: 'realisasi.view' },
            { label: 'Template Mapping', href: '/mapping-template', hak: 'mapping.view' },
        ],
    },
    {
        grup: 'Master',
        item: [
            { label: 'SKPD', href: '/skpd', hak: 'skpd.view' },
            { label: 'Rekening', href: '/rekening', hak: 'rekening.view' },
            { label: 'Jenis Kebutuhan', href: '/jenis-kebutuhan', hak: 'jenis_kebutuhan.view' },
            { label: 'Kebutuhan Tambahan', href: '/jenis-kebutuhan-tambahan', hak: 'kebutuhan_tambahan.view' },
        ],
    },
    {
        grup: 'Administrasi',
        item: [
            { label: 'Pengguna', href: '/pengguna', hak: 'pengguna.view' },
            { label: 'Peran', href: '/peran', hak: 'peran.manage' },
            { label: 'Log Audit', href: '/audit', hak: 'audit.view' },
        ],
    },
];

export default function Utama({ children }) {
    const { auth, flash } = usePage().props;
    const url = usePage().url;
    const bisa = (kode) => (auth.hak_akses || []).includes(kode);

    return (
        <div className="min-h-screen bg-slate-100 flex">
            <aside className="w-60 bg-slate-800 text-slate-100 flex flex-col fixed inset-y-0 z-10">
                <div className="p-4 border-b border-slate-700">
                    <div className="font-bold">SIPD Anggaran Gaji</div>
                    <div className="text-xs text-slate-400 mt-1">
                        {auth.pengguna?.nama} ({(auth.pengguna?.peran || []).join(', ')})
                    </div>
                </div>
                <nav className="flex-1 overflow-y-auto p-3 space-y-4">
                    {MENU.map((g) => {
                        const items = g.item.filter((i) => bisa(i.hak));
                        if (!items.length) return null;
                        return (
                            <div key={g.grup}>
                                <div className="text-xs uppercase tracking-wide text-slate-400 px-2 mb-1">{g.grup}</div>
                                {items.map((i) => (
                                    <Link
                                        key={i.href}
                                        href={i.href}
                                        className={`block px-3 py-2 rounded text-sm mb-0.5 ${
                                            url.startsWith(i.href) ? 'bg-slate-700 text-white font-medium' : 'hover:bg-slate-700/50'
                                        }`}
                                    >
                                        {i.label}
                                    </Link>
                                ))}
                            </div>
                        );
                    })}
                </nav>
                <div className="p-3 border-t border-slate-700">
                    <button
                        onClick={() => router.post('/keluar')}
                        className="w-full text-left px-3 py-2 rounded text-sm hover:bg-slate-700/50"
                    >
                        Keluar
                    </button>
                </div>
            </aside>
            <main className="flex-1 ml-60 p-6 max-w-7xl">
                {flash.sukses && (
                    <div className="mb-4 p-3 bg-green-100 border border-green-300 text-green-800 rounded text-sm">{flash.sukses}</div>
                )}
                {flash.galat && (
                    <div className="mb-4 p-3 bg-red-100 border border-red-300 text-red-800 rounded text-sm">{flash.galat}</div>
                )}
                {children}
            </main>
        </div>
    );
}
