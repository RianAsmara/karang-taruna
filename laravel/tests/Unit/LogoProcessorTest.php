<?php

namespace Tests\Unit;

use App\Support\Theme\LogoProcessor;
use PHPUnit\Framework\TestCase;

class LogoProcessorTest extends TestCase
{
    /**
     * A 300x150 canvas, transparent, with an opaque 100x100 circle
     * off-center — exercises trim (most of the canvas is empty) and
     * non-square bounding box (wide canvas, round content) at once.
     */
    private function sampleLogo(): \GdImage
    {
        $image = imagecreatetruecolor(300, 150);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefilledrectangle($image, 0, 0, 299, 149, $transparent);
        imagealphablending($image, true);
        $opaqueRed = imagecolorallocatealpha($image, 236, 48, 19, 0);
        imagefilledellipse($image, 150, 75, 100, 100, $opaqueRed);

        return $image;
    }

    public function test_process_emits_every_derivative_at_the_correct_dimensions()
    {
        $out = (new LogoProcessor)->process($this->sampleLogo());

        $this->assertSame([96, 96], [imagesx($out['mark1x']), imagesy($out['mark1x'])]);
        $this->assertSame([192, 192], [imagesx($out['mark2x']), imagesy($out['mark2x'])]);
        $this->assertSame([288, 288], [imagesx($out['mark3x']), imagesy($out['mark3x'])]);
        $this->assertSame([1024, 1024], [imagesx($out['icon']), imagesy($out['icon'])]);
        $this->assertSame([512, 512], [imagesx($out['mono']), imagesy($out['mono'])]);
    }

    public function test_icon_is_full_bleed_with_no_padding_margin()
    {
        $out = (new LogoProcessor)->process($this->sampleLogo());
        $icon = $out['icon'];

        // The circle's top edge should be opaque at y=0 on a full-bleed
        // export — a padded export would have transparent pixels first.
        $topCenterAlpha = (imagecolorat($icon, (int) (imagesx($icon) / 2), 0) >> 24) & 0x7F;
        $this->assertLessThan(127, $topCenterAlpha, 'icon-1024 should touch the canvas edge (full bleed), not be padded');
    }

    public function test_mark_has_breathing_room_padding_unlike_icon()
    {
        $out = (new LogoProcessor)->process($this->sampleLogo());
        $mark = $out['mark3x'];

        $topCenterAlpha = (imagecolorat($mark, (int) (imagesx($mark) / 2), 0) >> 24) & 0x7F;
        $this->assertSame(127, $topCenterAlpha, 'mark@3x should have transparent padding at the very top edge');
    }

    public function test_mono_flattens_every_opaque_pixel_to_the_fixed_ink_color_and_preserves_alpha()
    {
        $out = (new LogoProcessor)->process($this->sampleLogo());
        $mono = $out['mono'];

        $center = (int) (imagesx($mono) / 2);
        $rgba = imagecolorat($mono, $center, $center);
        $r = ($rgba >> 16) & 0xFF;
        $g = ($rgba >> 8) & 0xFF;
        $b = $rgba & 0xFF;
        $alpha = ($rgba >> 24) & 0x7F;

        $this->assertSame(0x20, $r);
        $this->assertSame(0x1E, $g);
        $this->assertSame(0x1D, $b);
        $this->assertSame(0, $alpha, 'center of the circle should be fully opaque');

        $cornerAlpha = (imagecolorat($mono, 2, 2) >> 24) & 0x7F;
        $this->assertSame(127, $cornerAlpha, 'the transparent corner should stay transparent, not become ink-colored');
    }
}
