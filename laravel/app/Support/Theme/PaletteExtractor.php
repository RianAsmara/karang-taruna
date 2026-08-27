<?php

namespace App\Support\Theme;

/**
 * "AI color suggestion" per docs/design/docs/theme-builder.md § AI color
 * suggestion — OKLCH-quantizes the opaque pixels of a processed logo,
 * drops near-white/near-black/low-chroma clusters, ranks the rest by
 * chroma × coverage, and returns up to 4 candidates. A suggestion only —
 * nothing here is pre-selected, and every candidate is guard-checked so the
 * UI can show a pass/fail badge (and a `nearestPassing` neighbor for any
 * that fail) rather than silently filtering.
 */
class PaletteExtractor
{
    private const SAMPLE_STRIDE = 4;

    private const NEAR_WHITE_L = 0.92;

    private const NEAR_BLACK_L = 0.08;

    private const LOW_CHROMA = 0.04;

    private const MAX_CANDIDATES = 4;

    /** Bucket grid resolution — coarse enough to group "the same color" across anti-aliased edges. */
    private const BUCKET_L_STEP = 0.05;

    private const BUCKET_C_STEP = 0.02;

    private const BUCKET_H_STEP = 15.0;

    /**
     * @return list<array{hex: string, passesAllGuards: bool, guards: array<string, bool>, nearestPassing: string}>
     */
    public function extract(\GdImage $image): array
    {
        $buckets = $this->quantize($image);

        if ($buckets === []) {
            return [];
        }

        usort($buckets, fn (array $a, array $b) => ($b['chroma'] * $b['count']) <=> ($a['chroma'] * $a['count']));

        $candidates = [];

        foreach (array_slice($buckets, 0, self::MAX_CANDIDATES) as $bucket) {
            $hex = OklchColor::oklchToHex($bucket['l'], $bucket['chroma'], $bucket['h']);
            $guards = ThemeColorEngine::checkGuards($hex);
            $passes = ! in_array(false, $guards, true);

            $candidates[] = [
                'hex' => $hex,
                'passesAllGuards' => $passes,
                'guards' => $guards,
                'nearestPassing' => $passes ? $hex : ThemeColorEngine::nearestPassing($hex),
            ];
        }

        return $candidates;
    }

    /**
     * @return list<array{l: float, chroma: float, h: float, count: int}>
     */
    private function quantize(\GdImage $image): array
    {
        $width = imagesx($image);
        $height = imagesy($image);

        /** @var array<string, array{lSum: float, cSum: float, hSum: float, count: int}> $buckets */
        $buckets = [];

        for ($y = 0; $y < $height; $y += self::SAMPLE_STRIDE) {
            for ($x = 0; $x < $width; $x += self::SAMPLE_STRIDE) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;

                if ($alpha >= 127) {
                    continue;
                }

                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                [$l, $c, $h] = OklchColor::rgbToOklch($r, $g, $b);

                if ($l > self::NEAR_WHITE_L || $l < self::NEAR_BLACK_L || $c < self::LOW_CHROMA) {
                    continue;
                }

                $key = implode(':', [
                    (int) round($l / self::BUCKET_L_STEP),
                    (int) round($c / self::BUCKET_C_STEP),
                    (int) round($h / self::BUCKET_H_STEP),
                ]);

                $buckets[$key] ??= ['lSum' => 0.0, 'cSum' => 0.0, 'hSum' => 0.0, 'count' => 0];
                $buckets[$key]['lSum'] += $l;
                $buckets[$key]['cSum'] += $c;
                $buckets[$key]['hSum'] += $h;
                $buckets[$key]['count']++;
            }
        }

        return array_values(array_map(fn (array $bucket) => [
            'l' => $bucket['lSum'] / $bucket['count'],
            'chroma' => $bucket['cSum'] / $bucket['count'],
            'h' => $bucket['hSum'] / $bucket['count'],
            'count' => $bucket['count'],
        ], $buckets));
    }
}
