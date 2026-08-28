import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export type OrganizationRole = 'KETUA' | 'BENDAHARA' | 'SEKRETARIS' | 'ANGGOTA';

export interface CurrentOrganization {
    id: string;
    name: string;
    slug: string;
    role: OrganizationRole;
    roleLabel: string;
}

export interface OrganizationThemeColors {
    accent: string;
    accent200: string;
    accent700: string;
    accent800: string;
    onAccent: string;
    onInk: string;
}

export interface OrganizationTheme {
    version: number;
    updatedAt: string;
    name: string;
    primary: string;
    logo: { mark: string; icon: string; mono: string };
    color: { light: OrganizationThemeColors; dark: OrganizationThemeColors };
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    currentOrganization: CurrentOrganization | null;
    unreadNotificationsCount: number;
    organizationTheme: OrganizationTheme | null;
    [key: string]: unknown;
}

export interface User {
    id: string;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    is_superadmin?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
