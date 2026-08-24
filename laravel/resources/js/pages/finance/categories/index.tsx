import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kas', href: '/finance/accounts' },
    { title: 'Kategori', href: '/finance/categories' },
];

interface Category {
    id: string;
    name: string;
    transactionType: string;
    transactionTypeLabel: string;
}

interface CategoriesIndexProps {
    categories: Category[];
    canManage: boolean;
}

function AddCategoryForm() {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', transaction_type: 'INCOME' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('finance.categories.store'), { onSuccess: () => reset('name') });
    };

    return (
        <form onSubmit={submit} className="border-sidebar-border/70 dark:border-sidebar-border flex flex-wrap items-end gap-4 rounded-xl border p-4">
            <div className="grid gap-2">
                <Label htmlFor="name">Nama kategori</Label>
                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Konsumsi" className="w-48" />
                <InputError message={errors.name} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="transaction_type">Jenis</Label>
                <Select value={data.transaction_type} onValueChange={(value) => setData('transaction_type', value)}>
                    <SelectTrigger id="transaction_type" className="w-40">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="INCOME">Pemasukan</SelectItem>
                        <SelectItem value="EXPENSE">Pengeluaran</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={errors.transaction_type} />
            </div>
            <Button type="submit" disabled={processing}>
                Tambah kategori
            </Button>
        </form>
    );
}

export default function CategoriesIndex({ categories, canManage }: CategoriesIndexProps) {
    const deleteCategory = (category: Category) => {
        router.delete(route('finance.categories.destroy', category.id), { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kategori Kas" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Kategori</h1>

                {canManage && <AddCategoryForm />}

                <div className="grid gap-3">
                    {categories.map((category) => (
                        <div
                            key={category.id}
                            className="border-sidebar-border/70 dark:border-sidebar-border flex items-center justify-between rounded-xl border p-4"
                        >
                            <div className="flex items-center gap-3">
                                <span className="font-medium">{category.name}</span>
                                <Badge variant="secondary">{category.transactionTypeLabel}</Badge>
                            </div>
                            {canManage && (
                                <Button variant="ghost" size="sm" onClick={() => deleteCategory(category)}>
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
