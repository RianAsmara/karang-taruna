import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Boxes, CalendarDays, FileText, HandCoins, Handshake, LayoutGrid, Megaphone, Palette, ShieldAlert, ShieldCheck, Users, Vote, Wallet } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        url: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Kegiatan',
        url: '/events',
        icon: CalendarDays,
    },
    {
        title: 'Anggota',
        url: '/members',
        icon: Users,
    },
    {
        title: 'Kas',
        url: '/finance/accounts',
        icon: Wallet,
    },
    {
        title: 'Transparansi',
        url: '/transparansi',
        icon: ShieldCheck,
    },
    {
        title: 'Iuran',
        url: '/finance/dues',
        icon: HandCoins,
    },
    {
        title: 'Voting',
        url: '/votes',
        icon: Vote,
    },
    {
        title: 'Inventaris',
        url: '/inventory',
        icon: Boxes,
    },
    {
        title: 'Sponsor',
        url: '/sponsors',
        icon: Handshake,
    },
    {
        title: 'Dokumen',
        url: '/documents',
        icon: FileText,
    },
    {
        title: 'Pengumuman',
        url: '/announcements',
        icon: Megaphone,
    },
];

export function AppSidebar() {
    const { auth, currentOrganization } = usePage<SharedData>().props;

    // Ketua-only entry point — absent for anyone else, not disabled, per
    // docs/design/docs/theme-builder.md § Permissions and audit. Also
    // absent for a superadmin even if they independently hold a real
    // ketua membership somewhere — superadmin stays strictly read-only
    // (matches OrganizationPolicy::manageTheme, the actual boundary).
    let navItems =
        currentOrganization?.role === 'KETUA' && !auth.user.is_superadmin
            ? [...mainNavItems, { title: 'Tema', url: '/organisasi/tema', icon: Palette }]
            : mainNavItems;

    navItems = auth.user.is_superadmin ? [...navItems, { title: 'Superadmin', url: '/superadmin/organizations', icon: ShieldAlert }] : navItems;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
