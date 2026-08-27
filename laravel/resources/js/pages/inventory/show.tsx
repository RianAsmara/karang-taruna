import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Option {
    value: string;
    label: string;
}

interface LoanRow {
    id: string;
    borrowerName: string;
    quantity: number;
    status: string;
    statusLabel: string;
    isOverdue: boolean;
    purpose: string | null;
    borrowedAt: string;
    dueDate: string;
    returnedAt: string | null;
    eventTitle: string | null;
}

interface InventoryShowProps {
    item: {
        id: string;
        name: string;
        category: string;
        categoryLabel: string;
        quantity: number;
        availableQuantity: number;
        condition: string;
        conditionLabel: string;
        location: string | null;
        notes: string | null;
        lastCheckedAt: string | null;
        responsible: { id: string; name: string } | null;
    };
    loans: LoanRow[];
    canManage: boolean;
    canBorrow: boolean;
    myActiveLoanId: string | null;
    conditions: Option[];
    events: { id: string; title: string }[];
}

function BorrowForm({ item, events }: { item: InventoryShowProps['item']; events: { id: string; title: string }[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        quantity: '1',
        due_date: '',
        purpose: '',
        event_id: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('inventory.loans.store', item.id), { onSuccess: () => reset() });
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button disabled={item.availableQuantity === 0}>Pinjam</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Pinjam {item.name}</DialogTitle>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="quantity">Jumlah</Label>
                        <Input
                            id="quantity"
                            type="number"
                            min={1}
                            max={item.availableQuantity}
                            value={data.quantity}
                            onChange={(e) => setData('quantity', e.target.value)}
                        />
                        <p className="text-muted-foreground text-xs">Hanya {item.availableQuantity} tersedia.</p>
                        <InputError message={errors.quantity} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="due_date">Kembali paling lambat</Label>
                        <Input id="due_date" type="date" value={data.due_date} onChange={(e) => setData('due_date', e.target.value)} />
                        <InputError message={errors.due_date} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="purpose">Keperluan</Label>
                        <Input id="purpose" value={data.purpose} onChange={(e) => setData('purpose', e.target.value)} />
                        <InputError message={errors.purpose} />
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

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="secondary" type="button">
                                Batal
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing || !data.due_date}>
                            Pinjam
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function ReturnForm({ loanId, conditions }: { loanId: string; conditions: Option[] }) {
    const { data, setData, post, processing, errors } = useForm({
        quantity: '1',
        condition: conditions[0]?.value ?? '',
        note: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('inventory.loans.return', loanId));
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="outline">Kembalikan</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Kembalikan barang</DialogTitle>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="return_quantity">Jumlah</Label>
                        <Input
                            id="return_quantity"
                            type="number"
                            min={1}
                            value={data.quantity}
                            onChange={(e) => setData('quantity', e.target.value)}
                        />
                        <InputError message={errors.quantity} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="return_condition">Kondisi saat dikembalikan</Label>
                        <Select value={data.condition} onValueChange={(value) => setData('condition', value)}>
                            <SelectTrigger id="return_condition">
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
                        <Label htmlFor="return_note">Catatan</Label>
                        <textarea
                            id="return_note"
                            className="border-input bg-background flex min-h-20 w-full rounded-md border px-3 py-2 text-sm"
                            value={data.note}
                            onChange={(e) => setData('note', e.target.value)}
                        />
                        <p className="text-muted-foreground text-xs">Wajib diisi jika kondisi menurun.</p>
                        <InputError message={errors.note} />
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="secondary" type="button">
                                Batal
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing}>
                            Kembalikan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function InventoryShow({ item, loans, canManage, canBorrow, myActiveLoanId, conditions, events }: InventoryShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inventaris', href: '/inventory' },
        { title: item.name, href: route('inventory.show', item.id) },
    ];

    const activeLoans = loans.filter((loan) => loan.status === 'BORROWED');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={item.name} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-start justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-xl font-semibold">{item.name}</h1>
                            <Badge variant={item.availableQuantity > 0 ? 'default' : 'secondary'}>
                                {item.condition === 'RUSAK' ? 'Rusak' : item.availableQuantity > 0 ? 'Tersedia' : 'Dipinjam'}
                            </Badge>
                        </div>
                        <p className="text-muted-foreground text-sm">{item.categoryLabel}</p>
                    </div>
                    <div className="flex gap-2">
                        {canManage && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('inventory.edit', item.id)}>Ubah barang</Link>
                            </Button>
                        )}
                        {myActiveLoanId ? (
                            <ReturnForm loanId={myActiveLoanId} conditions={conditions} />
                        ) : (
                            canBorrow && <BorrowForm item={item} events={events} />
                        )}
                    </div>
                </div>

                <dl className="grid max-w-xl grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt className="text-muted-foreground">Jumlah</dt>
                    <dd>{item.quantity}</dd>
                    <dt className="text-muted-foreground">Tersedia</dt>
                    <dd>{item.availableQuantity}</dd>
                    <dt className="text-muted-foreground">Kondisi</dt>
                    <dd>
                        {item.conditionLabel}
                        {item.lastCheckedAt && ` · diperiksa ${formatDate(item.lastCheckedAt)}`}
                    </dd>
                    {item.location && (
                        <>
                            <dt className="text-muted-foreground">Lokasi</dt>
                            <dd>{item.location}</dd>
                        </>
                    )}
                    {item.notes && (
                        <>
                            <dt className="text-muted-foreground">Catatan</dt>
                            <dd>{item.notes}</dd>
                        </>
                    )}
                    <dt className="text-muted-foreground">Penanggung jawab</dt>
                    <dd>{item.responsible ? item.responsible.name : '—'}</dd>
                </dl>

                <section className="max-w-xl space-y-3">
                    <h2 className="text-sm font-semibold">Sedang dipinjam</h2>
                    {activeLoans.length === 0 ? (
                        <p className="text-muted-foreground text-sm">Tidak sedang dipinjam.</p>
                    ) : (
                        <ul className="space-y-2">
                            {activeLoans.map((loan) => (
                                <li
                                    key={loan.id}
                                    className={`rounded-lg border p-3 text-sm ${loan.isOverdue ? 'border-destructive border-l-4' : 'border-sidebar-border/70 dark:border-sidebar-border'}`}
                                >
                                    <p className="font-medium">
                                        {loan.borrowerName} · {loan.quantity}
                                    </p>
                                    <p className="text-muted-foreground">
                                        Kembali {formatDate(loan.dueDate)}
                                        {loan.isOverdue && ' · Terlambat'}
                                        {loan.eventTitle && ` · ${loan.eventTitle}`}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="max-w-xl space-y-3">
                    <h2 className="text-sm font-semibold">Riwayat</h2>
                    {loans.length === 0 ? (
                        <p className="text-muted-foreground text-sm">Belum ada riwayat.</p>
                    ) : (
                        <ul className="space-y-2">
                            {loans.map((loan) => (
                                <li key={loan.id} className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3 text-sm">
                                    <p className="font-medium">
                                        {loan.borrowerName} · {loan.quantity} · {loan.statusLabel}
                                    </p>
                                    <p className="text-muted-foreground">
                                        {formatDate(loan.borrowedAt)}
                                        {loan.returnedAt && ` – ${formatDate(loan.returnedAt)}`}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
