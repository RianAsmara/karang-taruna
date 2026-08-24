import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatDateTime, formatRupiah } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface ReportShowProps {
    report: {
        id: string;
        title: string;
        reportTypeLabel: string;
        periodStart: string;
        periodEnd: string;
        status: string;
        statusLabel: string;
        visibility: string;
        visibilityLabel: string;
        openingBalance: number;
        totalIncome: number;
        totalExpense: number;
        closingBalance: number;
        publishedAt: string | null;
        publisherName: string | null;
        revisionCount: number;
        organizationName: string;
    };
    canPublish: boolean;
    canArchive: boolean;
    canRevise: boolean;
    canDelete: boolean;
    shareUrl: string;
}

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'outline'> = {
    DRAFT: 'outline',
    PUBLISHED: 'default',
    ARCHIVED: 'secondary',
};

function whatsappMessage(report: ReportShowProps['report'], shareUrl: string): string {
    return [
        `📊 ${report.title.toUpperCase()}`,
        `Organisasi: ${report.organizationName}`,
        `Periode: ${formatDate(report.periodStart)} – ${formatDate(report.periodEnd)}`,
        '',
        `Saldo awal:`,
        formatRupiah(report.openingBalance),
        '',
        `Pemasukan:`,
        `+${formatRupiah(report.totalIncome)}`,
        '',
        `Pengeluaran:`,
        `-${formatRupiah(report.totalExpense)}`,
        '',
        `Saldo akhir:`,
        formatRupiah(report.closingBalance),
        '',
        `Detail:`,
        shareUrl,
        '',
        `Laporan ini dibuat otomatis oleh RukunMuda.`,
    ].join('\n');
}

function ReportBody({ report, canPublish, canArchive, canRevise, canDelete, shareUrl }: ReportShowProps) {
    const [copied, setCopied] = useState(false);

    const publish = () => router.post(route('reports.publish', report.id));
    const archive = () => router.post(route('reports.archive', report.id));
    const revise = () => router.post(route('reports.revise', report.id));
    const destroy = () => router.delete(route('reports.destroy', report.id));

    const logShare = (channel: 'WHATSAPP' | 'WEB') => {
        router.post(route('reports.share', report.id), { channel }, { preserveScroll: true, preserveState: true });
    };

    const shareToWhatsapp = () => {
        logShare('WHATSAPP');
        window.open(`https://wa.me/?text=${encodeURIComponent(whatsappMessage(report, shareUrl))}`, '_blank');
    };

    const copyLink = async () => {
        logShare('WEB');
        try {
            await navigator.clipboard.writeText(shareUrl);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
            // Clipboard API unavailable — the link is still visible below to copy by hand.
        }
    };

    return (
        <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div className="flex items-start justify-between">
                <div>
                    <div className="flex items-center gap-2">
                        <h1 className="text-xl font-semibold">{report.title}</h1>
                        <Badge variant={STATUS_VARIANT[report.status] ?? 'outline'}>{report.statusLabel}</Badge>
                    </div>
                    <p className="text-muted-foreground text-sm">
                        {report.organizationName} · {formatDate(report.periodStart)} – {formatDate(report.periodEnd)} · {report.reportTypeLabel} ·{' '}
                        {report.visibilityLabel}
                    </p>
                    {report.publishedAt && (
                        <p className="text-muted-foreground text-sm">
                            Diterbitkan {formatDateTime(report.publishedAt)}
                            {report.publisherName && ` oleh ${report.publisherName}`}
                            {report.revisionCount > 0 && ` · ${report.revisionCount} revisi`}
                        </p>
                    )}
                </div>
                <div className="flex flex-wrap gap-2">
                    {canPublish && (
                        <Button onClick={publish} size="sm">
                            Terbitkan
                        </Button>
                    )}
                    {canRevise && (
                        <Button onClick={revise} variant="outline" size="sm">
                            Revisi
                        </Button>
                    )}
                    {canArchive && (
                        <Button onClick={archive} variant="outline" size="sm">
                            Arsipkan
                        </Button>
                    )}
                    {canDelete && (
                        <Button onClick={destroy} variant="destructive" size="sm">
                            Hapus
                        </Button>
                    )}
                </div>
            </div>

            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                    <p className="text-muted-foreground text-xs">Saldo awal</p>
                    <p className="font-medium">{formatRupiah(report.openingBalance)}</p>
                </div>
                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                    <p className="text-muted-foreground text-xs">Pemasukan</p>
                    <p className="font-medium text-emerald-600 dark:text-emerald-400">+{formatRupiah(report.totalIncome)}</p>
                </div>
                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                    <p className="text-muted-foreground text-xs">Pengeluaran</p>
                    <p className="text-destructive font-medium">-{formatRupiah(report.totalExpense)}</p>
                </div>
                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                    <p className="text-muted-foreground text-xs">Saldo akhir</p>
                    <p className="font-medium">{formatRupiah(report.closingBalance)}</p>
                </div>
            </div>

            {report.status === 'PUBLISHED' && (
                <div className="border-sidebar-border/70 dark:border-sidebar-border flex flex-wrap items-center gap-4 rounded-xl border p-4">
                    <img
                        src={route('reports.qr', report.id)}
                        alt="QR kode laporan"
                        width={120}
                        height={120}
                        className="rounded-md bg-white p-1"
                    />
                    <div className="flex flex-col gap-2">
                        <p className="text-muted-foreground text-sm">Bagikan laporan ini</p>
                        <div className="flex flex-wrap gap-2">
                            <Button size="sm" onClick={shareToWhatsapp}>
                                Bagikan ke WhatsApp
                            </Button>
                            <Button size="sm" variant="outline" onClick={copyLink}>
                                {copied ? 'Tautan disalin!' : 'Salin tautan'}
                            </Button>
                        </div>
                        <code className="text-muted-foreground text-xs break-all">{shareUrl}</code>
                    </div>
                </div>
            )}
        </div>
    );
}

export default function ReportShow(props: ReportShowProps) {
    const { auth } = usePage<SharedData>().props;

    if (!auth.user) {
        return (
            <>
                <Head title={props.report.title} />
                <div className="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                    <header className="mx-auto flex max-w-3xl items-center justify-between p-6">
                        <span className="text-sm font-medium">RukunMuda</span>
                        <Link
                            href={route('login')}
                            className="rounded-sm border border-[#19140035] px-4 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                        >
                            Masuk
                        </Link>
                    </header>
                    <main className="mx-auto max-w-3xl px-6 pb-16">
                        <ReportBody {...props} />
                    </main>
                </div>
            </>
        );
    }

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kas', href: '/finance/accounts' },
        { title: 'Laporan', href: '/finance/reports' },
        { title: props.report.title, href: route('reports.show', props.report.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={props.report.title} />
            <ReportBody {...props} />
        </AppLayout>
    );
}
