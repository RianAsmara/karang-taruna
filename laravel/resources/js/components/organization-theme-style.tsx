import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

/**
 * Applies the org's one brand color across the actual app UI — primary
 * buttons/badges and the sidebar's active item — not just the Theme
 * Builder page's own preview. Maps RukunMuda's `accent`/`onAccent`
 * tokens onto shadcn's `--primary`/`--sidebar-primary` custom
 * properties, since those (not shadcn's own differently-purposed
 * `--accent`, a neutral hover tint) are what actually render as "the
 * brand color" in this starter kit's components.
 */
export function OrganizationThemeStyle() {
    const { organizationTheme } = usePage<SharedData>().props;

    if (!organizationTheme) return null;

    const { light, dark } = organizationTheme.color;

    const block = (colors: typeof light) => `
        --primary: ${colors.accent};
        --primary-foreground: ${colors.onAccent};
        --sidebar-primary: ${colors.accent};
        --sidebar-primary-foreground: ${colors.onAccent};
        --ring: ${colors.accent};
    `;

    return (
        <style>{`
            :root { ${block(light)} }
            .dark { ${block(dark)} }
        `}</style>
    );
}
