import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Sponsor', href: '/sponsors' }];

interface SponsorContributionItem {
    id: string;
    sponsor: { id: string; name: string };
    type: string;
    typeLabel: string;
    status: string;
    statusLabel: string;
    amount: number | null;
    event: { id: string; title: string } | null;
}

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    DIAJUKAN: 'outline',
    SETUJU: 'secondary',
    DITERIMA: 'default',
    BATAL: 'destructive',
};

export default function SponsorsIndex({
    contributions,
    totalSupport,
    canCreate,
}: {
    contributions: SponsorContributionItem[];
    totalSupport: number;
    canCreate: boolean;
}) {
    const groups = contributions.reduce<Record<string, { label: string; items: SponsorContributionItem[] }>>((acc, contribution) => {
        const key = contribution.event?.id ?? 'none';
        acc[key] ??= { label: contribution.event?.title ?? 'Tanpa kegiatan', items: [] };
        acc[key].items.push(contribution);
        return acc;
    }, {});
    // "Tanpa kegiatan" always sorts last, per mobile-screens.md §23.
    const entries = Object.entries(groups).sort(([a], [b]) => (a === 'none' ? 1 : b === 'none' ? -1 : 0));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Sponsor" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Sponsor</h1>
                        <p className="text-muted-foreground text-sm">Total dukungan · {formatRupiah(totalSupport)}</p>
                    </div>
                    {canCreate && (
                        <Button asChild>
                            <Link href={route('sponsors.create')}>Tambah sponsor</Link>
                        </Button>
                    )}
                </div>

                {contributions.length === 0 ? (
                    <p className="text-muted-foreground text-sm">Belum ada sponsor.</p>
                ) : (
                    entries.map(([key, group]) => (
                        <section key={key} className="space-y-3">
                            <h2 className="text-muted-foreground text-sm font-medium">{group.label}</h2>
                            <div className="grid gap-3">
                                {group.items.map((contribution) => (
                                    <Link
                                        key={contribution.id}
                                        href={route('sponsors.show', contribution.id)}
                                        className="border-sidebar-border/70 dark:border-sidebar-border hover:bg-muted/50 flex items-center justify-between rounded-xl border p-4 transition-colors"
                                    >
                                        <div>
                                            <p className="font-medium">{contribution.sponsor.name}</p>
                                            <p className="text-muted-foreground text-sm">{contribution.typeLabel}</p>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <span className="font-medium">
                                                {contribution.amount !== null ? formatRupiah(contribution.amount) : '—'}
                                            </span>
                                            <Badge variant={STATUS_VARIANT[contribution.status] ?? 'outline'}>{contribution.statusLabel}</Badge>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </section>
                    ))
                )}
            </div>
        </AppLayout>
    );
}
