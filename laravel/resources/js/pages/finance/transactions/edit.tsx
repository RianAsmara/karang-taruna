import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Option {
    id: string;
    name?: string;
    title?: string;
}

interface CategoryOption {
    id: string;
    name: string;
    transaction_type: string;
}

interface TypeOption {
    value: string;
    label: string;
}

interface TransactionData {
    id: string;
    financial_account_id: string;
    related_account_id: string | null;
    category_id: string | null;
    event_id: string | null;
    amount: number;
    transaction_type: string;
    description: string | null;
    transaction_date: string;
}

interface TransactionEditProps {
    transaction: TransactionData;
    accounts: Option[];
    categories: CategoryOption[];
    events: Option[];
    transactionTypes: TypeOption[];
}

export default function TransactionsEdit({ transaction, accounts, categories, events, transactionTypes }: TransactionEditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kas', href: '/finance/accounts' },
        { title: 'Riwayat Transaksi', href: '/finance/transactions' },
        { title: 'Ubah Transaksi', href: route('finance.transactions.edit', transaction.id) },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        financial_account_id: transaction.financial_account_id,
        related_account_id: transaction.related_account_id ?? '',
        category_id: transaction.category_id ?? '',
        event_id: transaction.event_id ?? '',
        amount: String(transaction.amount),
        transaction_type: transaction.transaction_type,
        description: transaction.description ?? '',
        transaction_date: transaction.transaction_date,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('finance.transactions.update', transaction.id));
    };

    const isTransfer = data.transaction_type === 'TRANSFER';
    const availableCategories = categories.filter((c) => c.transaction_type === data.transaction_type);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ubah Transaksi" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Ubah Transaksi</h1>

                <form onSubmit={submit} className="max-w-xl space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="transaction_type">Jenis transaksi</Label>
                        <Select
                            value={data.transaction_type}
                            onValueChange={(value) => setData((prev) => ({ ...prev, transaction_type: value, category_id: '' }))}
                        >
                            <SelectTrigger id="transaction_type">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {transactionTypes.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.transaction_type} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="financial_account_id">{isTransfer ? 'Dari kas' : 'Kas'}</Label>
                        <Select value={data.financial_account_id || undefined} onValueChange={(value) => setData('financial_account_id', value)}>
                            <SelectTrigger id="financial_account_id">
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

                    {isTransfer && (
                        <div className="grid gap-2">
                            <Label htmlFor="related_account_id">Ke kas</Label>
                            <Select
                                value={data.related_account_id || undefined}
                                onValueChange={(value) => setData('related_account_id', value)}
                            >
                                <SelectTrigger id="related_account_id">
                                    <SelectValue placeholder="Pilih kas tujuan" />
                                </SelectTrigger>
                                <SelectContent>
                                    {accounts.map((account) => (
                                        <SelectItem key={account.id} value={account.id}>
                                            {account.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.related_account_id} />
                        </div>
                    )}

                    {!isTransfer && (
                        <div className="grid gap-2">
                            <Label htmlFor="category_id">Kategori</Label>
                            <Select value={data.category_id || undefined} onValueChange={(value) => setData('category_id', value)}>
                                <SelectTrigger id="category_id">
                                    <SelectValue placeholder="Pilih kategori" />
                                </SelectTrigger>
                                <SelectContent>
                                    {availableCategories.map((category) => (
                                        <SelectItem key={category.id} value={category.id}>
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.category_id} />
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="amount">Jumlah (Rp)</Label>
                        <Input id="amount" type="number" min={1} value={data.amount} onChange={(e) => setData('amount', e.target.value)} />
                        <InputError message={errors.amount} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="transaction_date">Tanggal</Label>
                        <Input
                            id="transaction_date"
                            type="date"
                            value={data.transaction_date}
                            onChange={(e) => setData('transaction_date', e.target.value)}
                            className="w-48"
                        />
                        <InputError message={errors.transaction_date} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="event_id">Kegiatan terkait (opsional)</Label>
                        <Select value={data.event_id || undefined} onValueChange={(value) => setData('event_id', value)}>
                            <SelectTrigger id="event_id">
                                <SelectValue placeholder="Tidak terkait kegiatan" />
                            </SelectTrigger>
                            <SelectContent>
                                {events.map((event) => (
                                    <SelectItem key={event.id} value={event.id}>
                                        {event.title}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.event_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Keterangan</Label>
                        <textarea
                            id="description"
                            className="border-input bg-background flex min-h-20 w-full rounded-md border px-3 py-2 text-sm"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                        />
                        <InputError message={errors.description} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        Simpan
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
