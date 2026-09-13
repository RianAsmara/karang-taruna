import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { CheckCircle2, X, XCircle } from 'lucide-react';
import { useEffect, useState } from 'react';

/**
 * Renders `flash.success` / `flash.error` shared by HandleInertiaRequests.
 * Mounted once in the authenticated layout, so any controller can flash a
 * message and have it actually reach the user — before this, five different
 * `->with('error'|'success')` calls were silently dropped.
 */
export function FlashMessages() {
    const { flash } = usePage<SharedData>().props;
    const message = flash?.error ?? flash?.success ?? null;
    const isError = Boolean(flash?.error);

    const [dismissed, setDismissed] = useState(false);

    // A new message after a redirect must reappear even if the previous one
    // was dismissed — keyed on the text itself, since that is what changes.
    useEffect(() => {
        setDismissed(false);
    }, [message]);

    if (!message || dismissed) {
        return null;
    }

    const Icon = isError ? XCircle : CheckCircle2;

    return (
        <div
            // Status, not colour alone: the icon and wording carry the meaning
            // too, so it survives colourblindness and a bad screen outdoors.
            role={isError ? 'alert' : 'status'}
            aria-live={isError ? 'assertive' : 'polite'}
            className={`mx-4 mt-4 flex items-start gap-3 rounded-xl border p-3 text-sm ${
                isError
                    ? 'border-destructive/40 bg-destructive/10 text-destructive'
                    : 'border-emerald-600/40 bg-emerald-600/10 text-emerald-700 dark:text-emerald-400'
            }`}
        >
            <Icon className="mt-0.5 size-4 shrink-0" aria-hidden />
            <p className="flex-1">{message}</p>
            <button
                type="button"
                onClick={() => setDismissed(true)}
                aria-label="Tutup pesan"
                className="shrink-0 opacity-70 hover:opacity-100"
            >
                <X className="size-4" />
            </button>
        </div>
    );
}
