import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

interface VoteOption {
    id: string;
    label: string;
}

interface VoteData {
    id: string;
    question: string;
    description: string | null;
    anonymous: boolean;
    editable: boolean;
    maxSelections: number;
    endAt: string;
    isOpen: boolean;
    isEligible: boolean;
    hasResponded: boolean;
    participationCount: number;
    eligibleCount: number;
    options: VoteOption[];
    myOptionIds: string[];
    event: { id: string; title: string } | null;
}

interface VoteResultOption {
    id: string;
    label: string;
    count: number;
    percent: number | null;
}

interface VoteResultData {
    participationCount: number;
    eligibleCount: number;
    options: VoteResultOption[];
    winningOptionIds: string[];
    isTie: boolean;
    breakdown: { id: string; label: string; members: { id: string; name: string }[] }[] | null;
}

interface VoteShowProps {
    vote: VoteData;
    canRespond: boolean;
    results: VoteResultData | null;
}

function optionLabel(vote: VoteData, ids: string[]): string {
    return vote.options
        .filter((option) => ids.includes(option.id))
        .map((option) => option.label)
        .join(', ');
}

export default function VoteShow({ vote, canRespond, results }: VoteShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Voting', href: '/votes' },
        { title: vote.question, href: route('votes.show', vote.id) },
    ];

    const [editing, setEditing] = useState(false);
    const [selected, setSelected] = useState<string[]>(vote.myOptionIds);

    const toggleOption = (optionId: string) => {
        setSelected((current) => {
            if (current.includes(optionId)) {
                return current.filter((id) => id !== optionId);
            }
            if (vote.maxSelections === 1) {
                return [optionId];
            }
            if (current.length >= vote.maxSelections) {
                return current;
            }
            return [...current, optionId];
        });
    };

    const submit = () => {
        router.post(
            route('votes.responses.store', vote.id),
            { option_ids: selected },
            { preserveScroll: true, onSuccess: () => setEditing(false) },
        );
    };

    const editabilitySentence = vote.editable ? 'Bisa diubah sampai voting ditutup.' : 'Tidak bisa diubah setelah dikirim.';
    const isRespondingNow = vote.isOpen && canRespond && (!vote.hasResponded || editing);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={vote.question} />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="max-w-xl space-y-1">
                    <div className="flex items-center gap-2">
                        <h1 className="text-xl font-semibold">{vote.question}</h1>
                        <Badge variant={vote.isOpen ? 'default' : 'secondary'}>{vote.isOpen ? 'Berjalan' : 'Ditutup'}</Badge>
                    </div>
                    {vote.description && <p className="text-muted-foreground text-sm">{vote.description}</p>}
                    {vote.event && <p className="text-muted-foreground text-sm">Kegiatan: {vote.event.title}</p>}
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border max-w-xl space-y-1 rounded-lg border-l-2 bg-muted/30 p-3 text-sm">
                    <p>{vote.anonymous ? 'Pilihan Anda tidak akan terlihat siapa pun.' : 'Pilihan Anda terlihat oleh pengurus.'}</p>
                    <p>{editabilitySentence}</p>
                    <p>Ditutup {formatDateTime(vote.endAt)}</p>
                </div>

                {!vote.isEligible ? (
                    <p className="text-muted-foreground max-w-xl text-sm">Voting ini hanya untuk pengurus.</p>
                ) : isRespondingNow ? (
                    <div className="max-w-xl space-y-4">
                        {vote.maxSelections > 1 && (
                            <p className="text-muted-foreground text-sm">Pilih maksimal {vote.maxSelections}</p>
                        )}
                        <div className="grid gap-2">
                            {vote.options.map((option) => (
                                <label
                                    key={option.id}
                                    className="border-sidebar-border/70 dark:border-sidebar-border flex items-center gap-3 rounded-lg border p-3 text-sm"
                                >
                                    <Checkbox checked={selected.includes(option.id)} onCheckedChange={() => toggleOption(option.id)} />
                                    {option.label}
                                </label>
                            ))}
                        </div>

                        <div className="flex gap-2">
                            <Dialog>
                                <DialogTrigger asChild>
                                    <Button disabled={selected.length === 0}>Kirim pilihan</Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogTitle>Kirim pilihan Anda?</DialogTitle>
                                    <DialogDescription>
                                        {optionLabel(vote, selected)}. {editabilitySentence}
                                    </DialogDescription>
                                    <DialogFooter>
                                        <DialogClose asChild>
                                            <Button variant="secondary">Batal</Button>
                                        </DialogClose>
                                        <DialogClose asChild>
                                            <Button onClick={submit}>Kirim</Button>
                                        </DialogClose>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                            {editing && (
                                <Button variant="outline" onClick={() => setEditing(false)}>
                                    Batal
                                </Button>
                            )}
                        </div>
                    </div>
                ) : (
                    <div className="max-w-xl space-y-3">
                        {vote.hasResponded && (
                            <div className="grid gap-2">
                                {vote.options.map((option) => (
                                    <div
                                        key={option.id}
                                        className="border-sidebar-border/70 dark:border-sidebar-border flex items-center justify-between rounded-lg border p-3 text-sm"
                                    >
                                        {option.label}
                                        {vote.myOptionIds.includes(option.id) && <Badge variant="secondary">Pilihan Anda</Badge>}
                                    </div>
                                ))}
                            </div>
                        )}

                        {vote.isOpen && !vote.hasResponded && (
                            <p className="text-muted-foreground text-sm">Voting belum bisa diikuti saat ini.</p>
                        )}

                        {vote.isOpen && vote.hasResponded && vote.editable && (
                            <Button variant="outline" size="sm" onClick={() => setEditing(true)}>
                                Ubah pilihan
                            </Button>
                        )}

                        {!vote.isOpen && <p className="text-muted-foreground text-sm">Voting sudah ditutup.</p>}
                    </div>
                )}

                {results && (
                    <div className="max-w-xl space-y-3 border-t pt-4">
                        <h2 className="text-lg font-semibold">Hasil</h2>
                        <p className="text-muted-foreground text-sm">
                            {results.participationCount} dari {results.eligibleCount} memilih
                        </p>

                        <div className="grid gap-2">
                            {results.options.map((option) => (
                                <div key={option.id} className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3 text-sm">
                                    <div className="flex items-center justify-between">
                                        <span className="font-medium">{option.label}</span>
                                        {results.winningOptionIds.includes(option.id) && (
                                            <Badge>{results.isTie ? 'Seri' : 'Menang'}</Badge>
                                        )}
                                    </div>
                                    <p className="text-muted-foreground">
                                        {option.count} suara{option.percent !== null && ` · ${option.percent}%`}
                                    </p>
                                </div>
                            ))}
                        </div>

                        <p className="text-muted-foreground text-xs">
                            {vote.anonymous
                                ? 'Voting ini anonim. Daftar pemilih tidak disimpan.'
                                : 'Pengurus dapat melihat siapa memilih apa.'}
                        </p>
                        {results.options.every((option) => option.percent === null) && results.participationCount > 0 && (
                            <p className="text-muted-foreground text-xs">Terlalu sedikit suara untuk ditampilkan sebagai persen.</p>
                        )}

                        {results.breakdown && (
                            <div className="space-y-2">
                                <h3 className="text-muted-foreground text-sm font-medium">Siapa memilih apa</h3>
                                {results.breakdown.map((group) => (
                                    <div key={group.id} className="text-sm">
                                        <p className="font-medium">{group.label}</p>
                                        <p className="text-muted-foreground">
                                            {group.members.length === 0 ? '—' : group.members.map((member) => member.name).join(', ')}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
