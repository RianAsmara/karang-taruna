// Pure derivation of the six brand-color tokens from one authored primary,
// per docs/design/docs/theme-builder.md. Mirrors app/Support/Theme/ThemeColorEngine.php
// (the server-side, authoritative implementation — this file drives instant
// client-side preview/guard feedback only; nothing here is trusted for what
// actually gets persisted or served to mobile). Both are pinned to the same
// reference-value unit test.
//
// See ThemeColorEngine.php's docblock for the two formula notes (accent200's
// tolerance, and onAccent's lightness-heuristic branch) — not repeated here,
// kept in sync by the shared test fixtures.

import { contrastRatio, hexToOklch, chroma as oklchChroma, oklchToHex } from './oklch';

export type ThemeColors = {
    accent: string;
    accent200: string;
    accent700: string;
    accent800: string;
    onAccent: string;
    onInk: string;
};

export type GuardName = 'accentVsBg' | 'onAccentVsAccent' | 'accent700VsSurface' | 'chromaFloor';

const LIGHT_BG = '#f3f2f2';
const LIGHT_SURFACE = '#ffffff';
const LIGHT_TEXT = '#201e1d';
const DARK_BG = '#1a1918';
const DARK_SURFACE = '#242221';

/** Below this OKLCH lightness, accent is "dark enough" for onAccent = bg; above it, onAccent = text. Tuned so the reference input (L=0.6112) selects bg, matching the spec's own reference value. */
const ON_ACCENT_LIGHTNESS_THRESHOLD = 0.7;

const CHROMA_FLOOR = 0.05;
const GUARD1_MIN_CONTRAST = 3.0;
const GUARD2_MIN_CONTRAST = 4.5;
const GUARD3_MIN_CONTRAST = 4.5;

export function deriveLight(hex: string): ThemeColors {
    const [l, c, h] = hexToOklch(hex);

    return {
        accent: oklchToHex(l, c, h),
        accent200: oklchToHex(0.92, Math.min(c, 0.06), h),
        accent700: oklchToHex(l * 0.78, c * 0.81, h),
        accent800: oklchToHex(l * 0.61, c * 0.65, h),
        onAccent: l < ON_ACCENT_LIGHTNESS_THRESHOLD ? LIGHT_BG : LIGHT_TEXT,
        // Spec: "only if text inversion demands it; in practice stays #f3f2f2".
        onInk: LIGHT_BG,
    };
}

export function deriveDark(hex: string): ThemeColors {
    const [l, c, h] = hexToOklch(hex);
    const lightAccent800 = deriveLight(hex).accent800;

    return {
        accent: oklchToHex(l + 0.09, c * 0.88, h),
        accent200: lightAccent800,
        accent700: oklchToHex(0.78, c * 0.55, h),
        accent800: oklchToHex(0.87, c * 0.38, h),
        onAccent: DARK_BG,
        onInk: DARK_BG,
    };
}

export function derive(hex: string): { light: ThemeColors; dark: ThemeColors } {
    return { light: deriveLight(hex), dark: deriveDark(hex) };
}

/** The four guards from the spec. All must pass to allow save. */
export function checkGuards(hex: string): Record<GuardName, boolean> {
    const light = deriveLight(hex);
    const dark = deriveDark(hex);
    const c = oklchChroma(hex);

    return {
        accentVsBg: contrastRatio(light.accent, LIGHT_BG) >= GUARD1_MIN_CONTRAST && contrastRatio(dark.accent, DARK_BG) >= GUARD1_MIN_CONTRAST,
        // Light mode only — unlike guards 1 and 3, the spec never says "and
        // dark" / "in both modes" for this one. Dark mode's onAccent is
        // unconditionally `dark bg` per the derivation table (no bg-or-text
        // branch to guard there at all).
        onAccentVsAccent: contrastRatio(light.onAccent, light.accent) >= GUARD2_MIN_CONTRAST,
        accent700VsSurface:
            contrastRatio(light.accent700, LIGHT_SURFACE) >= GUARD3_MIN_CONTRAST &&
            contrastRatio(dark.accent700, DARK_SURFACE) >= GUARD3_MIN_CONTRAST,
        chromaFloor: c >= CHROMA_FLOOR,
    };
}

export function passesAllGuards(hex: string): boolean {
    return Object.values(checkGuards(hex)).every(Boolean);
}

export function failingGuards(hex: string): GuardName[] {
    const guards = checkGuards(hex);

    return (Object.keys(guards) as GuardName[]).filter((name) => !guards[name]);
}

/**
 * Bounded grid search over (L, C) at the input's fixed H for the nearest
 * color (by OKLCH distance) that passes all four guards. Guards are L/C-
 * driven, not hue-driven, so hue is held constant — the fix stays
 * recognizably "the same color, adjusted", never a hue shift.
 */
export function nearestPassing(hex: string): string {
    const [inputL, inputC, h] = hexToOklch(hex);

    if (passesAllGuards(hex)) {
        return oklchToHex(inputL, inputC, h);
    }

    let best: string | null = null;
    let bestDistance = Infinity;

    for (let li = 0; li <= 45; li++) {
        const l = 0.3 + (li / 45) * (0.75 - 0.3);

        for (let ci = 0; ci <= 25; ci++) {
            const c = 0.05 + (ci / 25) * (0.3 - 0.05);
            const candidate = oklchToHex(l, c, h);

            if (!passesAllGuards(candidate)) continue;

            const distance = Math.sqrt((l - inputL) ** 2 + (c - inputC) ** 2);

            if (distance < bestDistance) {
                bestDistance = distance;
                best = candidate;
            }
        }
    }

    return best ?? oklchToHex(inputL, inputC, h);
}
