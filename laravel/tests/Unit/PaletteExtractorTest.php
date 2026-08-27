<?php

namespace Tests\Unit;

use App\Support\Theme\PaletteExtractor;
use PHPUnit\Framework\TestCase;

class PaletteExtractorTest extends TestCase
{
    public function test_extract_returns_nothing_for_a_pure_white_and_black_logo()
    {
        $image = imagecreatetruecolor(100, 100);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $white = imagecolorallocatealpha($image, 255, 255, 255, 0);
        imagefilledrectangle($image, 0, 0, 49, 99, $white);
        $black = imagecolorallocatealpha($image, 0, 0, 0, 0);
        imagefilledrectangle($image, 50, 0, 99, 99, $black);

        $this->assertSame([], (new PaletteExtractor)->extract($image));
    }

    public function test_extract_finds_a_dominant_saturated_color_and_ranks_it_first()
    {
        $image = imagecreatetruecolor(400, 400);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefilledrectangle($image, 0, 0, 399, 399, $transparent);
        imagealphablending($image, true);

        // Large red field (dominant coverage) + a small blue square.
        $red = imagecolorallocatealpha($image, 236, 48, 19, 0);
        imagefilledellipse($image, 200, 200, 300, 300, $red);
        $blue = imagecolorallocatealpha($image, 20, 60, 200, 0);
        imagefilledrectangle($image, 20, 20, 80, 80, $blue);

        $candidates = (new PaletteExtractor)->extract($image);

        $this->assertNotEmpty($candidates);
        $this->assertLessThanOrEqual(4, count($candidates));
        $this->assertSame('#ec3013', $candidates[0]['hex']);
        $this->assertArrayHasKey('passesAllGuards', $candidates[0]);
        $this->assertArrayHasKey('guards', $candidates[0]);
        $this->assertArrayHasKey('nearestPassing', $candidates[0]);
    }

    public function test_a_failing_candidate_carries_a_passing_neighbor_instead_of_being_hidden()
    {
        $image = imagecreatetruecolor(200, 200);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefilledrectangle($image, 0, 0, 199, 199, $transparent);
        imagealphablending($image, true);
        $red = imagecolorallocatealpha($image, 236, 48, 19, 0);
        imagefilledellipse($image, 100, 100, 150, 150, $red);

        $candidates = (new PaletteExtractor)->extract($image);

        $this->assertNotEmpty($candidates);
        $this->assertFalse($candidates[0]['passesAllGuards']);
        $this->assertNotSame($candidates[0]['hex'], $candidates[0]['nearestPassing']);
    }
}
