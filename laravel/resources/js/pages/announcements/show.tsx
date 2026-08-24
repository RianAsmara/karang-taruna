import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface AnnouncementShowProps {
    announcement: {
        id: string;
        title: string;
        body: string;
        publishedAt: string | null;
        authorName: string;
    };
    canManage: boolean;
}

export default function AnnouncementShow({ announcement, canManage }: AnnouncementShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Pengumuman', href: '/announcements' },
        { title: announcement.title, href: route('announcements.show', announcement.id) },
    ];

    const destroy = () => {
        router.delete(route('announcements.destroy', announcement.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={announcement.title} />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">{announcement.title}</h1>
                        <p className="text-muted-foreground text-sm">
                            {announcement.publishedAt ? formatDateTime(announcement.publishedAt) : 'Draf'} · {announcement.authorName}
                        </p>
                    </div>
                    {canManage && (
                        <div className="flex gap-2">
                            <Button variant="outline" asChild>
                                <Link href={route('announcements.edit', announcement.id)}>Ubah</Link>
                            </Button>
                            <Button variant="destructive" onClick={destroy}>
                                Hapus
                            </Button>
                        </div>
                    )}
                </div>

                <p className="max-w-2xl text-sm whitespace-pre-line">{announcement.body}</p>
            </div>
        </AppLayout>
    );
}
