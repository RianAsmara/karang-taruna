import { CreateOrganizationForm } from '@/components/create-organization-form';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatRupiah } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface RecentTransaction {
    amount: number;
    transactionType: string;
    description: string | null;
    transactionDate: string;
}

interface DashboardSummary {
    balance: number;
    monthIncome: number;
    monthExpense: number;
    monthSurplus: number;
    recentTransactions: RecentTransaction[];
}

interface NextEvent {
    id: string;
    title: string;
    startAt: string;
    location: string | null;
}

interface MyTask {
    id: string;
    title: string;
    statusLabel: string;
    dueDate: string | null;
    eventTitle: string;
}

interface MyDue {
    period: string;
    amountOutstanding: number;
    isAwaitingConfirmation: boolean;
}

interface DashboardAnnouncement {
    id: string;
    title: string;
    publishedAt: string;
}

interface OrganizationDashboardProps {
    summary: DashboardSummary;
    nextEvent: NextEvent | null;
    myTasks: MyTask[];
    myDue: MyDue | null;
    announcement: DashboardAnnouncement | null;
}

function OrganizationDashboard({ summary, nextEvent, myTasks, myDue, announcement }: OrganizationDashboardProps) {
    const { currentOrganization } = usePage<SharedData>().props;

    if (!currentOrganization) {
        return null;
    }

    return (
        <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div>
                <p className="text-muted-foreground text-sm">{currentOrganization.name}</p>
                <h1 className="text-xl font-semibold">Halo, {currentOrganization.roleLabel}</h1>
            </div>

            <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                <p className="text-muted-foreground text-sm">Saldo kas</p>
                <p className="text-2xl font-semibold">{formatRupiah(summary.balance)}</p>
                <div className="mt-3 flex gap-6 text-sm">
                    <span className="text-emerald-600 dark:text-emerald-400">+{formatRupiah(summary.monthIncome)} masuk</span>
                    <span className="text-destructive">-{formatRupiah(summary.monthExpense)} keluar</span>
                </div>
            </div>

            {myDue && (
                <section className="border-sidebar-border/70 dark:border-sidebar-border space-y-1 rounded-xl border p-4">
                    <h2 className="font-semibold">Perlu tindakan</h2>
                    <p className="text-sm">
                        {myDue.isAwaitingConfirmation
                            ? `Iuran bulan ini menunggu konfirmasi bendahara (${formatRupiah(myDue.amountOutstanding)}).`
                            : `Iuran bulan ini belum dibayar — ${formatRupiah(myDue.amountOutstanding)}.`}
                    </p>
                </section>
            )}

            <section className="space-y-3">
                <h2 className="font-semibold">Kegiatan terdekat</h2>
                {nextEvent ? (
                    <Link
                        href={route('events.show', nextEvent.id)}
                        className="border-sidebar-border/70 dark:border-sidebar-border hover:bg-muted/50 block rounded-lg border p-3 text-sm transition-colors"
                    >
                        <p className="font-medium">{nextEvent.title}</p>
                        <p className="text-muted-foreground">
                            {formatDate(nextEvent.startAt)}
                            {nextEvent.location ? ` · ${nextEvent.location}` : ''}
                        </p>
                    </Link>
                ) : (
                    <p className="text-muted-foreground text-sm">Belum ada kegiatan mendatang.</p>
                )}
            </section>

            <section className="space-y-3">
                <h2 className="font-semibold">Tugas saya</h2>
                {myTasks.length === 0 ? (
                    <p className="text-muted-foreground text-sm">Tidak ada tugas untuk Anda saat ini.</p>
                ) : (
                    <ul className="space-y-2">
                        {myTasks.map((task) => (
                            <li key={task.id} className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3 text-sm">
                                <div className="flex items-center justify-between">
                                    <span className="font-medium">{task.title}</span>
                                    <span className="text-muted-foreground">{task.statusLabel}</span>
                                </div>
                                <p className="text-muted-foreground">
                                    {task.eventTitle}
                                    {task.dueDate ? ` · jatuh tempo ${formatDate(task.dueDate)}` : ''}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <section className="space-y-3">
                <h2 className="font-semibold">Pengumuman</h2>
                {announcement ? (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3 text-sm">
                        <p className="font-medium">{announcement.title}</p>
                        <p className="text-muted-foreground">{formatDate(announcement.publishedAt)}</p>
                    </div>
                ) : (
                    <p className="text-muted-foreground text-sm">Belum ada pengumuman.</p>
                )}
            </section>

            <section className="space-y-3">
                <div className="flex items-center justify-between">
                    <h2 className="font-semibold">Aktivitas terbaru</h2>
                    <Link href={route('transparency.index')} className="text-muted-foreground text-sm underline">
                        Lihat kas
                    </Link>
                </div>
                {summary.recentTransactions.length === 0 ? (
                    <p className="text-muted-foreground text-sm">Belum ada transaksi disetujui.</p>
                ) : (
                    <ul className="space-y-1 text-sm">
                        {summary.recentTransactions.map((t, index) => (
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
        </div>
    );
}

export default function Dashboard(props: Partial<OrganizationDashboardProps>) {
    const { currentOrganization } = usePage<SharedData>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            {currentOrganization && props.summary ? (
                <OrganizationDashboard
                    summary={props.summary}
                    nextEvent={props.nextEvent ?? null}
                    myTasks={props.myTasks ?? []}
                    myDue={props.myDue ?? null}
                    announcement={props.announcement ?? null}
                />
            ) : (
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                    <CreateOrganizationForm
                        title="Buat organisasi"
                        body="Anda belum tergabung di organisasi manapun. Buat organisasi untuk Karang Taruna, Pemuda Kampung, atau komunitas Anda."
                    />
                </div>
            )}
        </AppLayout>
    );
}
