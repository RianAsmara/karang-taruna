import { Input } from '@/components/ui/input';
import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

type Props = {
    initialValue: string | null;
    placeholder: string;
    /** Extra query params to preserve alongside `search` (e.g. transactions' `event_id`). */
    extraParams?: Record<string, string>;
};

/**
 * Debounced search box that updates the current URL's `?search=` param via
 * a GET visit — shared by events/members/transactions index pages, the
 * only three lists with search per the backlog (no date-range/multi-field
 * filtering was asked for).
 */
export function SearchInput({ initialValue, placeholder, extraParams }: Props) {
    const [value, setValue] = useState(initialValue ?? '');
    const isFirstRender = useRef(true);

    useEffect(() => {
        // Skip the debounce on mount — `value` already matches the URL
        // that produced this page, so there is nothing new to navigate to.
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        const handle = setTimeout(() => {
            router.get(
                window.location.pathname,
                { ...extraParams, ...(value ? { search: value } : {}) },
                { preserveState: true, replace: true, preserveScroll: true },
            );
        }, 350);

        return () => clearTimeout(handle);
        // extraParams is an object literal recreated every render at the
        // call site — including it here would reset the debounce on every
        // unrelated re-render, not just when its actual values change.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [value]);

    return <Input value={value} onChange={(e) => setValue(e.target.value)} placeholder={placeholder} className="max-w-xs" />;
}
