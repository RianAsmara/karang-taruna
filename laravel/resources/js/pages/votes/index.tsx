import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Voting', href: '/votes' }];

interface VoteListItem {
    id: string;
    question: string;
    isOpen: boolean;
    endAt: string;
    participationCount: number;
    eligibleCount: number;
}

export default function VotesIndex({ votes }: { votes: VoteListItem[] }) {
    const open = votes.filter((vote) => vote.isOpen);
    const closed = votes.filter((vote) => !vote.isOpen);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Voting" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-xl font-semibold">Voting</h1>

                {votes.length === 0 ? (
                    <p className="text-muted-foreground text-sm">Belum ada voting.</p>
                ) : (
                    <>
                        <section className="space-y-3">
                            <h2 className="text-muted-foreground text-sm font-medium">Sedang berjalan</h2>
                            {open.length === 0 ? (
                                <p className="text-muted-foreground text-sm">Tidak ada voting yang berjalan.</p>
                            ) : (
                                <div className="grid gap-3">
                                    {open.map((vote) => (
                                        <Link
                                            key={vote.id}
                                            href={route('votes.show', vote.id)}
                                            className="border-sidebar-border/70 dark:border-sidebar-border hover:bg-muted/50 flex items-center justify-between rounded-xl border p-4 transition-colors"
                                        >
                                            <div>
                                                <p className="font-medium">{vote.question}</p>
                                                <p className="text-muted-foreground text-sm">
                                                    Tutup {formatDateTime(vote.endAt)} · {vote.participationCount} dari {vote.eligibleCount}{' '}
                                                    sudah memilih
                                                </p>
                                            </div>
                                            <Badge>Berjalan</Badge>
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </section>

                        {closed.length > 0 && (
                            <section className="space-y-3">
                                <h2 className="text-muted-foreground text-sm font-medium">Sudah ditutup</h2>
                                <div className="grid gap-3">
                                    {closed.map((vote) => (
                                        <Link
                                            key={vote.id}
                                            href={route('votes.show', vote.id)}
                                            className="border-sidebar-border/70 dark:border-sidebar-border hover:bg-muted/50 flex items-center justify-between rounded-xl border p-4 transition-colors"
                                        >
                                            <div>
                                                <p className="font-medium">{vote.question}</p>
                                                <p className="text-muted-foreground text-sm">Ditutup {formatDateTime(vote.endAt)}</p>
                                            </div>
                                            <Badge variant="secondary">Ditutup</Badge>
                                        </Link>
                                    ))}
                                </div>
                            </section>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
