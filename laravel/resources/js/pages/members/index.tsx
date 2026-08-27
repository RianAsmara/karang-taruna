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
}

interface MembersIndexProps {
    members: Member[];
    roles: RoleOption[];
    canManageMembers: boolean;
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

export default function MembersIndex({ members, roles, canManageMembers, filters }: MembersIndexProps) {
    const updateRole = (member: Member, role: string) => {
        router.patch(route('members.update-role', member.id), { role }, { preserveScroll: true });
    };

    const removeMember = (member: Member) => {
        router.delete(route('members.destroy', member.id), { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Anggota" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Anggota</h1>

                {canManageMembers && <AddMemberForm roles={roles} />}

                <SearchInput initialValue={filters.search} placeholder="Cari nama atau email…" />

                <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border">
                    <table className="w-full text-left text-sm">
                        <thead className="text-muted-foreground border-b">
                            <tr>
                                <th className="px-4 py-2 font-medium">Nama</th>
                                <th className="px-4 py-2 font-medium">Email</th>
                                <th className="px-4 py-2 font-medium">Peran</th>
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
            </div>
        </AppLayout>
    );
}
