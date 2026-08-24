import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Kas', href: '/finance/accounts' }];

interface Account {
    id: string;
    name: string;
    balance: number;
}

interface AccountsIndexProps {
    accounts: Account[];
    totalBalance: number;
    canManage: boolean;
}

function AddAccountForm() {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('finance.accounts.store'), { onSuccess: () => reset() });
    };

    return (
        <form onSubmit={submit} className="border-sidebar-border/70 dark:border-sidebar-border flex flex-wrap items-end gap-4 rounded-xl border p-4">
            <div className="grid gap-2">
                <Label htmlFor="name">Nama kas baru</Label>
                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Kas Olahraga" className="w-64" />
                <InputError message={errors.name} />
            </div>
            <Button type="submit" disabled={processing}>
                Tambah kas
            </Button>
        </form>
    );
}

export default function AccountsIndex({ accounts, totalBalance, canManage }: AccountsIndexProps) {
    const deleteAccount = (account: Account) => {
        router.delete(route('finance.accounts.destroy', account.id), { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kas" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Kas</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={route('finance.categories.index')}>Kategori</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={route('finance.transactions.index')}>Riwayat Transaksi</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={route('finance.reports.index')}>Laporan</Link>
                        </Button>
                    </div>
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                    <p className="text-muted-foreground text-sm">Total saldo</p>
                    <p className="text-2xl font-semibold">{formatRupiah(totalBalance)}</p>
                </div>

                {canManage && <AddAccountForm />}

                <div className="grid gap-3">
                    {accounts.map((account) => (
                        <div
                            key={account.id}
                            className="border-sidebar-border/70 dark:border-sidebar-border flex items-center justify-between rounded-xl border p-4"
                        >
                            <div>
                                <p className="font-medium">{account.name}</p>
                                <p className="text-muted-foreground text-sm">{formatRupiah(account.balance)}</p>
                            </div>
                            {canManage && (
                                <Button variant="ghost" size="sm" onClick={() => deleteAccount(account)}>
                                    Hapus
                                </Button>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
