import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inventaris', href: '/inventory' },
    { title: 'Tambah barang', href: '/inventory/create' },
];

interface Option {
    value: string;
    label: string;
}

interface MemberOption {
    id: string;
    name: string;
}

interface InventoryCreateProps {
    categories: Option[];
    conditions: Option[];
    members: MemberOption[];
}

export default function InventoryCreate({ categories, conditions, members }: InventoryCreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        category: categories[0]?.value ?? '',
        quantity: '1',
        condition: conditions[0]?.value ?? '',
        location: '',
        notes: '',
        responsible_membership_id: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('inventory.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah barang" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Tambah barang</h1>

                <form onSubmit={submit} className="max-w-xl space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Nama</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} autoFocus />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="category">Kategori</Label>
                            <Select value={data.category} onValueChange={(value) => setData('category', value)}>
                                <SelectTrigger id="category">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {categories.map((category) => (
                                        <SelectItem key={category.value} value={category.value}>
                                            {category.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.category} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="quantity">Jumlah</Label>
                            <Input
                                id="quantity"
                                type="number"
                                min={1}
                                value={data.quantity}
                                onChange={(e) => setData('quantity', e.target.value)}
                            />
                            <InputError message={errors.quantity} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="condition">Kondisi</Label>
                        <Select value={data.condition} onValueChange={(value) => setData('condition', value)}>
                            <SelectTrigger id="condition">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {conditions.map((condition) => (
                                    <SelectItem key={condition.value} value={condition.value}>
                                        {condition.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.condition} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="location">Lokasi</Label>
                        <Input id="location" value={data.location} onChange={(e) => setData('location', e.target.value)} />
                        <InputError message={errors.location} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="notes">Catatan</Label>
                        <textarea
                            id="notes"
                            className="border-input bg-background flex min-h-24 w-full rounded-md border px-3 py-2 text-sm"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="responsible_membership_id">Penanggung jawab</Label>
                        <Select
                            value={data.responsible_membership_id || undefined}
                            onValueChange={(value) => setData('responsible_membership_id', value)}
                        >
                            <SelectTrigger id="responsible_membership_id">
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
                        <InputError message={errors.responsible_membership_id} />
                    </div>

                    <Button type="submit" disabled={processing || !data.name || !data.quantity}>
                        Simpan
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
