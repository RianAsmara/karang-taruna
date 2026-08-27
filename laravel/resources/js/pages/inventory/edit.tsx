import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Option {
    value: string;
    label: string;
}

interface MemberOption {
    id: string;
    name: string;
}

interface InventoryEditProps {
    item: {
        id: string;
        name: string;
        category: string;
        quantity: number;
        condition: string;
        location: string | null;
        notes: string | null;
        responsible_membership_id: string | null;
    };
    categories: Option[];
    conditions: Option[];
    members: MemberOption[];
}

export default function InventoryEdit({ item, categories, conditions, members }: InventoryEditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inventaris', href: '/inventory' },
        { title: item.name, href: route('inventory.show', item.id) },
        { title: 'Ubah', href: route('inventory.edit', item.id) },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        name: item.name,
        category: item.category,
        quantity: String(item.quantity),
        condition: item.condition,
        location: item.location ?? '',
        notes: item.notes ?? '',
        responsible_membership_id: item.responsible_membership_id ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('inventory.update', item.id));
    };

    const destroy = () => router.delete(route('inventory.destroy', item.id));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ubah ${item.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Ubah barang</h1>

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

                    <div className="flex items-center justify-between">
                        <Button type="submit" disabled={processing || !data.name || !data.quantity}>
                            Simpan perubahan
                        </Button>

                        <Dialog>
                            <DialogTrigger asChild>
                                <Button type="button" variant="ghost" className="text-destructive">
                                    Hapus barang
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogTitle>Hapus {item.name}?</DialogTitle>
                                <DialogDescription>
                                    Barang yang sedang dipinjam tidak bisa dihapus. Tindakan ini tidak bisa dibatalkan.
                                </DialogDescription>
                                <DialogFooter>
                                    <DialogClose asChild>
                                        <Button variant="secondary">Batal</Button>
                                    </DialogClose>
                                    <DialogClose asChild>
                                        <Button variant="destructive" onClick={destroy}>
                                            Hapus
                                        </Button>
                                    </DialogClose>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
