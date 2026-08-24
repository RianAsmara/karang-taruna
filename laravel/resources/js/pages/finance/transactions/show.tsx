import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatDateTime, formatRupiah } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface TransactionShowProps {
    transaction: {
        id: string;
        amount: number;
        transactionType: string;
        transactionTypeLabel: string;
        status: string;
        statusLabel: string;
        description: string | null;
        transactionDate: string;
        accountName: string;
        relatedAccountName: string | null;
        categoryName: string | null;
        eventTitle: string | null;
        creatorName: string;
        reviewerName: string | null;
        reviewedAt: string | null;
    };
    canEdit: boolean;
    canDelete: boolean;
    canSubmit: boolean;
    canReview: boolean;
}

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    DRAFT: 'outline',
    PENDING: 'secondary',
    APPROVED: 'default',
    REJECTED: 'destructive',
};

export default function TransactionShow({ transaction, canEdit, canDelete, canSubmit, canReview }: TransactionShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kas', href: '/finance/accounts' },
        { title: 'Riwayat Transaksi', href: '/finance/transactions' },
        { title: transaction.description ?? transaction.transactionTypeLabel, href: route('finance.transactions.show', transaction.id) },
    ];

    const submit = () => router.post(route('finance.transactions.submit', transaction.id));
    const approve = () => router.post(route('finance.transactions.approve', transaction.id));
    const reject = () => router.post(route('finance.transactions.reject', transaction.id));
    const destroy = () => router.delete(route('finance.transactions.destroy', transaction.id));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={transaction.description ?? transaction.transactionTypeLabel} />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-start justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-xl font-semibold">
                                {transaction.transactionType === 'EXPENSE' ? '-' : '+'}
                                {formatRupiah(transaction.amount)}
                            </h1>
                            <Badge variant={STATUS_VARIANT[transaction.status] ?? 'outline'}>{transaction.statusLabel}</Badge>
                        </div>
                        <p className="text-muted-foreground text-sm">{formatDate(transaction.transactionDate)}</p>
                    </div>
                    <div className="flex gap-2">
                        {canSubmit && (
                            <Button onClick={submit} size="sm">
                                Ajukan
                            </Button>
                        )}
                        {canReview && (
                            <>
                                <Button onClick={approve} size="sm">
                                    Setujui
                                </Button>
                                <Button onClick={reject} variant="destructive" size="sm">
                                    Tolak
                                </Button>
                            </>
                        )}
                        {canEdit && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('finance.transactions.edit', transaction.id)}>Ubah</Link>
                            </Button>
                        )}
                        {canDelete && (
                            <Button variant="destructive" size="sm" onClick={destroy}>
                                Hapus
                            </Button>
                        )}
                    </div>
                </div>

                <dl className="grid max-w-xl grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt className="text-muted-foreground">Jenis</dt>
                    <dd>{transaction.transactionTypeLabel}</dd>

                    <dt className="text-muted-foreground">Kas</dt>
                    <dd>{transaction.accountName}</dd>

                    {transaction.relatedAccountName && (
                        <>
                            <dt className="text-muted-foreground">Kas tujuan</dt>
                            <dd>{transaction.relatedAccountName}</dd>
                        </>
                    )}

                    {transaction.categoryName && (
                        <>
                            <dt className="text-muted-foreground">Kategori</dt>
                            <dd>{transaction.categoryName}</dd>
                        </>
                    )}

                    {transaction.eventTitle && (
                        <>
                            <dt className="text-muted-foreground">Kegiatan</dt>
                            <dd>{transaction.eventTitle}</dd>
                        </>
                    )}

                    {transaction.description && (
                        <>
                            <dt className="text-muted-foreground">Keterangan</dt>
                            <dd>{transaction.description}</dd>
                        </>
                    )}

                    <dt className="text-muted-foreground">Dibuat oleh</dt>
                    <dd>{transaction.creatorName}</dd>

                    {transaction.reviewerName && transaction.reviewedAt && (
                        <>
                            <dt className="text-muted-foreground">Ditinjau oleh</dt>
                            <dd>
                                {transaction.reviewerName} · {formatDateTime(transaction.reviewedAt)}
                            </dd>
                        </>
                    )}
                </dl>
            </div>
        </AppLayout>
    );
}
