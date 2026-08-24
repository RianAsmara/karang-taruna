import { formatDate, formatRupiah } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

interface PublicReport {
    id: string;
    title: string;
    periodStart: string;
    periodEnd: string;
    closingBalance: number;
}

interface OrganizationTransparencyProps {
    organization: {
        name: string;
    };
    balance: number;
    totalIncome: number;
    totalExpense: number;
    publicReports: PublicReport[];
}

export default function OrganizationTransparency({ organization, balance, totalIncome, totalExpense, publicReports }: OrganizationTransparencyProps) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title={`Transparansi — ${organization.name}`} />
            <div className="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="mx-auto flex max-w-3xl items-center justify-between p-6">
                    <span className="text-sm font-medium">RukunMuda</span>
                    <Link
                        href={auth.user ? route('dashboard') : route('login')}
                        className="rounded-sm border border-[#19140035] px-4 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                    >
                        {auth.user ? 'Dashboard' : 'Masuk'}
                    </Link>
                </header>

                <main className="mx-auto max-w-3xl px-6 pb-16">
                    <h1 className="text-3xl font-semibold">Transparansi Kas</h1>
                    <p className="text-muted-foreground mt-1 text-sm">{organization.name}</p>

                    <div className="border-sidebar-border/70 dark:border-sidebar-border mt-6 rounded-xl border p-4">
                        <p className="text-muted-foreground text-sm">Saldo</p>
                        <p className="text-2xl font-semibold">{formatRupiah(balance)}</p>
                    </div>

                    <div className="mt-3 grid grid-cols-2 gap-3">
                        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                            <p className="text-muted-foreground text-xs">Total pemasukan</p>
                            <p className="font-medium text-emerald-600 dark:text-emerald-400">+{formatRupiah(totalIncome)}</p>
                        </div>
                        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                            <p className="text-muted-foreground text-xs">Total pengeluaran</p>
                            <p className="text-destructive font-medium">-{formatRupiah(totalExpense)}</p>
                        </div>
                    </div>

                    <section className="mt-10">
                        <h2 className="text-lg font-semibold">Laporan publik</h2>
                        {publicReports.length === 0 ? (
                            <p className="text-muted-foreground mt-2 text-sm">Belum ada laporan publik yang diterbitkan.</p>
                        ) : (
                            <ul className="mt-3 space-y-3">
                                {publicReports.map((report) => (
                                    <li key={report.id}>
                                        <Link
                                            href={route('reports.show', report.id)}
                                            className="border-sidebar-border/70 dark:border-sidebar-border hover:bg-muted/50 flex items-center justify-between rounded-xl border p-4 transition-colors"
                                        >
                                            <div>
                                                <p className="font-medium">{report.title}</p>
                                                <p className="text-muted-foreground text-sm">
                                                    {formatDate(report.periodStart)} – {formatDate(report.periodEnd)}
                                                </p>
                                            </div>
                                            <span className="font-medium">{formatRupiah(report.closingBalance)}</span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </main>
            </div>
        </>
    );
}
