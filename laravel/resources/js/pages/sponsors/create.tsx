import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Sponsor', href: '/sponsors' },
    { title: 'Tambah sponsor', href: '/sponsors/create' },
];

interface Option {
    value: string;
    label: string;
}

interface SponsorCreateProps {
    types: Option[];
    events: { id: string; title: string }[];
}

export default function SponsorCreate({ types, events }: SponsorCreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        type: 'UANG',
        amount: '',
        description: '',
        event_id: '',
        contact_name: '',
        contact_phone: '',
        notes: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('sponsors.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah sponsor" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Tambah sponsor</h1>

                <form onSubmit={submit} className="max-w-xl space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Nama sponsor</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} autoFocus />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Jenis</Label>
                        <ToggleGroup type="single" value={data.type} onValueChange={(value) => value && setData('type', value)}>
                            {types.map((type) => (
                                <ToggleGroupItem key={type.value} value={type.value}>
                                    {type.label}
                                </ToggleGroupItem>
                            ))}
                        </ToggleGroup>
                        <InputError message={errors.type} />
                    </div>

                    {data.type === 'UANG' ? (
                        <div className="grid gap-2">
                            <Label htmlFor="amount">Jumlah</Label>
                            <Input id="amount" type="number" min={1} value={data.amount} onChange={(e) => setData('amount', e.target.value)} />
                            <InputError message={errors.amount} />
                        </div>
                    ) : (
                        <div className="grid gap-2">
                            <Label htmlFor="description">Deskripsi</Label>
                            <textarea
                                id="description"
                                className="border-input bg-background flex min-h-20 w-full rounded-md border px-3 py-2 text-sm"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                            />
                            <InputError message={errors.description} />
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="event_id">Kegiatan (opsional)</Label>
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

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="contact_name">Nama kontak</Label>
                            <Input
                                id="contact_name"
                                value={data.contact_name}
                                onChange={(e) => setData('contact_name', e.target.value)}
                            />
                            <InputError message={errors.contact_name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="contact_phone">Nomor WhatsApp</Label>
                            <Input
                                id="contact_phone"
                                value={data.contact_phone}
                                onChange={(e) => setData('contact_phone', e.target.value)}
                            />
                            <InputError message={errors.contact_phone} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="notes">Catatan</Label>
                        <textarea
                            id="notes"
                            className="border-input bg-background flex min-h-20 w-full rounded-md border px-3 py-2 text-sm"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <Button type="submit" disabled={processing || !data.name}>
                        Simpan
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
