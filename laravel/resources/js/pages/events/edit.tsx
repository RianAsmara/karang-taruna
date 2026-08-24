import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface MemberOption {
    id: string;
    name: string;
}

interface Option {
    value: string;
    label: string;
}

interface EventEditProps {
    event: {
        id: string;
        title: string;
        description: string | null;
        location: string | null;
        startAt: string;
        endAt: string | null;
        status: string;
        lifecycleStage: string;
        picMembershipId: string | null;
    };
    members: MemberOption[];
    statuses: Option[];
    lifecycleStages: Option[];
}

function toDatetimeLocal(iso: string): string {
    return iso.slice(0, 16);
}

export default function EventsEdit({ event, members, statuses, lifecycleStages }: EventEditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kegiatan', href: '/events' },
        { title: event.title, href: route('events.show', event.id) },
        { title: 'Ubah', href: route('events.edit', event.id) },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        title: event.title,
        description: event.description ?? '',
        location: event.location ?? '',
        start_at: toDatetimeLocal(event.startAt),
        end_at: event.endAt ? toDatetimeLocal(event.endAt) : '',
        pic_membership_id: event.picMembershipId ?? '',
        status: event.status,
        lifecycle_stage: event.lifecycleStage,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('events.update', event.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ubah ${event.title}`} />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Ubah Kegiatan</h1>

                <form onSubmit={submit} className="max-w-xl space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Judul kegiatan</Label>
                        <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Deskripsi</Label>
                        <textarea
                            id="description"
                            className="border-input bg-background flex min-h-24 w-full rounded-md border px-3 py-2 text-sm"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="location">Lokasi</Label>
                        <Input id="location" value={data.location} onChange={(e) => setData('location', e.target.value)} />
                        <InputError message={errors.location} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="start_at">Mulai</Label>
                            <Input
                                id="start_at"
                                type="datetime-local"
                                value={data.start_at}
                                onChange={(e) => setData('start_at', e.target.value)}
                            />
                            <InputError message={errors.start_at} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="end_at">Selesai</Label>
                            <Input id="end_at" type="datetime-local" value={data.end_at} onChange={(e) => setData('end_at', e.target.value)} />
                            <InputError message={errors.end_at} />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="status">Status</Label>
                            <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                <SelectTrigger id="status">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {statuses.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.status} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="lifecycle_stage">Tahap</Label>
                            <Select value={data.lifecycle_stage} onValueChange={(value) => setData('lifecycle_stage', value)}>
                                <SelectTrigger id="lifecycle_stage">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {lifecycleStages.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.lifecycle_stage} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="pic_membership_id">Penanggung jawab (PIC)</Label>
                        <Select
                            value={data.pic_membership_id || undefined}
                            onValueChange={(value) => setData('pic_membership_id', value)}
                        >
                            <SelectTrigger id="pic_membership_id">
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
                        <InputError message={errors.pic_membership_id} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        Simpan
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
