import { Link, usePage, router } from '@inertiajs/react';
import {
    LayoutDashboard,
    CalendarRange,
    FileText,
    GitCompareArrows,
    Wallet,
    ArrowLeftRight,
    Building2,
    Receipt,
    ListPlus,
    CirclePlus,
    Users,
    ShieldCheck,
    ScrollText,
} from 'lucide-react';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarRail,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { Separator } from '@/components/ui/separator';
import { TooltipProvider } from '@/components/ui/tooltip';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Alert, AlertDescription } from '@/components/ui/alert';

const MENU = [
    {
        grup: 'Utama',
        item: [
            { label: 'Dasbor', href: '/dasbor', hak: 'laporan.view', ikon: LayoutDashboard },
            { label: 'Tahap', href: '/tahap', hak: 'tahap.view', ikon: CalendarRange },
            { label: 'Laporan', href: '/laporan', hak: 'laporan.view', ikon: FileText },
            { label: 'Perbandingan Tahap', href: '/perbandingan', hak: 'laporan.view', ikon: GitCompareArrows },
        ],
    },
    {
        grup: 'Transaksi',
        item: [
            { label: 'Realisasi', href: '/realisasi', hak: 'realisasi.view', ikon: Wallet },
            { label: 'Template Mapping', href: '/mapping-template', hak: 'mapping.view', ikon: ArrowLeftRight },
        ],
    },
    {
        grup: 'Master',
        item: [
            { label: 'SKPD', href: '/skpd', hak: 'skpd.view', ikon: Building2 },
            { label: 'Rekening', href: '/rekening', hak: 'rekening.view', ikon: Receipt },
            { label: 'Jenis Kebutuhan', href: '/jenis-kebutuhan', hak: 'jenis_kebutuhan.view', ikon: ListPlus },
            { label: 'Kebutuhan Tambahan', href: '/jenis-kebutuhan-tambahan', hak: 'kebutuhan_tambahan.view', ikon: CirclePlus },
        ],
    },
    {
        grup: 'Administrasi',
        item: [
            { label: 'Pengguna', href: '/pengguna', hak: 'pengguna.view', ikon: Users },
            { label: 'Peran', href: '/peran', hak: 'peran.manage', ikon: ShieldCheck },
            { label: 'Log Audit', href: '/audit', hak: 'audit.view', ikon: ScrollText },
        ],
    },
];

function judulHalaman(url, bisa) {
    if (url.startsWith('/kata-sandi')) return 'Ubah Kata Sandi';
    for (const g of MENU) {
        const ketemu = g.item.find((i) => bisa(i.hak) && url.startsWith(i.href));
        if (ketemu) return ketemu.label;
    }
    return 'SIPD Anggaran Gaji';
}

export default function Utama({ children }) {
    const { auth, flash } = usePage().props;
    const url = usePage().url;
    const bisa = (kode) => (auth.hak_akses || []).includes(kode);
    const nama = auth.pengguna?.nama || '-';
    const peran = (auth.pengguna?.peran || []).join(', ');
    const inisial = nama
        .split(' ')
        .map((w) => w[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    return (
        <SidebarProvider>
            <TooltipProvider>
            <Sidebar>
                <SidebarHeader>
                    <div className="px-2 py-1">
                        <div className="font-semibold">SIPD Anggaran Gaji</div>
                        <div className="text-xs text-muted-foreground">Perencanaan Anggaran Gaji</div>
                    </div>
                </SidebarHeader>
                <SidebarContent>
                    {MENU.map((g) => {
                        const items = g.item.filter((i) => bisa(i.hak));
                        if (!items.length) return null;
                        return (
                            <SidebarGroup key={g.grup}>
                                <SidebarGroupLabel>{g.grup}</SidebarGroupLabel>
                                <SidebarGroupContent>
                                    <SidebarMenu>
                                        {items.map((i) => (
                                            <SidebarMenuItem key={i.href}>
                                                <SidebarMenuButton
                                                    asChild
                                                    isActive={url.startsWith(i.href)}
                                                    tooltip={i.label}
                                                >
                                                    <Link href={i.href}>
                                                        <i.ikon />
                                                        <span>{i.label}</span>
                                                    </Link>
                                                </SidebarMenuButton>
                                            </SidebarMenuItem>
                                        ))}
                                    </SidebarMenu>
                                </SidebarGroupContent>
                            </SidebarGroup>
                        );
                    })}
                </SidebarContent>
                <SidebarFooter>
                    <div className="px-2 py-1 text-xs text-muted-foreground">
                        {nama}
                        {peran ? ` (${peran})` : ''}
                    </div>
                </SidebarFooter>
                <SidebarRail />
            </Sidebar>
            <SidebarInset>
                <header className="flex h-16 shrink-0 items-center gap-2 border-b px-4">
                    <SidebarTrigger />
                    <Separator orientation="vertical" className="mr-2 h-4" />
                    <h1 className="text-base font-semibold">{judulHalaman(url, bisa)}</h1>
                    <div className="ml-auto">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className="flex items-center gap-2">
                                    <Avatar className="size-8">
                                        <AvatarFallback>{inisial}</AvatarFallback>
                                    </Avatar>
                                    <span className="hidden text-sm md:block">{nama}</span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                <DropdownMenuLabel>
                                    <div className="flex flex-col gap-0.5">
                                        <span>{nama}</span>
                                        {peran ? (
                                            <span className="text-xs font-normal text-muted-foreground">{peran}</span>
                                        ) : null}
                                    </div>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuGroup>
                                    <DropdownMenuItem asChild>
                                        <Link href="/kata-sandi">Ubah Kata Sandi</Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem onSelect={() => router.post('/keluar')}>
                                        Keluar
                                    </DropdownMenuItem>
                                </DropdownMenuGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </header>
                <div className="flex flex-1 flex-col p-4 md:p-6">
                    <div className="mx-auto flex w-full max-w-7xl flex-col gap-4">
                        {flash.sukses && (
                            <Alert>
                                <AlertDescription>{flash.sukses}</AlertDescription>
                            </Alert>
                        )}
                        {flash.galat && (
                            <Alert variant="destructive">
                                <AlertDescription>{flash.galat}</AlertDescription>
                            </Alert>
                        )}
                        {children}
                    </div>
                </div>
            </SidebarInset>
            </TooltipProvider>
        </SidebarProvider>
    );
}
