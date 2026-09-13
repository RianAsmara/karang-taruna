import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { FlashMessages } from '@/components/flash-messages';
import { OrganizationThemeStyle } from '@/components/organization-theme-style';
import { type BreadcrumbItem } from '@/types';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: { children: React.ReactNode; breadcrumbs?: BreadcrumbItem[] }) {
    return (
        <AppShell variant="sidebar">
            <OrganizationThemeStyle />
            <AppSidebar />
            <AppContent variant="sidebar">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <FlashMessages />
                {children}
            </AppContent>
        </AppShell>
    );
}
