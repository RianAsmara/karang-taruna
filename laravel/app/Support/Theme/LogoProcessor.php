<?php

namespace App\Support\Theme;

use RuntimeException;

/**
 * Turns one uploaded PNG into the fixed set of derivatives the theme
 * contract needs (see docs/design/docs/theme-builder.md § Logo handling):
 * mark@1x/2x/3x, icon-1024, and mono. Pure GD (already installed, no new
 * dependency) — operates only on PNG. SVG input is rasterized to PNG
 * client-side before it ever reaches this class (see the theme-builder
 * plan's SVG decision); this class never branches on source format.
 *
 * Radius 0 throughout: nothing here is ever masked into a rounded shape.
 */
class LogoProcessor
{
    private const MARK_BASE_PX = 96;

    private const ICON_PX = 1024;

    private const MONO_PX = 512;

    private const PADDING_RATIO = 0.08;

    /** #201e1d — the fixed ink color mono is flattened to. Never the brand accent. */
    private const INK_R = 0x20;

    private const INK_G = 0x1E;

    private const INK_B = 0x1D;

    /**
     * @return array{mark1x: \GdImage, mark2x: \GdImage, mark3x: \GdImage, icon: \GdImage, mono: \GdImage}
     */
    public function process(\GdImage $source): array
    {
        [$left, $top, $right, $bottom] = $this->opaqueBoundingBox($source);
        $trimmedWidth = $right - $left + 1;
        $trimmedHeight = $bottom - $top + 1;

        $trimmed = $this->crop($source, $left, $top, $trimmedWidth, $trimmedHeight);
        $canonical = $this->padToSquare($trimmed, $trimmedWidth, $trimmedHeight, self::PADDING_RATIO);

        return [
            'mark1x' => $this->resizeSquare($canonical, self::MARK_BASE_PX),
            'mark2x' => $this->resizeSquare($canonical, self::MARK_BASE_PX * 2),
            'mark3x' => $this->resizeSquare($canonical, self::MARK_BASE_PX * 3),
            // Full bleed — no 8% margin — for platform icon export, which
            // applies its own mask/padding.
            'icon' => $this->padToSquare($trimmed, $trimmedWidth, $trimmedHeight, 0, self::ICON_PX),
            'mono' => $this->toMono($this->resizeSquare($canonical, self::MONO_PX)),
        ];
    }

    /**
     * Tightest bounding box of any pixel with alpha > 0 (GD alpha is
     * inverted: 0 = opaque, 127 = fully transparent).
     *
     * @return array{0: int, 1: int, 2: int, 3: int} left, top, right, bottom
     */
    private function opaqueBoundingBox(\GdImage $image): array
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $left = $width;
        $top = $height;
        $right = -1;
        $bottom = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $alpha = (imagecolorat($image, $x, $y) >> 24) & 0x7F;

                if ($alpha >= 127) {
                    continue;
                }

                $left = min($left, $x);
                $right = max($right, $x);
                $top = min($top, $y);
                $bottom = max($bottom, $y);
            }
        }

        if ($right === -1) {
            // Fully transparent image — nothing to trim to; use the whole canvas.
            return [0, 0, $width - 1, $height - 1];
        }

        return [$left, $top, $right, $bottom];
    }

    private function crop(\GdImage $source, int $left, int $top, int $width, int $height): \GdImage
    {
        $cropped = $this->blankCanvas($width, $height);
        imagecopy($cropped, $source, 0, 0, $left, $top, $width, $height);

        return $cropped;
    }

    /**
     * Centers `$content` in a square canvas. With `$paddingRatio` > 0, the
     * content's longer side occupies `(1 - 2*paddingRatio)` of the canvas
     * ("8% breathing room on every side"). With 0, content fills the canvas
     * edge to edge on its longer axis (full bleed).
     */
    private function padToSquare(\GdImage $content, int $contentWidth, int $contentHeight, float $paddingRatio, ?int $forceCanvasSide = null): \GdImage
    {
        $longerSide = max($contentWidth, $contentHeight);
        $canvasSide = $forceCanvasSide ?? (int) ceil($longerSide / (1 - 2 * $paddingRatio));

        $scale = ($canvasSide * (1 - 2 * $paddingRatio)) / $longerSide;
        $destWidth = (int) round($contentWidth * $scale);
        $destHeight = (int) round($contentHeight * $scale);

        $canvas = $this->blankCanvas($canvasSide, $canvasSide);
        imagecopyresampled(
            $canvas,
            $content,
            (int) round(($canvasSide - $destWidth) / 2),
            (int) round(($canvasSide - $destHeight) / 2),
            0,
            0,
            $destWidth,
            $destHeight,
            $contentWidth,
            $contentHeight,
        );

        return $canvas;
    }

    private function resizeSquare(\GdImage $source, int $side): \GdImage
    {
        $canvas = $this->blankCanvas($side, $side);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $side, $side, imagesx($source), imagesy($source));

        return $canvas;
    }

    /**
     * Every opaque-or-partial pixel's RGB is replaced with the fixed ink
     * color; alpha is untouched. Not a tint/colorize filter — a flat
     * replacement, since the mono asset must read as one solid ink color
     * at every opacity level, independent of the source hue.
     */
    private function toMono(\GdImage $source): \GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $mono = $this->blankCanvas($width, $height);
        $inkColor = imagecolorallocatealpha($mono, self::INK_R, self::INK_G, self::INK_B, 0);

        if ($inkColor === false) {
            throw new RuntimeException('Unable to allocate ink color for mono logo.');
        }

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $alpha = (imagecolorat($source, $x, $y) >> 24) & 0x7F;
                $inkWithSourceAlpha = imagecolorallocatealpha($mono, self::INK_R, self::INK_G, self::INK_B, $alpha);
                imagesetpixel($mono, $x, $y, $inkWithSourceAlpha === false ? $inkColor : $inkWithSourceAlpha);
            }
        }

        return $mono;
    }

    private function blankCanvas(int $width, int $height): \GdImage
    {
        $width = max(1, $width);
        $height = max(1, $height);

        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);

        if ($transparent === false) {
            throw new RuntimeException('Unable to allocate transparent background color.');
        }

        imagefilledrectangle($canvas, 0, 0, $width - 1, $height - 1, $transparent);
        imagealphablending($canvas, false);

        return $canvas;
    }
}
