import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dokumen', href: '/documents' }];

interface Option {
    value: string;
    label: string;
}

interface DocumentItem {
    id: string;
    title: string;
    category: string;
    categoryLabel: string;
    originalName: string;
    sizeBytes: number;
    uploaderName: string;
    eventTitle: string | null;
    canDelete: boolean;
    createdAt: string;
}

function formatFileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function monthLabel(iso: string): string {
    return new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date(iso));
}

export default function DocumentsIndex({ documents, categories, canCreate }: { documents: DocumentItem[]; categories: Option[]; canCreate: boolean }) {
    const [activeCategory, setActiveCategory] = useState<string | null>(null);

    const filtered = activeCategory ? documents.filter((doc) => doc.category === activeCategory) : documents;

    const groups = filtered.reduce<Record<string, DocumentItem[]>>((acc, doc) => {
        const key = monthLabel(doc.createdAt);
        acc[key] ??= [];
        acc[key].push(doc);
        return acc;
    }, {});

    const destroy = (id: string) => router.delete(route('documents.destroy', id));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dokumen" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Dokumen</h1>
                    {canCreate && (
                        <Button asChild>
                            <Link href={route('documents.create')}>Unggah dokumen</Link>
                        </Button>
                    )}
                </div>

                <div className="flex flex-wrap gap-2">
                    <Badge
                        variant={activeCategory === null ? 'default' : 'outline'}
                        className="cursor-pointer"
                        onClick={() => setActiveCategory(null)}
                    >
                        Semua
                    </Badge>
                    {categories.map((category) => (
                        <Badge
                            key={category.value}
                            variant={activeCategory === category.value ? 'default' : 'outline'}
                            className="cursor-pointer"
                            onClick={() => setActiveCategory(category.value)}
                        >
                            {category.label}
                        </Badge>
                    ))}
                </div>

                {filtered.length === 0 ? (
                    <p className="text-muted-foreground text-sm">Belum ada dokumen.</p>
                ) : (
                    Object.entries(groups).map(([month, items]) => (
                        <section key={month} className="space-y-3">
                            <h2 className="text-muted-foreground text-sm font-medium">{month}</h2>
                            <div className="grid gap-3">
                                {items.map((doc) => (
                                    <div
                                        key={doc.id}
                                        className="border-sidebar-border/70 dark:border-sidebar-border flex items-center justify-between rounded-xl border p-4"
                                    >
                                        <div className="min-w-0">
                                            <p className="font-medium">{doc.title}</p>
                                            <p className="text-muted-foreground text-sm">
                                                {doc.categoryLabel} · Diunggah {doc.uploaderName} · {formatFileSize(doc.sizeBytes)}
                                                {doc.eventTitle && ` · ${doc.eventTitle}`}
                                            </p>
                                        </div>
                                        <div className="flex shrink-0 items-center gap-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <a href={route('documents.download', doc.id)}>Unduh</a>
                                            </Button>
                                            {doc.canDelete && (
                                                <Dialog>
                                                    <DialogTrigger asChild>
                                                        <Button variant="ghost" size="sm">
                                                            Hapus
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent>
                                                        <DialogTitle>Hapus {doc.title}?</DialogTitle>
                                                        <DialogDescription>Tindakan ini tidak bisa dibatalkan.</DialogDescription>
                                                        <DialogFooter>
                                                            <DialogClose asChild>
                                                                <Button variant="secondary">Batal</Button>
                                                            </DialogClose>
                                                            <DialogClose asChild>
                                                                <Button variant="destructive" onClick={() => destroy(doc.id)}>
                                                                    Hapus
                                                                </Button>
                                                            </DialogClose>
                                                        </DialogFooter>
                                                    </DialogContent>
                                                </Dialog>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>
                    ))
                )}
            </div>
        </AppLayout>
    );
}
