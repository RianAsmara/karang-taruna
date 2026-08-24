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
    value: string;
    label: string;
}

interface ReportsCreateProps {
    reportTypes: Option[];
    visibilities: Option[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kas', href: '/finance/accounts' },
    { title: 'Laporan', href: '/finance/reports' },
    { title: 'Buat Laporan', href: '/finance/reports/create' },
];

export default function ReportsCreate({ reportTypes, visibilities }: ReportsCreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        report_type: 'MONTHLY',
        period_start: '',
        period_end: '',
        visibility: 'MEMBERS',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('finance.reports.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Buat Laporan" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Buat Laporan</h1>
                <p className="text-muted-foreground max-w-xl text-sm">
                    Saldo awal, pemasukan, pengeluaran, dan saldo akhir dihitung otomatis dari transaksi yang telah disetujui pada periode ini —
                    tidak dapat diketik manual.
                </p>

                <form onSubmit={submit} className="max-w-xl space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Judul laporan</Label>
                        <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} placeholder="Laporan Kas Agustus 2026" autoFocus />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="report_type">Jenis laporan</Label>
                        <Select value={data.report_type} onValueChange={(value) => setData('report_type', value)}>
                            <SelectTrigger id="report_type">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {reportTypes.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.report_type} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="period_start">Awal periode</Label>
                            <Input
                                id="period_start"
                                type="date"
                                value={data.period_start}
                                onChange={(e) => setData('period_start', e.target.value)}
                            />
                            <InputError message={errors.period_start} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="period_end">Akhir periode</Label>
                            <Input id="period_end" type="date" value={data.period_end} onChange={(e) => setData('period_end', e.target.value)} />
                            <InputError message={errors.period_end} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="visibility">Visibilitas</Label>
                        <Select value={data.visibility} onValueChange={(value) => setData('visibility', value)}>
                            <SelectTrigger id="visibility">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {visibilities.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.visibility} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        Buat draf laporan
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
