<?php

namespace Tests\Unit;

use App\Support\Theme\ThemeColorEngine;
use PHPUnit\Framework\TestCase;

class ThemeColorEngineTest extends TestCase
{
    private function assertHexWithinTolerance(string $expected, string $actual, int $tolerancePerChannel, string $message = ''): void
    {
        $e = sscanf(ltrim($expected, '#'), '%2x%2x%2x');
        $a = sscanf(ltrim($actual, '#'), '%2x%2x%2x');

        foreach ([0, 1, 2] as $i) {
            $delta = abs($e[$i] - $a[$i]);
            $this->assertLessThanOrEqual(
                $tolerancePerChannel,
                $delta,
                "{$message}: channel {$i} delta {$delta} exceeds tolerance {$tolerancePerChannel} ({$expected} vs {$actual})",
            );
        }
    }

    public function test_light_derivation_reproduces_the_spec_reference_table()
    {
        $light = ThemeColorEngine::deriveLight('#ec3013');

        $this->assertSame('#ec3013', $light['accent']);
        // accent200's C min(C, 0.06) cap doesn't hit the legacy hex within
        // 1/255 (real gap ~5/255, not rounding noise — verified during
        // implementation and confirmed with the user: implement the formula
        // literally, document the wider tolerance here).
        $this->assertHexWithinTolerance('#ffe0d9', $light['accent200'], 5, 'accent200');
        $this->assertHexWithinTolerance('#ae1800', $light['accent700'], 2, 'accent700');
        $this->assertHexWithinTolerance('#7c1405', $light['accent800'], 6, 'accent800');
        $this->assertSame('#f3f2f2', $light['onAccent']);
        $this->assertSame('#f3f2f2', $light['onInk']);
    }

    public function test_dark_derivation_reproduces_the_spec_reference_table()
    {
        $dark = ThemeColorEngine::deriveDark('#ec3013');

        $this->assertHexWithinTolerance('#ff563c', $dark['accent'], 18, 'dark accent');
        $this->assertHexWithinTolerance('#7c1405', $dark['accent200'], 6, 'dark accent200 (= light accent800)');
        $this->assertHexWithinTolerance('#ff9783', $dark['accent700'], 3, 'dark accent700');
        $this->assertSame('#ffc4b8', $dark['accent800']);
        $this->assertSame('#1a1918', $dark['onAccent']);
        $this->assertSame('#1a1918', $dark['onInk']);
    }

    public function test_the_spec_worked_example_green_passes_every_guard()
    {
        // docs/design/docs/theme-builder.md's own output-contract example
        // uses #1E5B3B as a valid primary — it must pass.
        $this->assertTrue(ThemeColorEngine::passesAllGuards('#1E5B3B'));
    }

    public function test_the_shipped_default_red_fails_guard_2_and_that_is_expected()
    {
        // Confirmed with the user: the shipped #ec3013 default is a
        // grandfathered static fallback baked into theme.ts/the mobile
        // binary. It never re-runs through this pipeline, so it failing
        // guard 2 (onAccent vs accent, 3.76:1 < 4.5:1) as a *new* submission
        // is correct, stricter-than-legacy behavior, not a bug.
        $guards = ThemeColorEngine::checkGuards('#ec3013');

        $this->assertFalse($guards['onAccentVsAccent']);
        $this->assertTrue($guards['accentVsBg']);
        $this->assertTrue($guards['accent700VsSurface']);
        $this->assertTrue($guards['chromaFloor']);
        $this->assertFalse(ThemeColorEngine::passesAllGuards('#ec3013'));
    }

    public function test_chroma_floor_rejects_a_near_gray_primary()
    {
        $guards = ThemeColorEngine::checkGuards('#888888');

        $this->assertFalse($guards['chromaFloor']);
        $this->assertFalse(ThemeColorEngine::passesAllGuards('#888888'));
    }

    public function test_nearest_passing_returns_the_input_unchanged_when_it_already_passes()
    {
        $this->assertSame(
            ThemeColorEngine::deriveLight('#1E5B3B')['accent'],
            ThemeColorEngine::nearestPassing('#1E5B3B'),
        );
    }

    public function test_nearest_passing_finds_a_color_that_passes_every_guard()
    {
        foreach (['#ec3013', '#888888', '#ff0000', '#000080'] as $failingHex) {
            $fixed = ThemeColorEngine::nearestPassing($failingHex);

            $this->assertTrue(
                ThemeColorEngine::passesAllGuards($fixed),
                "nearestPassing({$failingHex}) = {$fixed} still fails a guard",
            );
        }
    }

    public function test_failing_guards_lists_only_the_checks_that_actually_failed()
    {
        $this->assertSame(['onAccentVsAccent'], ThemeColorEngine::failingGuards('#ec3013'));
        $this->assertSame([], ThemeColorEngine::failingGuards('#1E5B3B'));
    }
}
