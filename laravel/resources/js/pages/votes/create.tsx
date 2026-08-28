import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Voting', href: '/votes' },
    { title: 'Buat voting', href: '/votes/create' },
];

interface Option {
    value: string;
    label: string;
}

interface VoteCreateProps {
    events: { id: string; title: string }[];
    eligibleScopes: Option[];
}

export default function VoteCreate({ events, eligibleScopes }: VoteCreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        question: '',
        description: '',
        anonymous: false as boolean,
        editable: true as boolean,
        max_selections: '1',
        eligible_scope: eligibleScopes[0]?.value ?? 'ALL',
        event_id: '',
        start_at: '',
        end_at: '',
        options: ['', ''],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('votes.store'));
    };

    const updateOption = (index: number, value: string) => {
        setData(
            'options',
            data.options.map((option, i) => (i === index ? value : option)),
        );
    };

    const addOption = () => setData('options', [...data.options, '']);
    const removeOption = (index: number) => setData('options', data.options.filter((_, i) => i !== index));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Buat voting" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Buat voting</h1>

                <form onSubmit={submit} className="max-w-xl space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="question">Pertanyaan</Label>
                        <Input id="question" value={data.question} onChange={(e) => setData('question', e.target.value)} autoFocus />
                        <InputError message={errors.question} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Deskripsi (opsional)</Label>
                        <textarea
                            id="description"
                            className="border-input bg-background flex min-h-20 w-full rounded-md border px-3 py-2 text-sm"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Pilihan</Label>
                        {data.options.map((option, i) => (
                            <div key={i} className="flex items-center gap-2">
                                <Input value={option} onChange={(e) => updateOption(i, e.target.value)} placeholder={`Pilihan ${i + 1}`} />
                                {data.options.length > 2 && (
                                    <Button type="button" variant="ghost" size="sm" onClick={() => removeOption(i)}>
                                        Hapus
                                    </Button>
                                )}
                            </div>
                        ))}
                        <Button type="button" variant="outline" size="sm" onClick={addOption} className="w-fit">
                            + Tambah pilihan
                        </Button>
                        <InputError message={errors.options} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="max_selections">Maksimal dipilih</Label>
                            <Input
                                id="max_selections"
                                type="number"
                                min={1}
                                value={data.max_selections}
                                onChange={(e) => setData('max_selections', e.target.value)}
                            />
                            <InputError message={errors.max_selections} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="eligible_scope">Siapa yang berhak memilih</Label>
                            <Select value={data.eligible_scope} onValueChange={(value) => setData('eligible_scope', value)}>
                                <SelectTrigger id="eligible_scope">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {eligibleScopes.map((scope) => (
                                        <SelectItem key={scope.value} value={scope.value}>
                                            {scope.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
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
                            <Label htmlFor="end_at">Tutup</Label>
                            <Input id="end_at" type="datetime-local" value={data.end_at} onChange={(e) => setData('end_at', e.target.value)} />
                            <InputError message={errors.end_at} />
                        </div>
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
                    </div>

                    <div className="flex flex-col gap-3">
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox checked={data.anonymous} onCheckedChange={(checked) => setData('anonymous', checked === true)} />
                            Voting anonim — pilihan anggota tidak akan terlihat siapa pun
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox checked={data.editable} onCheckedChange={(checked) => setData('editable', checked === true)} />
                            Pilihan bisa diubah sampai voting ditutup
                        </label>
                    </div>

                    <Button type="submit" disabled={processing || !data.question || data.options.some((o) => !o.trim())}>
                        Buat voting
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
