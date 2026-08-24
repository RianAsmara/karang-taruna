import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatRupiah } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kas', href: '/finance/accounts' },
    { title: 'Laporan', href: '/finance/reports' },
];

interface ReportListItem {
    id: string;
    title: string;
    reportType: string;
    periodStart: string;
    periodEnd: string;
    status: string;
    statusLabel: string;
    visibilityLabel: string;
    closingBalance: number;
}

interface ReportsIndexProps {
    reports: ReportListItem[];
    canCreate: boolean;
}

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'outline'> = {
    DRAFT: 'outline',
    PUBLISHED: 'default',
    ARCHIVED: 'secondary',
};

export default function ReportsIndex({ reports, canCreate }: ReportsIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laporan Keuangan" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Laporan Keuangan</h1>
                    {canCreate && (
                        <Button asChild>
                            <Link href={route('finance.reports.create')}>Buat Laporan</Link>
                        </Button>
                    )}
                </div>

                {reports.length === 0 ? (
                    <p className="text-muted-foreground text-sm">Belum ada laporan.</p>
                ) : (
                    <div className="grid gap-3">
                        {reports.map((report) => (
                            <Link
                                key={report.id}
                                href={route('reports.show', report.id)}
                                className="border-sidebar-border/70 dark:border-sidebar-border hover:bg-muted/50 flex items-center justify-between rounded-xl border p-4 transition-colors"
                            >
                                <div>
                                    <p className="font-medium">{report.title}</p>
                                    <p className="text-muted-foreground text-sm">
                                        {formatDate(report.periodStart)} – {formatDate(report.periodEnd)} · {report.reportType} ·{' '}
                                        {report.visibilityLabel}
                                    </p>
                                </div>
                                <div className="flex items-center gap-3">
                                    <span className="font-medium">{formatRupiah(report.closingBalance)}</span>
                                    <Badge variant={STATUS_VARIANT[report.status] ?? 'outline'}>{report.statusLabel}</Badge>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
