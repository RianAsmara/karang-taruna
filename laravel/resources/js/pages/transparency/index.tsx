import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatRupiah } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Transparansi', href: '/transparansi' }];

interface RecentTransaction {
    amount: number;
    transactionType: string;
    description: string | null;
    transactionDate: string;
}

interface PublishedReport {
    id: string;
    title: string;
    periodStart: string;
    closingBalance: number;
}

interface TransparencyIndexProps {
    balance: number;
    monthIncome: number;
    monthExpense: number;
    monthSurplus: number;
    recentTransactions: RecentTransaction[];
    publishedReports: PublishedReport[];
    canManageTransparency: boolean;
    publicTransparencyEnabled: boolean;
    publicUrl: string;
}

export default function TransparencyIndex({
    balance,
    monthIncome,
    monthExpense,
    monthSurplus,
    recentTransactions,
    publishedReports,
    canManageTransparency,
    publicTransparencyEnabled,
    publicUrl,
}: TransparencyIndexProps) {
    const togglePublic = () => router.post(route('transparency.toggle'), {}, { preserveScroll: true });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transparansi" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Transparansi Kas</h1>

                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                    <p className="text-muted-foreground text-sm">Saldo</p>
                    <p className="text-2xl font-semibold">{formatRupiah(balance)}</p>
                </div>

                <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                        <p className="text-muted-foreground text-xs">Pemasukan bulan ini</p>
                        <p className="font-medium text-emerald-600 dark:text-emerald-400">+{formatRupiah(monthIncome)}</p>
                    </div>
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                        <p className="text-muted-foreground text-xs">Pengeluaran bulan ini</p>
                        <p className="text-destructive font-medium">-{formatRupiah(monthExpense)}</p>
                    </div>
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                        <p className="text-muted-foreground text-xs">Surplus</p>
                        <p className="font-medium">{formatRupiah(monthSurplus)}</p>
                    </div>
                </div>

                <section className="space-y-3">
                    <h2 className="font-semibold">Transaksi terbaru</h2>
                    {recentTransactions.length === 0 ? (
                        <p className="text-muted-foreground text-sm">Belum ada transaksi disetujui.</p>
                    ) : (
                        <ul className="space-y-1 text-sm">
                            {recentTransactions.map((t, index) => (
                                <li key={index} className="flex items-center justify-between">
                                    <span>
                                        {formatDate(t.transactionDate)} · {t.description}
                                    </span>
                                    <span className={t.transactionType === 'EXPENSE' ? 'text-destructive' : 'text-emerald-600 dark:text-emerald-400'}>
                                        {t.transactionType === 'EXPENSE' ? '-' : '+'}
                                        {formatRupiah(t.amount)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="space-y-3">
                    <h2 className="font-semibold">Laporan diterbitkan</h2>
                    {publishedReports.length === 0 ? (
                        <p className="text-muted-foreground text-sm">Belum ada laporan yang diterbitkan.</p>
                    ) : (
                        <ul className="space-y-2">
                            {publishedReports.map((report) => (
                                <li key={report.id}>
                                    <Link
                                        href={route('reports.show', report.id)}
                                        className="border-sidebar-border/70 dark:border-sidebar-border hover:bg-muted/50 flex items-center justify-between rounded-lg border p-3 text-sm transition-colors"
                                    >
                                        <span>{report.title}</span>
                                        <span className="font-medium">{formatRupiah(report.closingBalance)}</span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {canManageTransparency && (
                    <section className="border-sidebar-border/70 dark:border-sidebar-border space-y-2 rounded-xl border p-4">
                        <h2 className="font-semibold">Halaman transparansi publik</h2>
                        <p className="text-muted-foreground text-sm">
                            {publicTransparencyEnabled
                                ? 'Siapa pun dapat melihat saldo dan laporan publik organisasi tanpa masuk.'
                                : 'Halaman transparansi publik saat ini nonaktif.'}
                        </p>
                        {publicTransparencyEnabled && <code className="text-muted-foreground text-xs break-all">{publicUrl}</code>}
                        <div>
                            <Button size="sm" variant="outline" onClick={togglePublic}>
                                {publicTransparencyEnabled ? 'Nonaktifkan' : 'Aktifkan'}
                            </Button>
                        </div>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
