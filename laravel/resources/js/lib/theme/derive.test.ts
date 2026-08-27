import { describe, expect, it } from 'vitest';

import { checkGuards, deriveDark, deriveLight, failingGuards, nearestPassing, passesAllGuards } from './derive';

function assertHexWithinTolerance(expected: string, actual: string, tolerancePerChannel: number) {
    const e = expected
        .replace('#', '')
        .match(/.{2}/g)!
        .map((h) => parseInt(h, 16));
    const a = actual
        .replace('#', '')
        .match(/.{2}/g)!
        .map((h) => parseInt(h, 16));

    for (let i = 0; i < 3; i++) {
        const delta = Math.abs(e[i] - a[i]);
        expect(delta, `channel ${i} delta ${delta} exceeds tolerance ${tolerancePerChannel} (${expected} vs ${actual})`).toBeLessThanOrEqual(
            tolerancePerChannel,
        );
    }
}

describe('derive', () => {
    it('light derivation reproduces the spec reference table', () => {
        const light = deriveLight('#ec3013');

        expect(light.accent).toBe('#ec3013');
        // accent200's C min(C, 0.06) cap doesn't hit the legacy hex within
        // 1/255 (real gap ~5/255, not rounding noise) — implemented literally,
        // documented wider tolerance here, matching the PHP twin's test.
        assertHexWithinTolerance('#ffe0d9', light.accent200, 5);
        assertHexWithinTolerance('#ae1800', light.accent700, 2);
        assertHexWithinTolerance('#7c1405', light.accent800, 6);
        expect(light.onAccent).toBe('#f3f2f2');
        expect(light.onInk).toBe('#f3f2f2');
    });

    it('dark derivation reproduces the spec reference table', () => {
        const dark = deriveDark('#ec3013');

        assertHexWithinTolerance('#ff563c', dark.accent, 18);
        assertHexWithinTolerance('#7c1405', dark.accent200, 6);
        assertHexWithinTolerance('#ff9783', dark.accent700, 3);
        expect(dark.accent800).toBe('#ffc4b8');
        expect(dark.onAccent).toBe('#1a1918');
        expect(dark.onInk).toBe('#1a1918');
    });

    it('the spec worked example green passes every guard', () => {
        expect(passesAllGuards('#1E5B3B')).toBe(true);
    });

    it('the shipped default red fails guard 2 and that is expected', () => {
        const guards = checkGuards('#ec3013');

        expect(guards.onAccentVsAccent).toBe(false);
        expect(guards.accentVsBg).toBe(true);
        expect(guards.accent700VsSurface).toBe(true);
        expect(guards.chromaFloor).toBe(true);
        expect(passesAllGuards('#ec3013')).toBe(false);
    });

    it('chroma floor rejects a near-gray primary', () => {
        const guards = checkGuards('#888888');

        expect(guards.chromaFloor).toBe(false);
        expect(passesAllGuards('#888888')).toBe(false);
    });

    it('nearestPassing returns the input unchanged when it already passes', () => {
        expect(nearestPassing('#1E5B3B')).toBe(deriveLight('#1E5B3B').accent);
    });

    it('nearestPassing finds a color that passes every guard', () => {
        for (const failingHex of ['#ec3013', '#888888', '#ff0000', '#000080']) {
            const fixed = nearestPassing(failingHex);
            expect(passesAllGuards(fixed), `nearestPassing(${failingHex}) = ${fixed} still fails a guard`).toBe(true);
        }
    });

    it('failingGuards lists only the checks that actually failed', () => {
        expect(failingGuards('#ec3013')).toEqual(['onAccentVsAccent']);
        expect(failingGuards('#1E5B3B')).toEqual([]);
    });
});
