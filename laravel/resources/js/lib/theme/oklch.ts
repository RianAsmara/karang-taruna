// sRGB <-> OKLCH conversion, the standard Björn Ottosson (2020) matrices.
// Pure math, no framework dependency — this file and its PHP twin
// (app/Support/Theme/OklchColor.php) are the only two places this app does
// color-space conversion, and both are pinned to the same reference-value
// unit tests so they can't silently drift apart.

type Oklch = [l: number, c: number, h: number];
type LinearRgb = [r: number, g: number, b: number];
type Oklab = [l: number, a: number, b: number];

export function hexToOklch(hex: string): Oklch {
    const [r, g, b] = hexToLinearRgb(hex);
    const [labL, labA, labB] = linearRgbToOklab(r, g, b);

    return oklabToOklch(labL, labA, labB);
}

/**
 * Converts OKLCH back to a hex string, reducing chroma at fixed L/H until
 * the color re-enters the sRGB gamut (standard chroma-reduction gamut
 * mapping) — the input is never rejected, only pulled back in.
 */
export function oklchToHex(l: number, c: number, h: number): string {
    const [a, b] = oklchToOklab(c, h);

    if (inGamut(l, a, b)) {
        return linearRgbToHex(...oklabToLinearRgb(l, a, b));
    }

    let lo = 0;
    let hi = c;

    for (let i = 0; i < 24; i++) {
        const mid = (lo + hi) / 2;
        const [ma, mb] = oklchToOklab(mid, h);

        if (inGamut(l, ma, mb)) {
            lo = mid;
        } else {
            hi = mid;
        }
    }

    const [fa, fb] = oklchToOklab(lo, h);

    return linearRgbToHex(...oklabToLinearRgb(l, fa, fb));
}

export function chroma(hex: string): number {
    return hexToOklch(hex)[1];
}

export function lightness(hex: string): number {
    return hexToOklch(hex)[0];
}

/** WCAG relative-luminance contrast ratio between two hex colors. */
export function contrastRatio(hexA: string, hexB: string): number {
    const la = relativeLuminance(hexA);
    const lb = relativeLuminance(hexB);
    const lighter = Math.max(la, lb);
    const darker = Math.min(la, lb);

    return (lighter + 0.05) / (darker + 0.05);
}

function relativeLuminance(hex: string): number {
    const [r, g, b] = hexToLinearRgb(hex);

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function inGamut(l: number, a: number, b: number): boolean {
    const [r, g, bl] = oklabToLinearRgb(l, a, b);
    const eps = 1e-5;

    return r >= -eps && r <= 1 + eps && g >= -eps && g <= 1 + eps && bl >= -eps && bl <= 1 + eps;
}

function hexToLinearRgb(hex: string): LinearRgb {
    const clean = hex.replace('#', '');
    const r = parseInt(clean.slice(0, 2), 16) / 255;
    const g = parseInt(clean.slice(2, 4), 16) / 255;
    const b = parseInt(clean.slice(4, 6), 16) / 255;

    return [srgbToLinear(r), srgbToLinear(g), srgbToLinear(b)];
}

function srgbToLinear(c: number): number {
    return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
}

function linearToSrgb(c: number): number {
    return c <= 0.0031308 ? c * 12.92 : 1.055 * c ** (1 / 2.4) - 0.055;
}

function linearRgbToOklab(r: number, g: number, b: number): Oklab {
    const l = 0.4122214708 * r + 0.5363325363 * g + 0.0514459929 * b;
    const m = 0.2119034982 * r + 0.6806995451 * g + 0.1073969566 * b;
    const s = 0.0883024619 * r + 0.2817188376 * g + 0.6299787005 * b;

    const l_ = cbrt(l);
    const m_ = cbrt(m);
    const s_ = cbrt(s);

    return [
        0.2104542553 * l_ + 0.793617785 * m_ - 0.0040720468 * s_,
        1.9779984951 * l_ - 2.428592205 * m_ + 0.4505937099 * s_,
        0.0259040371 * l_ + 0.7827717662 * m_ - 0.808675766 * s_,
    ];
}

function oklabToLinearRgb(L: number, a: number, b: number): LinearRgb {
    const l_ = L + 0.3963377774 * a + 0.2158037573 * b;
    const m_ = L - 0.1055613458 * a - 0.0638541728 * b;
    const s_ = L - 0.0894841775 * a - 1.291485548 * b;

    const l = l_ ** 3;
    const m = m_ ** 3;
    const s = s_ ** 3;

    return [
        4.0767416621 * l - 3.3077115913 * m + 0.2309699292 * s,
        -1.2684380046 * l + 2.6097574011 * m - 0.3413193965 * s,
        -0.0041960863 * l - 0.7034186147 * m + 1.707614701 * s,
    ];
}

function linearRgbToHex(r: number, g: number, b: number): string {
    const toByte = (c: number) =>
        Math.max(0, Math.min(255, Math.round(linearToSrgb(c) * 255)))
            .toString(16)
            .padStart(2, '0');

    return `#${toByte(r)}${toByte(g)}${toByte(b)}`;
}

function oklabToOklch(L: number, a: number, b: number): Oklch {
    const c = Math.sqrt(a ** 2 + b ** 2);
    const h = (Math.atan2(b, a) * 180) / Math.PI;

    return [L, c, h];
}

function oklchToOklab(c: number, h: number): [a: number, b: number] {
    const hRad = (h * Math.PI) / 180;

    return [c * Math.cos(hRad), c * Math.sin(hRad)];
}

function cbrt(x: number): number {
    return x >= 0 ? x ** (1 / 3) : -((-x) ** (1 / 3));
}
