import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatRupiah } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Iuran', href: '/finance/dues' }];

interface Due {
    id: string;
    memberName: string;
    period: string;
    type: string;
    typeLabel: string;
    amountDue: number;
    amountPaid: number;
    amountOutstanding: number;
    isPaid: boolean;
}

interface Option {
    id: string;
    name: string;
}

interface DuesIndexProps {
    dues: Due[];
    isTreasurer: boolean;
    members: Option[];
    accounts: Option[];
    incomeCategories: Option[];
}

function GenerateMonthlyDuesForm() {
    const { data, setData, post, processing, errors, reset } = useForm({
        period: new Date().toISOString().slice(0, 8) + '01',
        amount_due: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('finance.dues.generate-monthly'), { onSuccess: () => reset('amount_due') });
    };

    return (
        <form onSubmit={submit} className="border-sidebar-border/70 dark:border-sidebar-border flex flex-wrap items-end gap-4 rounded-xl border p-4">
            <div className="grid gap-2">
                <Label htmlFor="period">Bulan</Label>
                <Input id="period" type="date" value={data.period} onChange={(e) => setData('period', e.target.value)} className="w-44" />
                <InputError message={errors.period} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="amount_due">Jumlah per anggota (Rp)</Label>
                <Input
                    id="amount_due"
                    type="number"
                    min={1}
                    value={data.amount_due}
                    onChange={(e) => setData('amount_due', e.target.value)}
                    className="w-48"
                />
                <InputError message={errors.amount_due} />
            </div>
            <Button type="submit" disabled={processing}>
                Buat iuran bulanan untuk semua anggota
            </Button>
        </form>
    );
}

const DUE_TYPES = [
    { value: 'EVENT', label: 'Iuran kegiatan' },
    { value: 'SPECIAL', label: 'Kontribusi khusus' },
    { value: 'DONATION', label: 'Donasi' },
];

function CreateDueForm({ members }: { members: Option[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        membership_id: '',
        period: new Date().toISOString().slice(0, 10),
        amount_due: '',
        type: 'EVENT',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('finance.dues.store'), { onSuccess: () => reset('amount_due') });
    };

    return (
        <form onSubmit={submit} className="border-sidebar-border/70 dark:border-sidebar-border flex flex-wrap items-end gap-4 rounded-xl border p-4">
            <div className="grid gap-2">
                <Label htmlFor="membership_id">Anggota</Label>
                <Select value={data.membership_id || undefined} onValueChange={(value) => setData('membership_id', value)}>
                    <SelectTrigger id="membership_id" className="w-44">
                        <SelectValue placeholder="Pilih anggota" />
                    </SelectTrigger>
                    <SelectContent>
                        {members.map((member) => (
                            <SelectItem key={member.id} value={member.id}>
                                {member.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.membership_id} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="type">Jenis</Label>
                <Select value={data.type} onValueChange={(value) => setData('type', value)}>
                    <SelectTrigger id="type" className="w-44">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {DUE_TYPES.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="grid gap-2">
                <Label htmlFor="due_period">Periode</Label>
                <Input id="due_period" type="date" value={data.period} onChange={(e) => setData('period', e.target.value)} className="w-40" />
                <InputError message={errors.period} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="due_amount">Jumlah (Rp)</Label>
                <Input
                    id="due_amount"
                    type="number"
                    min={1}
                    value={data.amount_due}
                    onChange={(e) => setData('amount_due', e.target.value)}
                    className="w-36"
                />
                <InputError message={errors.amount_due} />
            </div>
            <Button type="submit" disabled={processing}>
                Tambah tagihan
            </Button>
        </form>
    );
}

function PaymentForm({ due, accounts, incomeCategories }: { due: Due; accounts: Option[]; incomeCategories: Option[] }) {
    const { data, setData, post, processing, errors } = useForm({
        amount: String(due.amountOutstanding),
        paid_at: new Date().toISOString().slice(0, 10),
        financial_account_id: accounts[0]?.id ?? '',
        category_id: incomeCategories[0]?.id ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('finance.dues.payments.store', due.id), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="bg-muted/30 mt-2 flex flex-wrap items-end gap-3 rounded-lg p-3">
            <div className="grid gap-2">
                <Label htmlFor={`amount-${due.id}`}>Jumlah (Rp)</Label>
                <Input
                    id={`amount-${due.id}`}
                    type="number"
                    min={1}
                    value={data.amount}
                    onChange={(e) => setData('amount', e.target.value)}
                    className="w-36"
                />
                <InputError message={errors.amount} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor={`paid_at-${due.id}`}>Tanggal</Label>
                <Input
                    id={`paid_at-${due.id}`}
                    type="date"
                    value={data.paid_at}
                    onChange={(e) => setData('paid_at', e.target.value)}
                    className="w-40"
                />
            </div>
            <div className="grid gap-2">
                <Label htmlFor={`account-${due.id}`}>Masuk ke kas</Label>
                <Select value={data.financial_account_id || undefined} onValueChange={(value) => setData('financial_account_id', value)}>
                    <SelectTrigger id={`account-${due.id}`} className="w-40">
                        <SelectValue placeholder="Pilih kas" />
                    </SelectTrigger>
                    <SelectContent>
                        {accounts.map((account) => (
                            <SelectItem key={account.id} value={account.id}>
                                {account.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.financial_account_id} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor={`category-${due.id}`}>Kategori</Label>
                <Select value={data.category_id || undefined} onValueChange={(value) => setData('category_id', value)}>
                    <SelectTrigger id={`category-${due.id}`} className="w-40">
                        <SelectValue placeholder="Pilih kategori" />
                    </SelectTrigger>
                    <SelectContent>
                        {incomeCategories.map((category) => (
                            <SelectItem key={category.id} value={category.id}>
                                {category.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.category_id} />
            </div>
            <Button type="submit" size="sm" disabled={processing}>
                Simpan pembayaran
            </Button>
        </form>
    );
}

function DueRow({ due, isTreasurer, accounts, incomeCategories }: { due: Due; isTreasurer: boolean; accounts: Option[]; incomeCategories: Option[] }) {
    const [showPaymentForm, setShowPaymentForm] = useState(false);

    const deleteDue = () => {
        router.delete(route('finance.dues.destroy', due.id), { preserveScroll: true });
    };

    return (
        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
            <div className="flex items-center justify-between">
                <div>
                    <p className="font-medium">
                        {due.memberName} — {due.typeLabel}
                    </p>
                    <p className="text-muted-foreground text-sm">
                        {formatDate(due.period)} · {formatRupiah(due.amountDue)}
                        {due.amountPaid > 0 && !due.isPaid && ` · Terbayar ${formatRupiah(due.amountPaid)}`}
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    <Badge variant={due.isPaid ? 'default' : 'secondary'}>{due.isPaid ? 'Lunas' : formatRupiah(due.amountOutstanding)}</Badge>
                    {isTreasurer && !due.isPaid && (
                        <Button variant="outline" size="sm" onClick={() => setShowPaymentForm((v) => !v)}>
                            Catat Pembayaran
                        </Button>
                    )}
                    {isTreasurer && due.amountPaid === 0 && (
                        <Button variant="ghost" size="sm" onClick={deleteDue}>
                            Hapus
                        </Button>
                    )}
                </div>
            </div>
            {showPaymentForm && <PaymentForm due={due} accounts={accounts} incomeCategories={incomeCategories} />}
        </div>
    );
}

export default function DuesIndex({ dues, isTreasurer, members, accounts, incomeCategories }: DuesIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Iuran" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Iuran</h1>

                {isTreasurer && (
                    <>
                        <GenerateMonthlyDuesForm />
                        <CreateDueForm members={members} />
                    </>
                )}

                {dues.length === 0 ? (
                    <p className="text-muted-foreground text-sm">Belum ada iuran.</p>
                ) : (
                    <div className="grid gap-3">
                        {dues.map((due) => (
                            <DueRow key={due.id} due={due} isTreasurer={isTreasurer} accounts={accounts} incomeCategories={incomeCategories} />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
