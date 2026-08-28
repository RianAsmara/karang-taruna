import { CreateOrganizationForm } from '@/components/create-organization-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Buat organisasi', href: '/organizations/create' }];

export default function OrganizationsCreate() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Buat organisasi" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <CreateOrganizationForm
                    title="Buat organisasi baru"
                    body="Anda akan menjadi ketua organisasi baru ini. Organisasi Anda yang sudah ada tidak terpengaruh — Anda bisa berpindah kapan saja lewat menu akun."
                />
            </div>
        </AppLayout>
    );
}
