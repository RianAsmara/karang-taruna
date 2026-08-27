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
    { title: 'Dokumen', href: '/documents' },
    { title: 'Unggah dokumen', href: '/documents/create' },
];

interface Option {
    value: string;
    label: string;
}

interface DocumentCreateProps {
    categories: Option[];
    events: { id: string; title: string }[];
}

export default function DocumentCreate({ categories, events }: DocumentCreateProps) {
    const { data, setData, post, processing, errors } = useForm<{
        title: string;
        category: string;
        file: File | null;
        event_id: string;
    }>({
        title: '',
        category: categories[0]?.value ?? '',
        file: null,
        event_id: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('documents.store'), { forceFormData: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Unggah dokumen" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Unggah dokumen</h1>

                <form onSubmit={submit} className="max-w-xl space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Judul</Label>
                        <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} autoFocus />
                        <InputError message={errors.title} />
                    </div>

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
                        <Label htmlFor="file">Berkas</Label>
                        <input
                            id="file"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx"
                            onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                            className="text-sm"
                        />
                        <p className="text-muted-foreground text-xs">Maksimal 10 MB.</p>
                        <InputError message={errors.file} />
                    </div>

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

                    <Button type="submit" disabled={processing || !data.title || !data.file}>
                        Unggah
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
