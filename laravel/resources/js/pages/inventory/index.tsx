import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Inventaris', href: '/inventory' }];

interface InventoryListItem {
    id: string;
    name: string;
    category: string;
    categoryLabel: string;
    quantity: number;
    availableQuantity: number;
    condition: string;
    conditionLabel: string;
}

function availabilityLabel(item: InventoryListItem): string {
    if (item.condition === 'RUSAK') return 'Rusak';
    if (item.quantity === 1) return item.availableQuantity > 0 ? 'Tersedia' : 'Dipinjam';
    return `${item.availableQuantity}/${item.quantity} Tersedia`;
}

export default function InventoryIndex({ items, canCreate }: { items: InventoryListItem[]; canCreate: boolean }) {
    const groups = items.reduce<Record<string, { label: string; items: InventoryListItem[] }>>((acc, item) => {
        acc[item.category] ??= { label: item.categoryLabel, items: [] };
        acc[item.category].items.push(item);
        return acc;
    }, {});

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Inventaris" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Inventaris</h1>
                    {canCreate && (
                        <Button asChild>
                            <Link href={route('inventory.create')}>Tambah barang</Link>
                        </Button>
                    )}
                </div>

                {items.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        {canCreate ? 'Belum ada barang.' : 'Belum ada barang yang dicatat.'}
                    </p>
                ) : (
                    Object.entries(groups).map(([category, group]) => (
                        <section key={category} className="space-y-3">
                            <h2 className="text-muted-foreground text-sm font-medium">{group.label}</h2>
                            <div className="grid gap-3">
                                {group.items.map((item) => (
                                    <Link
                                        key={item.id}
                                        href={route('inventory.show', item.id)}
                                        className="border-sidebar-border/70 dark:border-sidebar-border hover:bg-muted/50 flex items-center justify-between rounded-xl border p-4 transition-colors"
                                    >
                                        <p className="font-medium">{item.name}</p>
                                        <Badge variant={item.condition === 'RUSAK' || item.availableQuantity === 0 ? 'secondary' : 'default'}>
                                            {availabilityLabel(item)}
                                        </Badge>
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
