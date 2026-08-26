import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatDateTime, formatRupiah } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useRef } from 'react';

interface TransactionAttachment {
    id: string;
    originalName: string;
    mimeType: string;
    sizeBytes: number;
    uploaderName: string;
    uploadedAt: string;
    downloadUrl: string;
}

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
        attachments: TransactionAttachment[];
    };
    canEdit: boolean;
    canDelete: boolean;
    canSubmit: boolean;
    canReview: boolean;
    canManageEvidence: boolean;
}

function formatFileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    DRAFT: 'outline',
    PENDING: 'secondary',
    APPROVED: 'default',
    REJECTED: 'destructive',
};

export default function TransactionShow({ transaction, canEdit, canDelete, canSubmit, canReview, canManageEvidence }: TransactionShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kas', href: '/finance/accounts' },
        { title: 'Riwayat Transaksi', href: '/finance/transactions' },
        { title: transaction.description ?? transaction.transactionTypeLabel, href: route('finance.transactions.show', transaction.id) },
    ];

    const submit = () => router.post(route('finance.transactions.submit', transaction.id));
    const approve = () => router.post(route('finance.transactions.approve', transaction.id));
    const reject = () => router.post(route('finance.transactions.reject', transaction.id));
    const destroy = () => router.delete(route('finance.transactions.destroy', transaction.id));

    const fileInputRef = useRef<HTMLInputElement>(null);
    const evidenceForm = useForm<{ file: File | null }>({ file: null });

    const uploadEvidence = () => {
        if (!evidenceForm.data.file) return;

        evidenceForm.post(route('finance.transactions.attachments.store', transaction.id), {
            forceFormData: true,
            onSuccess: () => {
                evidenceForm.reset('file');
                if (fileInputRef.current) fileInputRef.current.value = '';
            },
        });
    };

    const deleteAttachment = (attachmentId: string) => {
        router.delete(route('finance.transactions.attachments.destroy', [transaction.id, attachmentId]));
    };

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

                <div className="max-w-xl space-y-3">
                    <h2 className="text-sm font-semibold">Bukti Transaksi</h2>

                    {transaction.attachments.length > 0 ? (
                        <ul className="space-y-2">
                            {transaction.attachments.map((attachment) => (
                                <li key={attachment.id} className="flex items-center justify-between gap-2 rounded-md border p-2 text-sm">
                                    <div className="min-w-0">
                                        <a href={attachment.downloadUrl} className="block truncate font-medium hover:underline">
                                            {attachment.originalName}
                                        </a>
                                        <p className="text-muted-foreground text-xs">
                                            {formatFileSize(attachment.sizeBytes)} · {attachment.uploaderName} ·{' '}
                                            {formatDateTime(attachment.uploadedAt)}
                                        </p>
                                    </div>
                                    {canManageEvidence && (
                                        <Button variant="ghost" size="sm" onClick={() => deleteAttachment(attachment.id)}>
                                            Hapus
                                        </Button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-muted-foreground text-sm">Belum ada bukti yang diunggah.</p>
                    )}

                    {canManageEvidence && (
                        <div className="flex items-center gap-2">
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept=".jpg,.jpeg,.png,.webp,.pdf"
                                onChange={(e) => evidenceForm.setData('file', e.target.files?.[0] ?? null)}
                                className="text-sm"
                            />
                            <Button size="sm" onClick={uploadEvidence} disabled={!evidenceForm.data.file || evidenceForm.processing}>
                                Unggah
                            </Button>
                        </div>
                    )}
                    {evidenceForm.errors.file && <p className="text-destructive text-xs">{evidenceForm.errors.file}</p>}
                </div>
            </div>
        </AppLayout>
    );
}
