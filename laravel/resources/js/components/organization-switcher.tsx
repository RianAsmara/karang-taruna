import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { Link, router } from '@inertiajs/react';
import { Building2, Check, Plus } from 'lucide-react';
import { useState } from 'react';

interface OrganizationOption {
    id: string;
    name: string;
    roleLabel: string;
}

/**
 * The org list is fetched on demand (not a shared prop on every page)
 * since a single-org user, the common case, never needs it loaded.
 */
export function OrganizationSwitcherItem({ currentOrganizationId, onNavigate }: { currentOrganizationId: string | null; onNavigate: () => void }) {
    const [organizations, setOrganizations] = useState<OrganizationOption[] | null>(null);
    // Server-decided, not re-derived here: an ordinary member may not create
    // a second organization (OrganizationPolicy::create).
    const [canCreate, setCanCreate] = useState(false);
    const [open, setOpen] = useState(false);

    const load = async () => {
        if (organizations !== null) {
            setOpen((value) => !value);
            return;
        }

        const response = await fetch(route('organizations.options'), { headers: { Accept: 'application/json' } });
        const body = await response.json();
        setOrganizations(body.organizations as OrganizationOption[]);
        setCanCreate(Boolean(body.canCreate));
        setOpen(true);
    };

    const switchTo = (organizationId: string) => {
        onNavigate();
        router.post(route('organizations.switch'), { organization_id: organizationId });
    };

    return (
        <>
            <DropdownMenuItem onSelect={(e) => e.preventDefault()} onClick={load}>
                <Building2 className="mr-2" />
                Ganti organisasi
            </DropdownMenuItem>
            {open && organizations && (
                <div className="px-1 pb-1">
                    {organizations.map((org) => (
                        <button
                            key={org.id}
                            type="button"
                            onClick={() => switchTo(org.id)}
                            className="hover:bg-accent flex w-full items-center justify-between rounded-sm px-2 py-1.5 text-left text-sm"
                        >
                            <span>
                                {org.name} <span className="text-muted-foreground text-xs">· {org.roleLabel}</span>
                            </span>
                            {org.id === currentOrganizationId && <Check className="size-4" />}
                        </button>
                    ))}
                    {canCreate && (
                        <Link
                            href={route('organizations.create')}
                            className="hover:bg-accent text-muted-foreground flex w-full items-center rounded-sm px-2 py-1.5 text-left text-sm"
                        >
                            <Plus className="mr-2 size-4" />
                            Buat organisasi baru
                        </Link>
                    )}
                </div>
            )}
        </>
    );
}
