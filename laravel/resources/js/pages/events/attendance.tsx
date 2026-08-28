import AppLayout from '@/layouts/app-layout';
import { formatDateTime } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface AttendanceRow {
    id: string;
    name: string;
    method: string;
    methodLabel: string;
    checkedInAt: string;
}

interface EventAttendanceProps {
    event: { id: string; title: string };
    attendances: AttendanceRow[];
}

export default function EventAttendance({ event, attendances }: EventAttendanceProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kegiatan', href: '/events' },
        { title: event.title, href: route('events.show', event.id) },
        { title: 'Daftar hadir', href: route('events.attendance.index', event.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Daftar hadir · ${event.title}`} />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div>
                    <h1 className="text-xl font-semibold">Daftar hadir</h1>
                    <p className="text-muted-foreground text-sm">
                        {event.title} · {attendances.length} orang
                    </p>
                </div>

                {attendances.length === 0 ? (
                    <p className="text-muted-foreground text-sm">Belum ada yang mengonfirmasi kehadiran.</p>
                ) : (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border">
                        <table className="w-full text-left text-sm">
                            <thead className="text-muted-foreground border-b">
                                <tr>
                                    <th className="px-4 py-2 font-medium">Nama</th>
                                    <th className="px-4 py-2 font-medium">Metode</th>
                                    <th className="px-4 py-2 font-medium">Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                {attendances.map((attendance) => (
                                    <tr key={attendance.id} className="border-b last:border-0">
                                        <td className="px-4 py-2">{attendance.name}</td>
                                        <td className="px-4 py-2">{attendance.methodLabel}</td>
                                        <td className="px-4 py-2">{formatDateTime(attendance.checkedInAt)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
