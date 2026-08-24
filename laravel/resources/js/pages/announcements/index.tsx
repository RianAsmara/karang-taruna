import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pengumuman', href: '/announcements' }];

interface AnnouncementListItem {
    id: string;
    title: string;
    publishedAt: string | null;
}

interface AnnouncementsIndexProps {
    announcements: AnnouncementListItem[];
    canCreate: boolean;
}

export default function AnnouncementsIndex({ announcements, canCreate }: AnnouncementsIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pengumuman" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Pengumuman</h1>
                    {canCreate && (
                        <Button asChild>
                            <Link href={route('announcements.create')}>Buat Pengumuman</Link>
                        </Button>
                    )}
                </div>

                {announcements.length === 0 ? (
                    <p className="text-muted-foreground text-sm">Belum ada pengumuman.</p>
                ) : (
                    <div className="grid gap-3">
                        {announcements.map((announcement) => (
                            <Link
                                key={announcement.id}
                                href={route('announcements.show', announcement.id)}
                                className="border-sidebar-border/70 dark:border-sidebar-border hover:bg-muted/50 rounded-xl border p-4 transition-colors"
                            >
                                <p className="font-medium">{announcement.title}</p>
                                <p className="text-muted-foreground text-sm">
                                    {announcement.publishedAt ? formatDateTime(announcement.publishedAt) : 'Draf'}
                                </p>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
