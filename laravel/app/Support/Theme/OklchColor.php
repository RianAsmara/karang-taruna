<?php

namespace App\Support\Theme;

/**
 * sRGB <-> OKLCH conversion, the standard Björn Ottosson (2020) matrices.
 * Pure math, no framework dependency — this file and its TypeScript twin
 * (resources/js/lib/theme/derive.ts) are the only two places this app does
 * color-space conversion, and both are pinned to the same reference-value
 * unit tests so they can't silently drift apart.
 */
class OklchColor
{
    /**
     * @return array{0: float, 1: float, 2: float} L, C, H (H in degrees)
     */
    public static function hexToOklch(string $hex): array
    {
        [$r, $g, $b] = self::hexToLinearRgb($hex);
        [$labL, $labA, $labB] = self::linearRgbToOklab($r, $g, $b);

        return self::oklabToOklch($labL, $labA, $labB);
    }

    /**
     * Converts OKLCH back to a hex string, reducing chroma at fixed L/H
     * until the color re-enters the sRGB gamut (standard chroma-reduction
     * gamut mapping) — the input is never rejected, only pulled back in.
     */
    public static function oklchToHex(float $l, float $c, float $h): string
    {
        [$a, $b] = self::oklchToOklab($c, $h);

        if (self::inGamut($l, $a, $b)) {
            return self::linearRgbToHex(...self::oklabToLinearRgb($l, $a, $b));
        }

        $lo = 0.0;
        $hi = $c;

        for ($i = 0; $i < 24; $i++) {
            $mid = ($lo + $hi) / 2;
            [$ma, $mb] = self::oklchToOklab($mid, $h);

            if (self::inGamut($l, $ma, $mb)) {
                $lo = $mid;
            } else {
                $hi = $mid;
            }
        }

        [$fa, $fb] = self::oklchToOklab($lo, $h);

        return self::linearRgbToHex(...self::oklabToLinearRgb($l, $fa, $fb));
    }

    /**
     * @return array{0: float, 1: float, 2: float} L, C, H (H in degrees)
     */
    public static function rgbToOklch(int $r, int $g, int $b): array
    {
        [$labL, $labA, $labB] = self::linearRgbToOklab(self::srgbToLinear($r / 255), self::srgbToLinear($g / 255), self::srgbToLinear($b / 255));

        return self::oklabToOklch($labL, $labA, $labB);
    }

    public static function chroma(string $hex): float
    {
        return self::hexToOklch($hex)[1];
    }

    public static function lightness(string $hex): float
    {
        return self::hexToOklch($hex)[0];
    }

    /**
     * WCAG relative-luminance contrast ratio between two hex colors.
     */
    public static function contrastRatio(string $hexA, string $hexB): float
    {
        $la = self::relativeLuminance($hexA);
        $lb = self::relativeLuminance($hexB);
        $lighter = max($la, $lb);
        $darker = min($la, $lb);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private static function relativeLuminance(string $hex): float
    {
        [$r, $g, $b] = self::hexToLinearRgb($hex);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private static function inGamut(float $l, float $a, float $b): bool
    {
        [$r, $g, $bl] = self::oklabToLinearRgb($l, $a, $b);
        $eps = 1e-5;

        return $r >= -$eps && $r <= 1 + $eps && $g >= -$eps && $g <= 1 + $eps && $bl >= -$eps && $bl <= 1 + $eps;
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private static function hexToLinearRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        return [self::srgbToLinear($r), self::srgbToLinear($g), self::srgbToLinear($b)];
    }

    private static function srgbToLinear(float $c): float
    {
        return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    }

    private static function linearToSrgb(float $c): float
    {
        return $c <= 0.0031308 ? $c * 12.92 : 1.055 * ($c ** (1 / 2.4)) - 0.055;
    }

    /**
     * @return array{0: float, 1: float, 2: float} L, a, b (OKLab)
     */
    private static function linearRgbToOklab(float $r, float $g, float $b): array
    {
        $l = 0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b;
        $m = 0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b;
        $s = 0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b;

        $l_ = self::cbrt($l);
        $m_ = self::cbrt($m);
        $s_ = self::cbrt($s);

        return [
            0.2104542553 * $l_ + 0.7936177850 * $m_ - 0.0040720468 * $s_,
            1.9779984951 * $l_ - 2.4285922050 * $m_ + 0.4505937099 * $s_,
            0.0259040371 * $l_ + 0.7827717662 * $m_ - 0.8086757660 * $s_,
        ];
    }

    /**
     * @return array{0: float, 1: float, 2: float} r, g, b (linear, unclamped)
     */
    private static function oklabToLinearRgb(float $L, float $a, float $b): array
    {
        $l_ = $L + 0.3963377774 * $a + 0.2158037573 * $b;
        $m_ = $L - 0.1055613458 * $a - 0.0638541728 * $b;
        $s_ = $L - 0.0894841775 * $a - 1.2914855480 * $b;

        $l = $l_ ** 3;
        $m = $m_ ** 3;
        $s = $s_ ** 3;

        return [
            4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s,
            -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s,
            -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s,
        ];
    }

    private static function linearRgbToHex(float $r, float $g, float $b): string
    {
        $toByte = fn (float $c) => str_pad(dechex(max(0, min(255, (int) round(self::linearToSrgb($c) * 255)))), 2, '0', STR_PAD_LEFT);

        return '#'.$toByte($r).$toByte($g).$toByte($b);
    }

    /**
     * @return array{0: float, 1: float, 2: float} L, C, H (degrees)
     */
    private static function oklabToOklch(float $L, float $a, float $b): array
    {
        // Note: caller passes (l, a, b) positionally as (l, m, s) result of
        // linearRgbToOklab, which already returns (L, a, b) — kept here only
        // for the C/H step.
        $c = sqrt($a ** 2 + $b ** 2);
        $h = rad2deg(atan2($b, $a));

        return [$L, $c, $h];
    }

    /**
     * @return array{0: float, 1: float} a, b
     */
    private static function oklchToOklab(float $c, float $h): array
    {
        $hRad = deg2rad($h);

        return [$c * cos($hRad), $c * sin($hRad)];
    }

    private static function cbrt(float $x): float
    {
        return $x >= 0 ? $x ** (1 / 3) : -((-$x) ** (1 / 3));
    }
}
