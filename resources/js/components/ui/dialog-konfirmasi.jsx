import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

/**
 * Dialog konfirmasi bersama berbasis shadcn AlertDialog.
 *
 * Satu-satunya dialog konfirmasi yang dipakai di aplikasi (simpan impor,
 * hapus, finalkan, kunci tahap, ...). Teks pengguna Indonesia.
 *
 * @param {boolean} terbuka - status dialog (controlled)
 * @param {(terbuka: boolean) => void} onTerbukaBerubah
 * @param {string} judul - judul dialog (wajib, untuk aksesibilitas)
 * @param {string} [deskripsi] - penjelasan singkat
 * @param {string} [labelAksi='Ya'] - teks tombol konfirmasi
 * @param {string} [labelBatal='Batal'] - teks tombol batal
 * @param {'default' | 'destructive'} [varianAksi='default'] - 'destructive' untuk aksi hapus / tak bisa dibatalkan
 * @param {() => void} onKonfirmasi - dipanggil saat tombol konfirmasi diklik
 */
export function DialogKonfirmasi({
    terbuka,
    onTerbukaBerubah,
    judul,
    deskripsi,
    labelAksi = 'Ya',
    labelBatal = 'Batal',
    varianAksi = 'default',
    onKonfirmasi,
}) {
    return (
        <AlertDialog open={terbuka} onOpenChange={onTerbukaBerubah}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{judul}</AlertDialogTitle>
                    {deskripsi ? <AlertDialogDescription>{deskripsi}</AlertDialogDescription> : null}
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>{labelBatal}</AlertDialogCancel>
                    <AlertDialogAction variant={varianAksi} onClick={onKonfirmasi}>
                        {labelAksi}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
