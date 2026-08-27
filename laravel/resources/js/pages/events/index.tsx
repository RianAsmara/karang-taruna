import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { SearchInput } from '@/components/search-input';
import { formatDateTime } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Kegiatan', href: '/events' }];

interface EventListItem {
    id: string;
    title: string;
    startAt: string;
    status: string;
    statusLabel: string;
}

interface EventsIndexProps {
    events: EventListItem[];
    canCreate: boolean;
    filters: { search: string | null };
}

export default function EventsIndex({ events, canCreate, filters }: EventsIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kegiatan" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Kegiatan</h1>
                    {canCreate && (
                        <Button asChild>
                            <Link href={route('events.create')}>Buat Kegiatan</Link>
                        </Button>
                    )}
                </div>

                <SearchInput initialValue={filters.search} placeholder="Cari kegiatan…" />

                {events.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        {filters.search ? 'Tidak ada kegiatan yang cocok.' : 'Belum ada kegiatan.'}
                    </p>
                ) : (
                    <div className="grid gap-3">
                        {events.map((event) => (
                            <Link
                                key={event.id}
                                href={route('events.show', event.id)}
                                className="border-sidebar-border/70 dark:border-sidebar-border hover:bg-muted/50 flex items-center justify-between rounded-xl border p-4 transition-colors"
                            >
                                <div>
                                    <p className="font-medium">{event.title}</p>
                                    <p className="text-muted-foreground text-sm">{formatDateTime(event.startAt)}</p>
                                </div>
                                <Badge variant="secondary">{event.statusLabel}</Badge>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
