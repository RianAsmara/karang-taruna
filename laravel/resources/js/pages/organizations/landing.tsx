import { formatDate, formatDateTime } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

interface UpcomingEvent {
    title: string;
    startAt: string;
    location: string | null;
}

interface AnnouncementPreview {
    title: string;
    publishedAt: string;
    excerpt: string;
}

interface OrganizationLandingProps {
    organization: {
        name: string;
        memberCount: number;
    };
    upcomingEvents: UpcomingEvent[];
    announcements: AnnouncementPreview[];
}

export default function OrganizationLanding({ organization, upcomingEvents, announcements }: OrganizationLandingProps) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title={organization.name} />
            <div className="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="mx-auto flex max-w-3xl items-center justify-between p-6">
                    <span className="text-sm font-medium">RukunMuda</span>
                    <Link
                        href={auth.user ? route('dashboard') : route('login')}
                        className="rounded-sm border border-[#19140035] px-4 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                    >
                        {auth.user ? 'Dashboard' : 'Masuk'}
                    </Link>
                </header>

                <main className="mx-auto max-w-3xl px-6 pb-16">
                    <h1 className="text-3xl font-semibold">{organization.name}</h1>
                    <p className="text-muted-foreground mt-1 text-sm">{organization.memberCount} anggota</p>

                    <section className="mt-10">
                        <h2 className="text-lg font-semibold">Kegiatan mendatang</h2>
                        {upcomingEvents.length === 0 ? (
                            <p className="text-muted-foreground mt-2 text-sm">Belum ada kegiatan yang dijadwalkan.</p>
                        ) : (
                            <ul className="mt-3 space-y-3">
                                {upcomingEvents.map((event, index) => (
                                    <li key={index} className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                                        <p className="font-medium">{event.title}</p>
                                        <p className="text-muted-foreground text-sm">
                                            {formatDateTime(event.startAt)}
                                            {event.location && ` · ${event.location}`}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="mt-10">
                        <h2 className="text-lg font-semibold">Pengumuman</h2>
                        {announcements.length === 0 ? (
                            <p className="text-muted-foreground mt-2 text-sm">Belum ada pengumuman.</p>
                        ) : (
                            <ul className="mt-3 space-y-3">
                                {announcements.map((announcement, index) => (
                                    <li key={index} className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                                        <p className="font-medium">{announcement.title}</p>
                                        <p className="text-muted-foreground text-xs">{formatDate(announcement.publishedAt)}</p>
                                        <p className="mt-2 text-sm">{announcement.excerpt}</p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </main>
            </div>
        </>
    );
}
