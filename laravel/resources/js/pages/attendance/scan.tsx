import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

interface AttendanceScanProps {
    event: { id: string; title: string; statusLabel: string };
    alreadyCheckedIn: boolean;
    canCheckIn: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Presensi', href: '#' }];

export default function AttendanceScan({ event, alreadyCheckedIn, canCheckIn }: AttendanceScanProps) {
    const [processing, setProcessing] = useState(false);

    const confirm = () => {
        setProcessing(true);
        router.post(
            window.location.pathname,
            {},
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Presensi · ${event.title}`} />
            <div className="flex h-full flex-1 flex-col items-center justify-center gap-4 rounded-xl p-4 text-center">
                <p className="text-muted-foreground text-sm">Konfirmasi kehadiran untuk</p>
                <h1 className="text-2xl font-semibold">{event.title}</h1>
                <Badge variant="secondary">{event.statusLabel}</Badge>

                {alreadyCheckedIn ? (
                    <p className="text-muted-foreground mt-4 text-sm">Anda sudah tercatat hadir di kegiatan ini.</p>
                ) : canCheckIn ? (
                    <Button className="mt-4" onClick={confirm} disabled={processing}>
                        Konfirmasi kehadiran saya
                    </Button>
                ) : (
                    <p className="text-muted-foreground mt-4 text-sm">Presensi belum bisa dilakukan untuk kegiatan ini saat ini.</p>
                )}
            </div>
        </AppLayout>
    );
}
