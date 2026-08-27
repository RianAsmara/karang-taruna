import AppLayout from '@/layouts/app-layout';
import { formatDate, formatRupiah } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface OrganizationSummary {
    id: string;
    name: string;
    slug: string;
    memberCount: number;
    requireTransactionApproval: boolean;
    publicTransparencyEnabled: boolean;
    createdAt: string;
}

interface TransparencySummary {
    balance: number;
    monthIncome: number;
    monthExpense: number;
    monthSurplus: number;
}

interface Member {
    id: string;
    name: string;
    email: string;
    phone: string | null;
    role: string;
    roleLabel: string;
    joinedAt: string;
    isChair: boolean;
    leftAt: string | null;
}

interface Report {
    id: string;
    title: string;
    reportTypeLabel: string;
    periodStart: string;
    periodEnd: string;
    statusLabel: string;
    visibilityLabel: string;
    closingBalance: number;
    publishedAt: string | null;
}

interface SuperadminOrganizationShowProps {
    organization: OrganizationSummary;
    summary: TransparencySummary;
    members: Member[];
    reports: Report[];
}

export default function SuperadminOrganizationShow({ organization, summary, members, reports }: SuperadminOrganizationShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Superadmin', href: '/superadmin/organizations' },
        { title: organization.name, href: route('superadmin.organizations.show', organization.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Superadmin — ${organization.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div>
                    <h1 className="text-xl font-semibold">{organization.name}</h1>
                    <p className="text-muted-foreground text-sm">
                        {organization.slug} · Dilihat sebagai superadmin — kunjungan ini tercatat di audit log.
                    </p>
                </div>

                <div className="grid grid-cols-1 gap-3 sm:grid-cols-4">
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                        <p className="text-muted-foreground text-xs">Saldo</p>
                        <p className="font-medium">{formatRupiah(summary.balance)}</p>
                    </div>
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                        <p className="text-muted-foreground text-xs">Pemasukan bulan ini</p>
                        <p className="font-medium text-emerald-600 dark:text-emerald-400">+{formatRupiah(summary.monthIncome)}</p>
                    </div>
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                        <p className="text-muted-foreground text-xs">Pengeluaran bulan ini</p>
                        <p className="text-destructive font-medium">-{formatRupiah(summary.monthExpense)}</p>
                    </div>
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                        <p className="text-muted-foreground text-xs">Surplus</p>
                        <p className="font-medium">{formatRupiah(summary.monthSurplus)}</p>
                    </div>
                </div>

                <section className="space-y-3">
                    <h2 className="font-semibold">Anggota ({members.length})</h2>
                    <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border">
                        <table className="w-full text-left text-sm">
                            <thead className="text-muted-foreground border-b">
                                <tr>
                                    <th className="px-4 py-2 font-medium">Nama</th>
                                    <th className="px-4 py-2 font-medium">Email</th>
                                    <th className="px-4 py-2 font-medium">Peran</th>
                                    <th className="px-4 py-2 font-medium">Bergabung</th>
                                </tr>
                            </thead>
                            <tbody>
                                {members.map((member) => (
                                    <tr key={member.id} className="border-b last:border-0">
                                        <td className="px-4 py-2">{member.name}</td>
                                        <td className="px-4 py-2">{member.email}</td>
                                        <td className="px-4 py-2">{member.roleLabel}</td>
                                        <td className="px-4 py-2">{formatDate(member.joinedAt)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="space-y-3">
                    <h2 className="font-semibold">Laporan keuangan ({reports.length})</h2>
                    {reports.length === 0 ? (
                        <p className="text-muted-foreground text-sm">Belum ada laporan.</p>
                    ) : (
                        <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border">
                            <table className="w-full text-left text-sm">
                                <thead className="text-muted-foreground border-b">
                                    <tr>
                                        <th className="px-4 py-2 font-medium">Judul</th>
                                        <th className="px-4 py-2 font-medium">Periode</th>
                                        <th className="px-4 py-2 font-medium">Status</th>
                                        <th className="px-4 py-2 font-medium">Visibilitas</th>
                                        <th className="px-4 py-2 font-medium">Saldo akhir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {reports.map((report) => (
                                        <tr key={report.id} className="border-b last:border-0">
                                            {/* Not linked to /reports/{id}: that page enforces the normal
                                                per-member FinancialReportPolicy, which a superadmin (no
                                                membership anywhere) fails for any DRAFT/PRIVATE report —
                                                exactly the ones this table exists to surface. */}
                                            <td className="px-4 py-2 font-medium">{report.title}</td>
                                            <td className="px-4 py-2">
                                                {formatDate(report.periodStart)} – {formatDate(report.periodEnd)}
                                            </td>
                                            <td className="px-4 py-2">{report.statusLabel}</td>
                                            <td className="px-4 py-2">{report.visibilityLabel}</td>
                                            <td className="px-4 py-2">{formatRupiah(report.closingBalance)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
