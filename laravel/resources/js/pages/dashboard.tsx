import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

function CreateOrganizationCard() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('organizations.store'));
    };

    return (
        <div className="border-sidebar-border/70 dark:border-sidebar-border mx-auto w-full max-w-md rounded-xl border p-6">
            <h2 className="text-lg font-semibold">Buat organisasi</h2>
            <p className="text-muted-foreground mt-1 text-sm">
                Anda belum tergabung di organisasi manapun. Buat organisasi untuk Karang Taruna, Pemuda Kampung, atau komunitas Anda.
            </p>

            <form onSubmit={submit} className="mt-4 space-y-4">
                <div className="grid gap-2">
                    <Label htmlFor="name">Nama organisasi</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="Karang Taruna Melati"
                        autoFocus
                    />
                    <InputError message={errors.name} />
                </div>

                <Button type="submit" disabled={processing}>
                    Buat organisasi
                </Button>
            </form>
        </div>
    );
}

function OrganizationSummary() {
    const { currentOrganization } = usePage<SharedData>().props;

    if (!currentOrganization) {
        return null;
    }

    return (
        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
            <p className="text-muted-foreground text-sm">Organisasi Anda</p>
            <p className="text-lg font-semibold">{currentOrganization.name}</p>
            <p className="text-muted-foreground text-sm">Peran: {currentOrganization.roleLabel}</p>
        </div>
    );
}

export default function Dashboard() {
    const { currentOrganization } = usePage<SharedData>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                {currentOrganization ? <OrganizationSummary /> : <CreateOrganizationCard />}
            </div>
        </AppLayout>
    );
}
