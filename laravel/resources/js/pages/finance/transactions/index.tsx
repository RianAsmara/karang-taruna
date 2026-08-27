import { SearchInput } from '@/components/search-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatRupiah } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kas', href: '/finance/accounts' },
    { title: 'Riwayat Transaksi', href: '/finance/transactions' },
];

interface TransactionListItem {
    id: string;
    amount: number;
    transactionType: string;
    transactionTypeLabel: string;
    status: string;
    statusLabel: string;
    description: string | null;
    transactionDate: string;
    accountName: string;
    categoryName: string | null;
    eventTitle: string | null;
}

interface TransactionsIndexProps {
    transactions: TransactionListItem[];
    isTreasurer: boolean;
    canCreate: boolean;
    eventId: string | null;
    filters: { search: string | null };
}

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    DRAFT: 'outline',
    PENDING: 'secondary',
    APPROVED: 'default',
    REJECTED: 'destructive',
};

export default function TransactionsIndex({ transactions, isTreasurer, canCreate, eventId, filters }: TransactionsIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Riwayat Transaksi" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Riwayat Transaksi{eventId ? ' Kegiatan' : ''}</h1>
                    {canCreate && (
                        <Button asChild>
                            <Link href={route('finance.transactions.create')}>Buat Transaksi</Link>
                        </Button>
                    )}
                </div>

                {!isTreasurer && (
                    <p className="text-muted-foreground text-sm">Menampilkan transaksi yang telah disetujui.</p>
                )}

                <SearchInput
                    initialValue={filters.search}
                    placeholder="Cari transaksi…"
                    extraParams={eventId ? { event_id: eventId } : undefined}
                />

                {transactions.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        {filters.search ? 'Tidak ada transaksi yang cocok.' : 'Belum ada transaksi.'}
                    </p>
                ) : (
                    <div className="grid gap-3">
                        {transactions.map((transaction) => (
                            <Link
                                key={transaction.id}
                                href={route('finance.transactions.show', transaction.id)}
                                className="border-sidebar-border/70 dark:border-sidebar-border hover:bg-muted/50 flex items-center justify-between rounded-xl border p-4 transition-colors"
                            >
                                <div>
                                    <p className="font-medium">
                                        {transaction.description || transaction.categoryName || transaction.transactionTypeLabel}
                                    </p>
                                    <p className="text-muted-foreground text-sm">
                                        {formatDate(transaction.transactionDate)} · {transaction.accountName}
                                        {transaction.eventTitle && ` · ${transaction.eventTitle}`}
                                    </p>
                                </div>
                                <div className="flex items-center gap-3">
                                    <span
                                        className={
                                            transaction.transactionType === 'EXPENSE' ? 'text-destructive font-medium' : 'font-medium text-emerald-600 dark:text-emerald-400'
                                        }
                                    >
                                        {transaction.transactionType === 'EXPENSE' ? '-' : '+'}
                                        {formatRupiah(transaction.amount)}
                                    </span>
                                    <Badge variant={STATUS_VARIANT[transaction.status] ?? 'outline'}>{transaction.statusLabel}</Badge>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
