import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatRupiah } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface SponsorContributionData {
    id: string;
    sponsor: { id: string; name: string; contactName: string | null; contactPhone: string | null };
    type: string;
    typeLabel: string;
    status: string;
    statusLabel: string;
    amount: number | null;
    description: string | null;
    event: { id: string; title: string } | null;
    notes: string | null;
    createdAt: string;
}

interface Option {
    id: string;
    name: string;
}

interface SponsorShowProps {
    contribution: SponsorContributionData;
    history: SponsorContributionData[];
    canManage: boolean;
    accounts: Option[];
    categories: Option[];
}

const NEXT_STATUS: Record<string, { value: string; label: string }[]> = {
    DIAJUKAN: [
        { value: 'SETUJU', label: 'Tandai setuju' },
        { value: 'BATAL', label: 'Batalkan' },
    ],
    SETUJU: [
        { value: 'DITERIMA', label: 'Tandai diterima' },
        { value: 'BATAL', label: 'Batalkan' },
    ],
    DITERIMA: [{ value: 'BATAL', label: 'Batalkan' }],
    BATAL: [],
};

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    DIAJUKAN: 'outline',
    SETUJU: 'secondary',
    DITERIMA: 'default',
    BATAL: 'destructive',
};

function AdvanceStatusButton({
    contribution,
    target,
    label,
    accounts,
    categories,
}: {
    contribution: SponsorContributionData;
    target: string;
    label: string;
    accounts: Option[];
    categories: Option[];
}) {
    const { data, setData, patch, processing } = useForm({
        status: target,
        financial_account_id: '',
        category_id: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('sponsors.update-status', contribution.id));
    };

    const offersTransaction = target === 'DITERIMA' && contribution.type === 'UANG';

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant={target === 'BATAL' ? 'destructive' : 'default'} size="sm">
                    {label}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>{label}?</DialogTitle>
                <form onSubmit={submit} className="space-y-4">
                    {offersTransaction && (
                        <>
                            <p className="text-muted-foreground text-sm">
                                Catat transaksi pemasukan yang sesuai sekarang, atau lewati dan catat manual nanti.
                            </p>
                            <div className="grid gap-2">
                                <Select value={data.financial_account_id || undefined} onValueChange={(value) => setData('financial_account_id', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih kas (opsional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {accounts.map((account) => (
                                            <SelectItem key={account.id} value={account.id}>
                                                {account.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Select value={data.category_id || undefined} onValueChange={(value) => setData('category_id', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih kategori (opsional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((category) => (
                                            <SelectItem key={category.id} value={category.id}>
                                                {category.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </>
                    )}
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="secondary" type="button">
                                Batal
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing}>
                            {label}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function SponsorShow({ contribution, history, canManage, accounts, categories }: SponsorShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Sponsor', href: '/sponsors' },
        { title: contribution.sponsor.name, href: route('sponsors.show', contribution.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={contribution.sponsor.name} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-start justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-xl font-semibold">{contribution.sponsor.name}</h1>
                            <Badge variant="outline">{contribution.typeLabel}</Badge>
                            <Badge variant={STATUS_VARIANT[contribution.status] ?? 'outline'}>{contribution.statusLabel}</Badge>
                        </div>
                        <p className="text-2xl font-semibold">
                            {contribution.amount !== null ? formatRupiah(contribution.amount) : contribution.description}
                        </p>
                    </div>
                    {canManage && (
                        <div className="flex gap-2">
                            {NEXT_STATUS[contribution.status].map((next) => (
                                <AdvanceStatusButton
                                    key={next.value}
                                    contribution={contribution}
                                    target={next.value}
                                    label={next.label}
                                    accounts={accounts}
                                    categories={categories}
                                />
                            ))}
                        </div>
                    )}
                </div>

                <dl className="grid max-w-xl grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    {contribution.event && (
                        <>
                            <dt className="text-muted-foreground">Kegiatan</dt>
                            <dd>{contribution.event.title}</dd>
                        </>
                    )}
                    <dt className="text-muted-foreground">Dicatat</dt>
                    <dd>{formatDate(contribution.createdAt)}</dd>
                </dl>

                {canManage && (contribution.sponsor.contactName || contribution.sponsor.contactPhone) && (
                    <section className="max-w-xl space-y-1">
                        <h2 className="text-sm font-semibold">Kontak</h2>
                        <p className="text-sm">{contribution.sponsor.contactName ?? '—'}</p>
                        <p className="text-muted-foreground text-sm">{contribution.sponsor.contactPhone ?? '—'}</p>
                    </section>
                )}
                {!canManage && (
                    <p className="text-muted-foreground max-w-xl text-sm">Hanya bendahara dan ketua yang mengelola sponsor.</p>
                )}

                {contribution.notes && (
                    <section className="max-w-xl space-y-1">
                        <h2 className="text-sm font-semibold">Catatan</h2>
                        <p className="text-sm">{contribution.notes}</p>
                    </section>
                )}

                <section className="max-w-xl space-y-3">
                    <h2 className="text-sm font-semibold">Riwayat dukungan</h2>
                    {history.length === 0 ? (
                        <p className="text-muted-foreground text-sm">Belum ada dukungan lain dari sponsor ini.</p>
                    ) : (
                        <ul className="space-y-2">
                            {history.map((item) => (
                                <li key={item.id} className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3 text-sm">
                                    <p className="font-medium">
                                        {item.amount !== null ? formatRupiah(item.amount) : item.typeLabel} · {item.statusLabel}
                                    </p>
                                    <p className="text-muted-foreground">{formatDate(item.createdAt)}</p>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
