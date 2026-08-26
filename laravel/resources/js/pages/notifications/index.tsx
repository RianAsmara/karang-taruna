import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, formatRupiah } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Notifikasi', href: '/notifications' }];

interface NotificationItem {
    id: string;
    type: string;
    data: Record<string, string | number | null>;
    readAt: string | null;
    createdAt: string;
}

interface NotificationsIndexProps {
    notifications: NotificationItem[];
}

const STATUS_LABEL: Record<string, string> = {
    APPROVED: 'disetujui',
    REJECTED: 'ditolak',
};

function describe(notification: NotificationItem): { text: string; href: string | null } {
    const { type, data } = notification;

    switch (type) {
        case 'TransactionSubmittedForReview':
            return {
                text: `Transaksi ${formatRupiah(Number(data.amount))} menunggu persetujuan Anda.`,
                href: route('finance.transactions.show', String(data.transaction_id)),
            };
        case 'TransactionReviewed':
            return {
                text: `Transaksi ${formatRupiah(Number(data.amount))} ${STATUS_LABEL[String(data.status)] ?? String(data.status).toLowerCase()} oleh ${data.reviewer_name}.`,
                href: route('finance.transactions.show', String(data.transaction_id)),
            };
        case 'FinancialReportPublished':
            return {
                text: `Laporan "${data.title}" telah dipublikasikan.`,
                href: route('reports.show', String(data.report_id)),
            };
        case 'MemberDueReminder':
            return {
                text: `Iuran Anda sebesar ${formatRupiah(Number(data.amount_outstanding))} belum dibayar.`,
                href: route('finance.dues.index'),
            };
        default:
            return { text: type, href: null };
    }
}

export default function NotificationsIndex({ notifications }: NotificationsIndexProps) {
    const markAsRead = (id: string) => router.post(route('notifications.read', id), {}, { preserveScroll: true });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notifikasi" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Notifikasi</h1>

                {notifications.length === 0 ? (
                    <p className="text-muted-foreground text-sm">Belum ada notifikasi.</p>
                ) : (
                    <div className="grid gap-3">
                        {notifications.map((notification) => {
                            const { text, href } = describe(notification);

                            return (
                                <div
                                    key={notification.id}
                                    className="border-sidebar-border/70 dark:border-sidebar-border flex items-start justify-between gap-3 rounded-xl border p-4"
                                >
                                    <div>
                                        {href ? (
                                            <Link href={href} className="font-medium hover:underline">
                                                {text}
                                            </Link>
                                        ) : (
                                            <p className="font-medium">{text}</p>
                                        )}
                                        <p className="text-muted-foreground text-sm">{formatDateTime(notification.createdAt)}</p>
                                    </div>
                                    {!notification.readAt && (
                                        <Button variant="ghost" size="sm" onClick={() => markAsRead(notification.id)}>
                                            Tandai dibaca
                                        </Button>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
