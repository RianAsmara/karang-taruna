import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pengumuman', href: '/announcements' },
    { title: 'Buat Pengumuman', href: '/announcements/create' },
];

export default function AnnouncementsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        body: '',
        published_at: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('announcements.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Buat Pengumuman" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Buat Pengumuman</h1>

                <form onSubmit={submit} className="max-w-xl space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Judul</Label>
                        <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} autoFocus />
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
