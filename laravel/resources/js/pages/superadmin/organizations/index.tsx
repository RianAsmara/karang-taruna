import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Superadmin', href: '/superadmin/organizations' }];

interface OrganizationSummary {
    id: string;
    name: string;
    slug: string;
    memberCount: number;
    requireTransactionApproval: boolean;
    publicTransparencyEnabled: boolean;
    createdAt: string;
}

interface SuperadminOrganizationsIndexProps {
    organizations: OrganizationSummary[];
}

export default function SuperadminOrganizationsIndex({ organizations }: SuperadminOrganizationsIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Superadmin — Organisasi" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div>
                    <h1 className="text-xl font-semibold">Superadmin</h1>
                    <p className="text-muted-foreground text-sm">
                        Akses platform, hanya-lihat, lintas organisasi. Setiap kunjungan ke detail organisasi tercatat di audit log.
                    </p>
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border">
                    <table className="w-full text-left text-sm">
                        <thead className="text-muted-foreground border-b">
                            <tr>
                                <th className="px-4 py-2 font-medium">Organisasi</th>
                                <th className="px-4 py-2 font-medium">Anggota</th>
                                <th className="px-4 py-2 font-medium">Persetujuan transaksi</th>
                                <th className="px-4 py-2 font-medium">Transparansi publik</th>
                                <th className="px-4 py-2 font-medium">Dibuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            {organizations.map((organization) => (
                                <tr key={organization.id} className="border-b last:border-0">
                                    <td className="px-4 py-2">
                                        <Link
                                            href={route('superadmin.organizations.show', organization.id)}
                                            className="font-medium underline-offset-4 hover:underline"
                                        >
                                            {organization.name}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-2">{organization.memberCount}</td>
                                    <td className="px-4 py-2">{organization.requireTransactionApproval ? 'Aktif' : 'Nonaktif'}</td>
                                    <td className="px-4 py-2">{organization.publicTransparencyEnabled ? 'Aktif' : 'Nonaktif'}</td>
                                    <td className="px-4 py-2">{formatDate(organization.createdAt)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
