import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface AnnouncementEditProps {
    announcement: {
        id: string;
        title: string;
        body: string;
        publishedAt: string | null;
    };
}

export default function AnnouncementsEdit({ announcement }: AnnouncementEditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Pengumuman', href: '/announcements' },
        { title: announcement.title, href: route('announcements.show', announcement.id) },
        { title: 'Ubah', href: route('announcements.edit', announcement.id) },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        title: announcement.title,
        body: announcement.body,
        published_at: announcement.publishedAt ? announcement.publishedAt.slice(0, 16) : '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('announcements.update', announcement.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ubah ${announcement.title}`} />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Ubah Pengumuman</h1>

                <form onSubmit={submit} className="max-w-xl space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Judul</Label>
                        <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="body">Isi pengumuman</Label>
                        <textarea
                            id="body"
                            className="border-input bg-background flex min-h-40 w-full rounded-md border px-3 py-2 text-sm"
                            value={data.body}
                            onChange={(e) => setData('body', e.target.value)}
                        />
                        <InputError message={errors.body} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="published_at">Tanggal terbit (kosongkan untuk simpan sebagai draf)</Label>
                        <Input
                            id="published_at"
                            type="datetime-local"
                            value={data.published_at}
                            onChange={(e) => setData('published_at', e.target.value)}
                            className="w-64"
                        />
                        <InputError message={errors.published_at} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        Simpan
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
