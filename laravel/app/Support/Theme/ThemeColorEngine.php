<?php

namespace App\Support\Theme;

/**
 * Pure derivation of the six brand-color tokens from one authored primary,
 * per docs/design/docs/theme-builder.md — the single authoritative
 * implementation of that spec's derivation tables and guards. Mirrored in
 * resources/js/lib/theme/derive.ts for instant client-side preview/guard
 * feedback while a user picks a color; THIS class is what actually decides
 * whether a theme may be persisted (never trust the client's copy of the
 * math). Both are pinned to the same reference-value unit test.
 *
 * Two formula notes, found during verification against the spec's own
 * `#ec3013` reference table and confirmed with the user before implementing:
 *
 * - `accent200`'s cap (`C min(C, 0.06)`) does not reproduce `#ffe0d9` within
 *   1/255 — the reference's real chroma is ~0.036. Implemented literally per
 *   the spec anyway (a general rule for arbitrary new brand colors, not a
 *   reverse-engineering of this one legacy hex); the unit test documents the
 *   wider tolerance for this one token.
 * - `onAccent`'s "bg or text, whichever passes on accent" cannot be a live
 *   4.5:1 contrast check — neither candidate clears 4.5:1 for the reference
 *   input, yet the reference clearly wants `bg`. Implemented as an OKLCH
 *   lightness heuristic on `accent` itself instead (see
 *   ON_ACCENT_LIGHTNESS_THRESHOLD). Guard 2 still hard-blocks save on newly
 *   submitted primaries; the shipped default is a grandfathered static
 *   fallback that never runs through this engine.
 */
class ThemeColorEngine
{
    private const LIGHT_BG = '#f3f2f2';

    private const LIGHT_SURFACE = '#ffffff';

    private const LIGHT_TEXT = '#201e1d';

    private const DARK_BG = '#1a1918';

    private const DARK_SURFACE = '#242221';

    /** Below this OKLCH lightness, accent is "dark enough" for onAccent = bg; above it, onAccent = text. Tuned so the reference input (L=0.6112) selects bg, matching the spec's own reference value. */
    private const ON_ACCENT_LIGHTNESS_THRESHOLD = 0.7;

    private const CHROMA_FLOOR = 0.05;

    private const GUARD1_MIN_CONTRAST = 3.0;

    private const GUARD2_MIN_CONTRAST = 4.5;

    private const GUARD3_MIN_CONTRAST = 4.5;

    /**
     * @return array{accent: string, accent200: string, accent700: string, accent800: string, onAccent: string, onInk: string}
     */
    public static function deriveLight(string $hex): array
    {
        [$l, $c, $h] = OklchColor::hexToOklch($hex);

        $accent = OklchColor::oklchToHex($l, $c, $h);

        return [
            'accent' => $accent,
            'accent200' => OklchColor::oklchToHex(0.92, min($c, 0.06), $h),
            'accent700' => OklchColor::oklchToHex($l * 0.78, $c * 0.81, $h),
            'accent800' => OklchColor::oklchToHex($l * 0.61, $c * 0.65, $h),
            'onAccent' => $l < self::ON_ACCENT_LIGHTNESS_THRESHOLD ? self::LIGHT_BG : self::LIGHT_TEXT,
            // Spec: "only if text inversion demands it; in practice stays #f3f2f2".
            'onInk' => self::LIGHT_BG,
        ];
    }

    /**
     * @return array{accent: string, accent200: string, accent700: string, accent800: string, onAccent: string, onInk: string}
     */
    public static function deriveDark(string $hex): array
    {
        [$l, $c, $h] = OklchColor::hexToOklch($hex);
        $lightAccent800 = self::deriveLight($hex)['accent800'];

        return [
            'accent' => OklchColor::oklchToHex($l + 0.09, $c * 0.88, $h),
            'accent200' => $lightAccent800,
            'accent700' => OklchColor::oklchToHex(0.78, $c * 0.55, $h),
            'accent800' => OklchColor::oklchToHex(0.87, $c * 0.38, $h),
            'onAccent' => self::DARK_BG,
            'onInk' => self::DARK_BG,
        ];
    }

    /**
     * @return array{light: array{accent: string, accent200: string, accent700: string, accent800: string, onAccent: string, onInk: string}, dark: array{accent: string, accent200: string, accent700: string, accent800: string, onAccent: string, onInk: string}}
     */
    public static function derive(string $hex): array
    {
        return [
            'light' => self::deriveLight($hex),
            'dark' => self::deriveDark($hex),
        ];
    }

    /**
     * The four guards from the spec. All must pass to allow save.
     *
     * @return array<string, bool>
     */
    public static function checkGuards(string $hex): array
    {
        $light = self::deriveLight($hex);
        $dark = self::deriveDark($hex);
        $chroma = OklchColor::chroma($hex);

        return [
            'accentVsBg' => OklchColor::contrastRatio($light['accent'], self::LIGHT_BG) >= self::GUARD1_MIN_CONTRAST
                && OklchColor::contrastRatio($dark['accent'], self::DARK_BG) >= self::GUARD1_MIN_CONTRAST,
            // Light mode only — unlike guards 1 and 3, the spec never says
            // "and dark" / "in both modes" for this one. Dark mode's
            // onAccent is unconditionally `dark bg` per the derivation
            // table (no bg-or-text branch to guard there at all), so this
            // guard is only meaningful where the light-mode branch decision
            // actually happens.
            'onAccentVsAccent' => OklchColor::contrastRatio($light['onAccent'], $light['accent']) >= self::GUARD2_MIN_CONTRAST,
            'accent700VsSurface' => OklchColor::contrastRatio($light['accent700'], self::LIGHT_SURFACE) >= self::GUARD3_MIN_CONTRAST
                && OklchColor::contrastRatio($dark['accent700'], self::DARK_SURFACE) >= self::GUARD3_MIN_CONTRAST,
            'chromaFloor' => $chroma >= self::CHROMA_FLOOR,
        ];
    }

    public static function passesAllGuards(string $hex): bool
    {
        return ! in_array(false, self::checkGuards($hex), true);
    }

    /**
     * @return list<string> names of the failing guards, empty if all pass
     */
    public static function failingGuards(string $hex): array
    {
        return array_keys(array_filter(self::checkGuards($hex), fn (bool $passed) => ! $passed));
    }

    /**
     * Bounded grid search over (L, C) at the input's fixed H for the
     * nearest color (by OKLCH distance) that passes all four guards.
     * Guards are L/C-driven, not hue-driven, so hue is held constant —
     * the fix stays recognizably "the same color, adjusted", never a hue
     * shift.
     */
    public static function nearestPassing(string $hex): string
    {
        [$inputL, $inputC, $h] = OklchColor::hexToOklch($hex);

        if (self::passesAllGuards($hex)) {
            return OklchColor::oklchToHex($inputL, $inputC, $h);
        }

        $best = null;
        $bestDistance = INF;

        for ($li = 0; $li <= 45; $li++) {
            $l = 0.30 + ($li / 45) * (0.75 - 0.30);

            for ($ci = 0; $ci <= 25; $ci++) {
                $c = 0.05 + ($ci / 25) * (0.30 - 0.05);
                $candidate = OklchColor::oklchToHex($l, $c, $h);

                if (! self::passesAllGuards($candidate)) {
                    continue;
                }

                $distance = sqrt(($l - $inputL) ** 2 + ($c - $inputC) ** 2);

                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $best = $candidate;
                }
            }
        }

        return $best ?? OklchColor::oklchToHex($inputL, $inputC, $h);
    }
}
