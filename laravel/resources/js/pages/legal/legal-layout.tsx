import { Head, Link } from '@inertiajs/react';
import { type ReactNode } from 'react';

/**
 * Plain reading layout for the two legal pages. Deliberately outside the
 * app shell: both must be readable by someone with no account, since a
 * person has to be able to read what they are agreeing to *before* they
 * agree to it.
 */
export function LegalLayout({ title, updated, children }: { title: string; updated: string; children: ReactNode }) {
    return (
        <>
            <Head title={title} />
            <div className="min-h-screen bg-[#FDFDFC] px-6 py-12 text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <article className="mx-auto max-w-2xl">
                    <Link href={route('home')} className="text-muted-foreground text-sm hover:underline">
                        ← RukunMuda
                    </Link>
                    <h1 className="mt-6 text-2xl font-semibold">{title}</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Terakhir diperbarui: {updated}</p>
                    <div className="mt-8 space-y-6 text-sm leading-relaxed [&_h2]:mt-8 [&_h2]:text-base [&_h2]:font-semibold [&_li]:mt-1 [&_ul]:list-disc [&_ul]:pl-5">
                        {children}
                    </div>
                    <p className="text-muted-foreground mt-12 text-xs">
                        <Link href={route('legal.privacy')} className="hover:underline">
                            Kebijakan Privasi
                        </Link>
                        {' · '}
                        <Link href={route('legal.terms')} className="hover:underline">
                            Syarat Penggunaan
                        </Link>
                    </p>
                </article>
            </div>
        </>
    );
}
