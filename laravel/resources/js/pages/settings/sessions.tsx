import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { formatDateTime } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Perangkat',
        href: '/settings/sessions',
    },
];

interface SessionRow {
    id: string;
    deviceName: string;
    lastUsedAt: string | null;
    createdAt: string;
    isCurrent: boolean;
}

function revokeSession(id: string) {
    router.delete(route('sessions.destroy', id), { preserveScroll: true });
}

export default function Sessions({ sessions }: { sessions: SessionRow[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Perangkat" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Perangkat yang masuk"
                        description="Kelola sesi aplikasi mobile Anda dari sini. Sesi peramban yang sedang Anda gunakan tidak tercantum di daftar ini."
                    />

                    {sessions.length === 0 ? (
                        <p className="text-muted-foreground text-sm">Belum ada perangkat mobile yang masuk.</p>
                    ) : (
                        <ul className="space-y-2">
                            {sessions.map((session) => (
                                <li
                                    key={session.id}
                                    className="border-sidebar-border/70 dark:border-sidebar-border flex items-center justify-between rounded-lg border p-3 text-sm"
                                >
                                    <div>
                                        <p className="font-medium">{session.deviceName}</p>
                                        <p className="text-muted-foreground">
                                            {session.lastUsedAt ? `Terakhir aktif ${formatDateTime(session.lastUsedAt)}` : 'Belum pernah digunakan'}
                                            {' · '}
                                            Masuk {formatDateTime(session.createdAt)}
                                        </p>
                                    </div>

                                    <Dialog>
                                        <DialogTrigger asChild>
                                            <Button variant="outline" size="sm">
                                                Keluar
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogTitle>Keluarkan perangkat ini?</DialogTitle>
                                            <DialogDescription>
                                                "{session.deviceName}" tidak akan bisa mengakses akun Anda lagi sampai masuk kembali.
                                            </DialogDescription>
                                            <DialogFooter>
                                                <DialogClose asChild>
                                                    <Button variant="secondary">Batal</Button>
                                                </DialogClose>
                                                <DialogClose asChild>
                                                    <Button variant="destructive" onClick={() => revokeSession(session.id)}>
                                                        Keluarkan
                                                    </Button>
                                                </DialogClose>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
