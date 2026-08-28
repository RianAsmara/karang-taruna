import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, formatRupiah } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Option {
    value: string;
    label: string;
}

interface Committee {
    id: string;
    name: string;
    roleTitle: string | null;
}

interface Task {
    id: string;
    title: string;
    description: string | null;
    status: string;
    statusLabel: string;
    priority: string;
    priorityLabel: string;
    dueDate: string | null;
    assignee: { id: string; name: string } | null;
}

interface Participant {
    id: string;
    membershipId: string;
    name: string;
    status: string;
    statusLabel: string;
}

interface MemberOption {
    id: string;
    name: string;
}

interface BudgetSummary {
    actualIncome: number;
    actualExpense: number;
    actualNet: number;
    plannedIncome?: number;
    plannedExpense?: number;
    varianceIncome?: number;
    varianceExpense?: number;
}

interface AttendanceSummary {
    myStatus: string | null;
    count: number;
    canCheckIn: boolean;
}

interface EventShowProps {
    event: {
        id: string;
        title: string;
        description: string | null;
        location: string | null;
        startAt: string;
        endAt: string | null;
        status: string;
        statusLabel: string;
        lifecycleStage: string;
        lifecycleStageLabel: string;
        pic: { id: string; name: string } | null;
    };
    committees: Committee[];
    tasks: Task[];
    participants: Participant[];
    members: MemberOption[];
    canManage: boolean;
    canDelete: boolean;
    currentMembershipId: string | null;
    taskPriorities: Option[];
    budget: BudgetSummary;
    attendance: AttendanceSummary;
    canManageAttendance: boolean;
}

const TASK_STATUSES: Option[] = [
    { value: 'TODO', label: 'Belum dikerjakan' },
    { value: 'IN_PROGRESS', label: 'Sedang dikerjakan' },
    { value: 'BLOCKED', label: 'Terhambat' },
    { value: 'DONE', label: 'Selesai' },
];

function AddCommitteeForm({ eventId, members }: { eventId: string; members: MemberOption[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({ membership_id: '', role_title: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('events.committee.store', eventId), { onSuccess: () => reset() });
    };

    return (
        <form onSubmit={submit} className="flex flex-wrap items-end gap-3">
            <div className="grid gap-2">
                <Label htmlFor="committee_member">Anggota</Label>
                <Select value={data.membership_id || undefined} onValueChange={(value) => setData('membership_id', value)}>
                    <SelectTrigger id="committee_member" className="w-48">
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
                <InputError message={errors.membership_id} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="role_title">Jabatan (opsional)</Label>
                <Input
                    id="role_title"
                    value={data.role_title}
                    onChange={(e) => setData('role_title', e.target.value)}
                    placeholder="Sie Konsumsi"
                    className="w-48"
                />
            </div>
            <Button type="submit" disabled={processing}>
                Tambah
            </Button>
        </form>
    );
}

function AddTaskForm({ eventId, members, priorities }: { eventId: string; members: MemberOption[]; priorities: Option[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        assignee_membership_id: '',
        priority: 'MEDIUM',
        due_date: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('events.tasks.store', eventId), { onSuccess: () => reset('title', 'assignee_membership_id', 'due_date') });
    };

    return (
        <form onSubmit={submit} className="flex flex-wrap items-end gap-3">
            <div className="grid gap-2">
                <Label htmlFor="task_title">Tugas baru</Label>
                <Input id="task_title" value={data.title} onChange={(e) => setData('title', e.target.value)} className="w-48" />
                <InputError message={errors.title} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="task_assignee">Ditugaskan ke</Label>
                <Select
                    value={data.assignee_membership_id || undefined}
                    onValueChange={(value) => setData('assignee_membership_id', value)}
                >
                    <SelectTrigger id="task_assignee" className="w-44">
                        <SelectValue placeholder="Belum ditugaskan" />
                    </SelectTrigger>
                    <SelectContent>
                        {members.map((member) => (
                            <SelectItem key={member.id} value={member.id}>
                                {member.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="grid gap-2">
                <Label htmlFor="task_priority">Prioritas</Label>
                <Select value={data.priority} onValueChange={(value) => setData('priority', value)}>
                    <SelectTrigger id="task_priority" className="w-32">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {priorities.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="grid gap-2">
                <Label htmlFor="task_due_date">Tenggat</Label>
                <Input
                    id="task_due_date"
                    type="date"
                    value={data.due_date}
                    onChange={(e) => setData('due_date', e.target.value)}
                    className="w-40"
                />
            </div>
            <Button type="submit" disabled={processing}>
                Tambah
            </Button>
        </form>
    );
}

export default function EventShow({
    event,
    committees,
    tasks,
    participants,
    members,
    canManage,
    canDelete,
    currentMembershipId,
    taskPriorities,
    budget,
    attendance,
    canManageAttendance,
}: EventShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kegiatan', href: '/events' },
        { title: event.title, href: route('events.show', event.id) },
    ];

    const myParticipant = participants.find((p) => currentMembershipId && p.membershipId === currentMembershipId);

    const register = () => router.post(route('events.participants.store', event.id), {}, { preserveScroll: true });
    const cancelParticipation = (participantId: string) =>
        router.delete(route('events.participants.destroy', [event.id, participantId]), { preserveScroll: true });

    const updateTaskStatus = (taskId: string, status: string) =>
        router.patch(route('events.tasks.update-status', [event.id, taskId]), { status }, { preserveScroll: true });

    const deleteTask = (taskId: string) => router.delete(route('events.tasks.destroy', [event.id, taskId]), { preserveScroll: true });

    const removeCommittee = (committeeId: string) =>
        router.delete(route('events.committee.destroy', [event.id, committeeId]), { preserveScroll: true });

    const removeParticipant = (participantId: string) =>
        router.delete(route('events.participants.destroy', [event.id, participantId]), { preserveScroll: true });

    const deleteEvent = () => router.delete(route('events.destroy', event.id));

    const checkIn = () => router.post(route('events.attendance.store', event.id), {}, { preserveScroll: true });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={event.title} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">{event.title}</h1>
                        <p className="text-muted-foreground text-sm">{formatDateTime(event.startAt)}</p>
                        {event.location && <p className="text-muted-foreground text-sm">{event.location}</p>}
                        {event.description && <p className="mt-2 max-w-2xl text-sm">{event.description}</p>}
                        <div className="mt-2 flex gap-2">
                            <Badge variant="secondary">{event.statusLabel}</Badge>
                            <Badge variant="outline">{event.lifecycleStageLabel}</Badge>
                        </div>
                        {event.pic && <p className="text-muted-foreground mt-2 text-sm">PIC: {event.pic.name}</p>}
                    </div>
                    <div className="flex gap-2">
                        {canManage && (
                            <Button variant="outline" asChild>
                                <Link href={route('events.edit', event.id)}>Ubah</Link>
                            </Button>
                        )}
                        {canDelete && (
                            <Button variant="destructive" onClick={deleteEvent}>
                                Hapus
                            </Button>
                        )}
                    </div>
                </div>

                <section className="border-sidebar-border/70 dark:border-sidebar-border flex items-center justify-between rounded-lg border p-4">
                    <div>
                        <h2 className="font-semibold">Kehadiran</h2>
                        <p className="text-muted-foreground text-sm">{attendance.count} orang tercatat hadir</p>
                    </div>
                    <div className="flex items-center gap-2">
                        {canManageAttendance && (
                            <>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={route('events.attendance.index', event.id)}>Lihat daftar hadir</Link>
                                </Button>
                                <Button variant="outline" size="sm" asChild>
                                    <a href={route('events.attendance.qr', event.id)} target="_blank" rel="noopener noreferrer">
                                        QR presensi
                                    </a>
                                </Button>
                            </>
                        )}
                        {attendance.myStatus === 'HADIR' ? (
                            <Badge>Hadir</Badge>
                        ) : (
                            attendance.canCheckIn && (
                                <Button size="sm" onClick={checkIn}>
                                    Konfirmasi kehadiran saya
                                </Button>
                            )
                        )}
                    </div>
                </section>

                <section className="space-y-3">
                    <div className="flex items-center justify-between">
                        <h2 className="font-semibold">Anggaran</h2>
                        <Link
                            href={`${route('finance.transactions.index')}?event_id=${event.id}`}
                            className="text-muted-foreground text-sm underline"
                        >
                            Lihat transaksi
                        </Link>
                    </div>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                            <p className="text-muted-foreground text-xs">Pemasukan aktual</p>
                            <p className="font-medium text-emerald-600 dark:text-emerald-400">{formatRupiah(budget.actualIncome)}</p>
                        </div>
                        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                            <p className="text-muted-foreground text-xs">Pengeluaran aktual</p>
                            <p className="text-destructive font-medium">{formatRupiah(budget.actualExpense)}</p>
                        </div>
                        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                            <p className="text-muted-foreground text-xs">Selisih (netto)</p>
                            <p className="font-medium">{formatRupiah(budget.actualNet)}</p>
                        </div>
                    </div>
                    {budget.plannedIncome !== undefined && (
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                                <p className="text-muted-foreground text-xs">Pemasukan direncanakan</p>
                                <p className="text-sm">{formatRupiah(budget.plannedIncome)}</p>
                            </div>
                            <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                                <p className="text-muted-foreground text-xs">Pengeluaran direncanakan</p>
                                <p className="text-sm">{formatRupiah(budget.plannedExpense ?? 0)}</p>
                            </div>
                            <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                                <p className="text-muted-foreground text-xs">Varians pemasukan</p>
                                <p className="text-sm">{formatRupiah(budget.varianceIncome ?? 0)}</p>
                            </div>
                            <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                                <p className="text-muted-foreground text-xs">Varians pengeluaran</p>
                                <p className="text-sm">{formatRupiah(budget.varianceExpense ?? 0)}</p>
                            </div>
                        </div>
                    )}
                </section>

                <section className="space-y-3">
                    <h2 className="font-semibold">Panitia</h2>
                    <ul className="space-y-1 text-sm">
                        {committees.map((committee) => (
                            <li key={committee.id} className="flex items-center justify-between">
                                <span>
                                    {committee.name}
                                    {committee.roleTitle && <span className="text-muted-foreground"> — {committee.roleTitle}</span>}
                                </span>
                                {canManage && (
                                    <Button variant="ghost" size="sm" onClick={() => removeCommittee(committee.id)}>
                                        Hapus
                                    </Button>
                                )}
                            </li>
                        ))}
                    </ul>
                    {canManage && <AddCommitteeForm eventId={event.id} members={members} />}
                </section>

                <section className="space-y-3">
                    <h2 className="font-semibold">Tugas</h2>
                    <ul className="space-y-2 text-sm">
                        {tasks.map((task) => (
                            <li key={task.id} className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="font-medium">{task.title}</p>
                                        <p className="text-muted-foreground text-xs">
                                            {task.assignee ? task.assignee.name : 'Belum ditugaskan'} · {task.priorityLabel}
                                            {task.dueDate && ` · Tenggat ${formatDateTime(task.dueDate)}`}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Select value={task.status} onValueChange={(value) => updateTaskStatus(task.id, value)}>
                                            <SelectTrigger className="w-40">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {TASK_STATUSES.map((option) => (
                                                    <SelectItem key={option.value} value={option.value}>
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {canManage && (
                                            <Button variant="ghost" size="sm" onClick={() => deleteTask(task.id)}>
                                                Hapus
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                    {canManage && <AddTaskForm eventId={event.id} members={members} priorities={taskPriorities} />}
                </section>

                <section className="space-y-3">
                    <h2 className="font-semibold">Peserta</h2>
                    <ul className="space-y-1 text-sm">
                        {participants.map((participant) => (
                            <li key={participant.id} className="flex items-center justify-between">
                                <span>{participant.name}</span>
                                {(canManage || participant.membershipId === currentMembershipId) && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() =>
                                            canManage ? removeParticipant(participant.id) : cancelParticipation(participant.id)
                                        }
                                    >
                                        Batalkan
                                    </Button>
                                )}
                            </li>
                        ))}
                    </ul>
                    {currentMembershipId && !myParticipant && (
                        <Button onClick={register} size="sm">
                            Daftar sebagai peserta
                        </Button>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
