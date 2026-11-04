import { Link } from '@inertiajs/react';
import { useState, useRef, useEffect } from 'react';

/** Format angka gaya Indonesia: 1.234.567,89 */
export function rupiah(nilai) {
    const n = Number(nilai);
    if (Number.isNaN(n)) return '-';
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(n);
}

export function tanggal(nilai) {
    if (!nilai) return '-';
    return new Date(nilai).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

export function tanggalWaktu(nilai) {
    if (!nilai) return '-';
    return new Date(nilai).toLocaleString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

export function Kartu({ judul, aksi, children, className = '' }) {
    return (
        <div className={`bg-white rounded-lg shadow p-5 ${className}`}>
            {(judul || aksi) && (
                <div className="flex items-center justify-between mb-4">
                    {judul && <h2 className="text-lg font-semibold">{judul}</h2>}
                    {aksi}
                </div>
            )}
            {children}
        </div>
    );
}

export function Tombol({ children, varian = 'utama', className = '', ...props }) {
    const gaya = {
        utama: 'bg-blue-600 hover:bg-blue-700 text-white',
        sekunder: 'bg-slate-200 hover:bg-slate-300 text-slate-800',
        bahaya: 'bg-red-600 hover:bg-red-700 text-white',
        hijau: 'bg-green-600 hover:bg-green-700 text-white',
        kuning: 'bg-yellow-500 hover:bg-yellow-600 text-white',
    }[varian] || 'bg-blue-600 hover:bg-blue-700 text-white';

    return (
        <button {...props} className={`px-4 py-2 rounded text-sm font-medium disabled:opacity-50 ${gaya} ${className}`}>
            {children}
        </button>
    );
}

export function Lencana({ tipe = 'abu', children }) {
    const gaya = {
        hijau: 'bg-green-100 text-green-800',
        kuning: 'bg-yellow-100 text-yellow-800',
        merah: 'bg-red-100 text-red-800',
        abu: 'bg-slate-200 text-slate-700',
        biru: 'bg-blue-100 text-blue-800',
    }[tipe] || 'bg-slate-200 text-slate-700';

    return <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${gaya}`}>{children}</span>;
}

export function lencanaStatus(status) {
    const peta = { draf: 'abu', final: 'biru', terkunci: 'kuning' };
    return <Lencana tipe={peta[status] || 'abu'}>{status}</Lencana>;
}

export function Paginasi({ data }) {
    if (!data?.links || data.links.length <= 3) return null;
    return (
        <div className="flex gap-1 mt-4 flex-wrap">
            {data.links.map((l, i) =>
                l.url ? (
                    <Link
                        key={i}
                        href={l.url}
                        className={`px-3 py-1 rounded border text-sm ${
                            l.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white hover:bg-slate-100'
                        }`}
                        dangerouslySetInnerHTML={{ __html: l.label }}
                    />
                ) : (
                    <span
                        key={i}
                        className="px-3 py-1 rounded border text-sm bg-slate-100 text-slate-400"
                        dangerouslySetInnerHTML={{ __html: l.label }}
                    />
                )
            )}
        </div>
    );
}

export function SelKosong({ colspan, teks = 'Belum ada data.' }) {
    return (
        <tr>
            <td colSpan={colspan} className="px-4 py-6 text-center text-slate-500 text-sm">
                {teks}
            </td>
        </tr>
    );
}

/**
 * Combobox sederhana: ketik untuk mencari, klik untuk memilih.
 * opsi: [{ id, label }], nilai: id yang terpilih, onPilih(id).
 */
export function PilihCari({ opsi = [], nilai, onPilih, placeholder = 'Ketik untuk mencari…' }) {
    const [buka, setBuka] = useState(false);
    const [cari, setCari] = useState('');
    const bungkus = useRef(null);
    const terpilih = opsi.find((o) => String(o.id) === String(nilai));

    useEffect(() => {
        const tutup = (e) => {
            if (bungkus.current && !bungkus.current.contains(e.target)) setBuka(false);
        };
        document.addEventListener('mousedown', tutup);
        return () => document.removeEventListener('mousedown', tutup);
    }, []);

    const tampil = cari
        ? opsi.filter((o) => o.label.toLowerCase().includes(cari.toLowerCase()))
        : opsi;

    const bukaPencarian = () => { setCari(''); setBuka(true); };
    const pilih = (id) => { onPilih(id); setCari(''); setBuka(false); };

    return (
        <div ref={bungkus} className="relative">
            <div className="flex items-center gap-1">
                <input
                    type="text"
                    autoComplete="off"
                    placeholder={placeholder}
                    value={buka ? cari : (terpilih ? terpilih.label : '')}
                    onChange={(e) => { setCari(e.target.value); setBuka(true); }}
                    onFocus={bukaPencarian}
                    className="w-full border rounded px-3 py-2 text-sm"
                />
                {nilai !== '' && nilai != null && (
                    <button
                        type="button"
                        title="Bersihkan pilihan"
                        onClick={() => { onPilih(''); setCari(''); }}
                        className="shrink-0 px-2 text-slate-400 hover:text-slate-700 text-lg leading-none"
                    >
                        ×
                    </button>
                )}
            </div>
            {buka && (
                <ul className="absolute z-30 mt-1 w-full max-h-60 overflow-auto bg-white border rounded-md shadow-lg text-sm">
                    {tampil.length === 0 && (
                        <li className="px-3 py-2 text-slate-500">Tidak ada pilihan yang cocok.</li>
                    )}
                    {tampil.map((o) => (
                        <li key={o.id}>
                            <button
                                type="button"
                                onClick={() => pilih(o.id)}
                                className={`w-full text-left px-3 py-2 hover:bg-blue-50 ${String(o.id) === String(nilai) ? 'bg-blue-100 font-medium' : ''}`}
                            >
                                {o.label}
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
