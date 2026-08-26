import { Breadcrumbs } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const { unreadNotificationsCount } = usePage<SharedData>().props;

    return (
        <header className="border-sidebar-border/50 flex h-16 shrink-0 items-center gap-2 border-b px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <Button variant="ghost" size="icon" className="relative ml-auto" asChild>
                <Link href={route('notifications.index')}>
                    <Bell className="size-5" />
                    {unreadNotificationsCount > 0 && (
                        <span className="bg-destructive absolute top-1 right-1 size-2 rounded-full" />
                    )}
                    <span className="sr-only">Notifikasi</span>
                </Link>
            </Button>
        </header>
    );
}
