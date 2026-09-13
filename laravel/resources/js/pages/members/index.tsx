import InputError from '@/components/input-error';
import { SearchInput } from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Anggota', href: '/members' }];

interface RoleOption {
    value: string;
    label: string;
}

interface Member {
    id: string;
    name: string;
    email: string;
    role: string;
    roleLabel: string;
    joinedAt: string;
    isChair: boolean;
    activityPoints: number;
}

interface Invite {
    id: string;
    url: string;
    expiresAt: string;
    uses: number;
    maxUses: number | null;
}

interface ExitRequest {
    id: string;
    memberName: string | null;
    memberRoleLabel: string;
    reason: string | null;
    createdAt: string;
}

interface MembersIndexProps {
    members: Member[];
    roles: RoleOption[];
    canManageMembers: boolean;
    /** Active join links. Empty for everyone but the chair. */
    invites: Invite[];
    /** Pending "keluar" requests awaiting this chair's decision. Empty for everyone else. */
    exitRequests: ExitRequest[];
    myExitRequestPending: boolean;
    canRequestExit: boolean;
    filters: { search: string | null };
}

function AddMemberForm({ roles }: { roles: RoleOption[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        role: roles.find((r) => r.value === 'ANGGOTA')?.value ?? roles[0]?.value ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('members.store'), { onSuccess: () => reset('email') });
    };

    return (
        <form onSubmit={submit} className="border-sidebar-border/70 dark:border-sidebar-border flex flex-wrap items-end gap-4 rounded-xl border p-4">
            <div className="grid gap-2">
                <Label htmlFor="email">Email anggota</Label>
                <Input
                    id="email"
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    placeholder="anggota@contoh.com"
                    className="w-64"
                />
                <InputError message={errors.email} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="role">Peran</Label>
                <Select value={data.role} onValueChange={(value) => setData('role', value)}>
                    <SelectTrigger id="role" className="w-48">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {roles
                            .filter((role) => role.value !== 'OWNER')
                            .map((role) => (
                                <SelectItem key={role.value} value={role.value}>
                                    {role.label}
                                </SelectItem>
                            ))}
                    </SelectContent>
                </Select>
            </div>

            <Button type="submit" disabled={processing}>
                Tambah anggota
            </Button>
        </form>
    );
}

export default function MembersIndex({
    members,
    roles,
    canManageMembers,
    invites,
    exitRequests,
    myExitRequestPending,
    canRequestExit,
    filters,
}: MembersIndexProps) {
    const updateRole = (member: Member, role: string) => {
        router.patch(route('members.update-role', member.id), { role }, { preserveScroll: true });
    };

    const removeMember = (member: Member) => {
        router.delete(route('members.destroy', member.id), { preserveScroll: true });
    };

    const createInvite = () => {
        router.post(route('organizations.invites.store'), {}, { preserveScroll: true });
    };

    const revokeInvite = (invite: Invite) => {
        router.delete(route('organizations.invites.destroy', invite.id), { preserveScroll: true });
    };

    const copyInvite = async (invite: Invite) => {
        try {
            await navigator.clipboard.writeText(invite.url);
        } catch {
            // Clipboard is unavailable over plain HTTP and in some browsers —
            // the link is shown in full below, so it stays copyable by hand.
        }
    };

    const requestExit = () => {
        router.post(route('membership.exit-requests.store'), {}, { preserveScroll: true });
    };

    const decideExit = (exitRequest: ExitRequest, approve: boolean) => {
        router.post(route('membership.exit-requests.decide', exitRequest.id), { approve }, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Anggota" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Anggota</h1>

                {canManageMembers && <AddMemberForm roles={roles} />}

                {canManageMembers && (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h2 className="font-medium">Undang anggota</h2>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    Bagikan tautan ini di grup WhatsApp. Siapa pun yang membukanya akan bergabung sebagai anggota.
                                </p>
                            </div>
                            <Button size="sm" onClick={createInvite}>
                                Buat tautan undangan
                            </Button>
                        </div>

                        {invites.length === 0 ? (
                            <p className="text-muted-foreground mt-3 text-sm">Belum ada tautan undangan aktif.</p>
                        ) : (
                            <ul className="mt-3 flex flex-col gap-2">
                                {invites.map((invite) => (
                                    <li key={invite.id} className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3 text-sm">
                                        <div className="min-w-0">
                                            <code className="break-all">{invite.url}</code>
                                            <p className="text-muted-foreground mt-1">
                                                Berlaku sampai {new Date(invite.expiresAt).toLocaleDateString('id-ID', { dateStyle: 'long' })} ·{' '}
                                                {invite.uses}
                                                {invite.maxUses === null ? ' kali dipakai' : ` dari ${invite.maxUses} kali dipakai`}
                                            </p>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button size="sm" variant="outline" onClick={() => copyInvite(invite)}>
                                                Salin
                                            </Button>
                                            <Button size="sm" variant="ghost" onClick={() => revokeInvite(invite)}>
                                                Cabut
                                            </Button>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                )}

                {exitRequests.length > 0 && (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                        <h2 className="font-medium">Permintaan keluar</h2>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Anggota berikut meminta keluar dari organisasi. Keanggotaan mereka tetap aktif sampai Anda menyetujui.
                        </p>
                        <ul className="mt-3 flex flex-col gap-2">
                            {exitRequests.map((exitRequest) => (
                                <li
                                    key={exitRequest.id}
                                    className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3 text-sm"
                                >
                                    <div>
                                        <span className="font-medium">{exitRequest.memberName}</span>{' '}
                                        <span className="text-muted-foreground">· {exitRequest.memberRoleLabel}</span>
                                        {exitRequest.reason && <p className="text-muted-foreground mt-1">{exitRequest.reason}</p>}
                                    </div>
                                    <div className="flex gap-2">
                                        <Button size="sm" variant="outline" onClick={() => decideExit(exitRequest, false)}>
                                            Tolak
                                        </Button>
                                        <Button size="sm" onClick={() => decideExit(exitRequest, true)}>
                                            Setujui
                                        </Button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                <SearchInput initialValue={filters.search} placeholder="Cari nama atau email…" />

                <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border">
                    <table className="w-full text-left text-sm">
                        <thead className="text-muted-foreground border-b">
                            <tr>
                                <th className="px-4 py-2 font-medium">Nama</th>
                                <th className="px-4 py-2 font-medium">Email</th>
                                <th className="px-4 py-2 font-medium">Peran</th>
                                <th className="px-4 py-2 font-medium">Poin</th>
                                {canManageMembers && <th className="px-4 py-2 font-medium">Aksi</th>}
                            </tr>
                        </thead>
                        <tbody>
                            {members.map((member) => (
                                <tr key={member.id} className="border-b last:border-0">
                                    <td className="px-4 py-2">{member.name}</td>
                                    <td className="px-4 py-2">{member.email}</td>
                                    <td className="px-4 py-2">
                                        {canManageMembers && !member.isChair ? (
                                            <Select value={member.role} onValueChange={(value) => updateRole(member, value)}>
                                                <SelectTrigger className="w-40">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {roles
                                                        .filter((role) => role.value !== 'KETUA')
                                                        .map((role) => (
                                                            <SelectItem key={role.value} value={role.value}>
                                                                {role.label}
                                                            </SelectItem>
                                                        ))}
                                                </SelectContent>
                                            </Select>
                                        ) : (
                                            member.roleLabel
                                        )}
                                    </td>
                                    <td className="px-4 py-2 tabular-nums">{member.activityPoints}</td>
                                    {canManageMembers && (
                                        <td className="px-4 py-2">
                                            {!member.isChair && (
                                                <Button variant="ghost" size="sm" onClick={() => removeMember(member)}>
                                                    Keluarkan
                                                </Button>
                                            )}
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {myExitRequestPending ? (
                    <p className="text-muted-foreground text-sm">
                        Permintaan keluar Anda sedang menunggu persetujuan ketua.
                    </p>
                ) : canRequestExit ? (
                    <div>
                        <Button variant="ghost" size="sm" onClick={requestExit}>
                            Keluar dari organisasi
                        </Button>
                    </div>
                ) : null}
            </div>
        </AppLayout>
    );
}
