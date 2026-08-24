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

export type OrganizationRole = 'OWNER' | 'ADMIN' | 'TREASURER' | 'COMMITTEE' | 'MEMBER' | 'RESIDENT';

export interface CurrentOrganization {
    id: string;
    name: string;
    slug: string;
    role: OrganizationRole;
    roleLabel: string;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    currentOrganization: CurrentOrganization | null;
    [key: string]: unknown;
}

export interface User {
    id: string;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
