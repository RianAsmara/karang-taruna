import { checkGuards, deriveDark, deriveLight, failingGuards, nearestPassing, type GuardName } from '@/lib/theme/derive';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Tema', href: '/organisasi/tema' }];

// Fixed tokens — never authored by this builder — from docs/design/rn/theme.ts.
const FIXED = {
    light: {
        bg: '#f3f2f2',
        surface: '#ffffff',
        surfaceAlt: '#eae9e9',
        text: '#201e1d',
        textMuted: '#6b6867',
        divider: '#c9c6c5',
        rule: '#8d8988',
        expense: '#ae1800',
    },
    dark: {
        bg: '#1a1918',
        surface: '#242221',
        surfaceAlt: '#2d2b2a',
        text: '#f3f2f2',
        textMuted: '#a9a5a4',
        divider: '#3d3a39',
        rule: '#5c5857',
        expense: '#ff9783',
    },
};

const GUARD_LABELS: Record<GuardName, string> = {
    accentVsBg: 'Warna aksen terlalu dekat dengan warna latar — teks besar dan ikon jadi sulit dibaca.',
    onAccentVsAccent: 'Teks di atas warna aksen tidak cukup kontras untuk dibaca dengan nyaman.',
    accent700VsSurface: 'Warna aksen gelap (accent700) tidak cukup kontras di atas permukaan putih.',
    chromaFloor: 'Warna ini terlalu mendekati abu-abu — aksen jadi tidak terlihat di antara warna netral.',
};

type LogoUrls = { mark: string; icon: string; mono: string };
// AI color suggestion — paused per user request, kept for later re-enable.
// type PaletteSuggestion = {
//     hex: string;
//     passesAllGuards: boolean;
//     guards: Record<GuardName, boolean>;
//     nearestPassing: string;
// };
type HistoryEntry = { primary: string; actorName: string; appliedAt: string };
type ThemePayload = { primary: string; logo: LogoUrls; color: { light: Record<string, string>; dark: Record<string, string> } };

function readXsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/** Relative luminance (sRGB) — used only for the grayscale-readability check below, not for guard math. */
function grayscaleLuminance(hex: string): number {
    const clean = hex.replace('#', '');
    const r = parseInt(clean.slice(0, 2), 16);
    const g = parseInt(clean.slice(2, 4), 16);
    const b = parseInt(clean.slice(4, 6), 16);

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

export default function ThemeBuilder({
    organizationName,
    theme,
    history,
}: {
    organizationName: string;
    theme: ThemePayload | null;
    history: HistoryEntry[];
}) {
    const [primaryHex, setPrimaryHex] = useState(theme?.primary ?? '#ec3013');
    const [logo, setLogo] = useState<LogoUrls | null>(theme?.logo ?? null);
    // AI color suggestion — paused per user request, kept for later re-enable.
    // const [paletteSuggestions, setPaletteSuggestions] = useState<PaletteSuggestion[]>([]);
    const [uploading, setUploading] = useState(false);
    const [uploadErrors, setUploadErrors] = useState<string[]>([]);
    const [previewMode, setPreviewMode] = useState<'light' | 'dark'>('light');
    const [grayscale, setGrayscale] = useState(false);
    const [applySuccess, setApplySuccess] = useState(false);
    const [processing, setProcessing] = useState(false);

    const errors = (usePage().props.errors ?? {}) as Record<string, string>;

    const guards = useMemo(() => checkGuards(primaryHex), [primaryHex]);
    const failing = useMemo(() => failingGuards(primaryHex), [primaryHex]);
    const passes = failing.length === 0;
    const derived = useMemo(() => deriveLight(primaryHex), [primaryHex]);
    const suggestedFix = useMemo(() => (passes ? null : nearestPassing(primaryHex)), [primaryHex, passes]);

    const kasGrayscaleFails = useMemo(() => {
        const a = grayscaleLuminance(derived.accent700);
        const b = grayscaleLuminance(FIXED.light.expense);

        return Math.abs(a - b) < 15;
    }, [derived]);

    async function handleLogoSelected(file: File) {
        setUploading(true);
        setUploadErrors([]);

        let logoFile = file;

        if (file.type === 'image/svg+xml') {
            logoFile = await rasterizeSvgToPng(file);
        }

        const form = new FormData();
        form.append('logo', logoFile, 'logo.png');
        form.append('source', file);

        try {
            const response = await fetch(route('theme.upload-logo'), {
                method: 'POST',
                headers: { 'X-XSRF-TOKEN': readXsrfToken(), Accept: 'application/json' },
                body: form,
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const body = await response.json().catch(() => null);
                const messages: string[] = body?.errors ? (Object.values(body.errors).flat() as string[]) : ['Gagal mengunggah logo.'];
                setUploadErrors(messages);

                return;
            }

            const body = await response.json();
            setLogo(body.theme.logo);
            // AI color suggestion — paused per user request.
            // setPaletteSuggestions(body.paletteSuggestions);

            if (!theme) {
                // First logo ever attached — the server seeded the default primary.
                setPrimaryHex(body.theme.primary);
            }
        } finally {
            setUploading(false);
        }
    }

    function applyColor(hex: string) {
        setPrimaryHex(hex);
        setProcessing(true);
        router.patch(
            route('theme.update'),
            { primary: hex },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setApplySuccess(true);
                    setTimeout(() => setApplySuccess(false), 3000);
                },
                onFinish: () => setProcessing(false),
            },
        );
    }

    const failingGuardMessages = (errors.primary_failing_guards ?? '').split(',').filter(Boolean) as GuardName[];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tema" />

            <div className="mx-auto max-w-2xl space-y-0 p-4">
                <h1 className="mb-1 text-left text-2xl font-bold">Tema</h1>
                <p className="text-muted-foreground mb-6 text-left text-sm">
                    Unggah logo dan pilih satu warna utama. Sisanya diturunkan otomatis untuk mode terang dan gelap.
                </p>

                {!theme ? (
                    <div className="border-foreground mb-6 border-2 p-4 text-left">
                        <p className="text-sm">Tema belum diatur. Organisasi ini masih memakai warna bawaan RukunMuda.</p>
                    </div>
                ) : null}

                {/* ---- Upload ---- */}
                <section className="border-foreground border-t-2 py-6 text-left">
                    <h2 className="mb-3 text-xs font-bold tracking-wide uppercase">Logo</h2>

                    <label className="border-foreground block w-fit cursor-pointer rounded-none border border-dashed px-4 py-3 text-left text-sm">
                        {uploading ? 'Mengunggah…' : 'Pilih file PNG atau SVG'}
                        <input
                            type="file"
                            accept="image/png,image/svg+xml"
                            className="hidden"
                            disabled={uploading}
                            onChange={(e) => {
                                const file = e.target.files?.[0];
                                if (file) void handleLogoSelected(file);
                                e.target.value = '';
                            }}
                        />
                    </label>

                    {uploadErrors.map((message, i) => (
                        <p key={i} className="mt-2 text-left text-sm text-red-600">
                            {message}
                        </p>
                    ))}

                    {logo ? (
                        <div className="mt-4 flex flex-wrap gap-4">
                            <LogoSwatch label="Latar" background={FIXED.light.bg} src={logo.mark} />
                            <LogoSwatch label="Permukaan" background={FIXED.light.surface} src={logo.mark} />
                            <LogoSwatch label="Aksen" background={derived.accent} src={guards.accentVsBg ? logo.mark : logo.mono} />
                        </div>
                    ) : null}

                    {/* AI color suggestion ("Warna dari logo") — paused per user request, kept for later re-enable.
                    {paletteSuggestions.length > 0 ? (
                        <div className="mt-4">
                            <p className="mb-2 text-left text-xs font-bold tracking-wide uppercase">Warna dari logo</p>
                            <div className="flex flex-wrap gap-3">
                                {paletteSuggestions.map((candidate) => (
                                    <button
                                        key={candidate.hex}
                                        type="button"
                                        onClick={() => setPrimaryHex(candidate.passesAllGuards ? candidate.hex : candidate.nearestPassing)}
                                        className="border-foreground flex items-center gap-2 rounded-none border px-3 py-2 text-left text-xs"
                                    >
                                        <span className="border-foreground h-5 w-5 border" style={{ backgroundColor: candidate.hex }} />
                                        {candidate.hex}
                                        <span>{candidate.passesAllGuards ? '✓' : '✗'}</span>
                                    </button>
                                ))}
                            </div>
                        </div>
                    ) : null}
                    */}
                </section>

                {/* ---- Color ---- */}
                <section className="border-foreground border-t-2 py-6 text-left">
                    <h2 className="mb-3 text-xs font-bold tracking-wide uppercase">Warna utama</h2>

                    <div className="flex items-end gap-3">
                        <div className="grid gap-2">
                            <Label htmlFor="primary" className="text-left">
                                Kode warna
                            </Label>
                            <Input id="primary" className="w-40 rounded-none" value={primaryHex} onChange={(e) => setPrimaryHex(e.target.value)} />
                        </div>
                        <span className="border-foreground mb-1 h-10 w-10 border" style={{ backgroundColor: primaryHex }} />
                    </div>

                    {!passes ? (
                        <div className="border-foreground bg-muted/40 mt-4 space-y-2 border p-3">
                            {failing.map((name) => (
                                <p key={name} className="text-left text-sm">
                                    {GUARD_LABELS[name]}
                                </p>
                            ))}
                            {suggestedFix ? (
                                <Button type="button" variant="outline" className="rounded-none" onClick={() => setPrimaryHex(suggestedFix)}>
                                    Gunakan {suggestedFix}
                                </Button>
                            ) : null}
                        </div>
                    ) : null}

                    {failingGuardMessages.length > 0 ? (
                        <div className="mt-4 space-y-2 border border-red-600 bg-red-50 p-3">
                            <p className="text-left text-sm text-red-700">{errors.primary}</p>
                            {failingGuardMessages.map((name) => (
                                <p key={name} className="text-left text-sm text-red-700">
                                    {GUARD_LABELS[name] ?? name}
                                </p>
                            ))}
                            {errors.primary_nearest_passing ? (
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="rounded-none"
                                    onClick={() => setPrimaryHex(errors.primary_nearest_passing as string)}
                                >
                                    Gunakan {errors.primary_nearest_passing}
                                </Button>
                            ) : null}
                        </div>
                    ) : null}

                    {history.length > 0 ? (
                        <div className="mt-4">
                            <p className="mb-2 text-left text-xs font-bold tracking-wide uppercase">Tema sebelumnya</p>
                            <div className="divide-border border-foreground divide-y border">
                                {history.map((entry, i) => (
                                    <button
                                        key={i}
                                        type="button"
                                        onClick={() => setPrimaryHex(entry.primary)}
                                        className="flex w-full items-center gap-3 px-3 py-2 text-left text-sm"
                                    >
                                        <span className="border-foreground h-5 w-5 border" style={{ backgroundColor: entry.primary }} />
                                        <span>{entry.primary}</span>
                                        <span className="text-muted-foreground ml-auto text-xs">{entry.actorName}</span>
                                    </button>
                                ))}
                            </div>
                        </div>
                    ) : null}
                </section>

                {/* ---- Preview ---- */}
                <section className="border-foreground border-t-2 py-6 text-left">
                    <div className="mb-3 flex items-center justify-between">
                        <h2 className="text-xs font-bold tracking-wide uppercase">Pratinjau</h2>
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant={previewMode === 'light' ? 'default' : 'outline'}
                                className="rounded-none"
                                onClick={() => setPreviewMode('light')}
                            >
                                Terang
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant={previewMode === 'dark' ? 'default' : 'outline'}
                                className="rounded-none"
                                onClick={() => setPreviewMode('dark')}
                            >
                                Gelap
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant={grayscale ? 'default' : 'outline'}
                                className="rounded-none"
                                onClick={() => setGrayscale((v) => !v)}
                            >
                                Hitam-putih
                            </Button>
                        </div>
                    </div>

                    <PhonePreview
                        mode={previewMode}
                        grayscale={grayscale}
                        primaryHex={primaryHex}
                        organizationName={organizationName}
                        logoMark={logo?.mark ?? null}
                    />

                    {grayscale && kasGrayscaleFails ? (
                        <p className="mt-3 text-left text-sm text-red-600">
                            Kas tidak terbaca dalam hitam-putih — warna aksen gelap dan warna pengeluaran terlalu mirip.
                        </p>
                    ) : null}
                </section>

                <div className="border-foreground bg-background sticky bottom-0 border-t-2 py-4">
                    {applySuccess ? <p className="mb-2 text-left text-sm text-green-700">Tema diterapkan.</p> : null}
                    <Button
                        type="button"
                        disabled={!logo || !passes || processing}
                        className="w-full rounded-none text-left"
                        onClick={() => applyColor(primaryHex)}
                    >
                        {!logo ? 'Unggah logo terlebih dahulu' : processing ? 'Menerapkan…' : 'Terapkan tema'}
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}

function LogoSwatch({ label, background, src }: { label: string; background: string; src: string }) {
    return (
        <div className="text-left">
            <div className="border-foreground flex h-20 w-20 items-center justify-center border" style={{ backgroundColor: background }}>
                <img src={src} alt={label} className="max-h-14 max-w-14" />
            </div>
            <p className="text-muted-foreground mt-1 text-xs">{label}</p>
        </div>
    );
}

function PhonePreview({
    mode,
    grayscale,
    primaryHex,
    organizationName,
    logoMark,
}: {
    mode: 'light' | 'dark';
    grayscale: boolean;
    primaryHex: string;
    organizationName: string;
    logoMark: string | null;
}) {
    const colors = mode === 'light' ? deriveLight(primaryHex) : deriveDark(primaryHex);
    const fixed = FIXED[mode];

    return (
        <div
            className="border-foreground mx-auto w-[220px] border-2"
            style={{ filter: grayscale ? 'grayscale(1)' : undefined, backgroundColor: fixed.bg }}
        >
            {/* Home */}
            <div className="p-3" style={{ borderBottom: `2px solid ${fixed.rule}` }}>
                <div className="mb-2 flex items-center gap-2">
                    {logoMark ? (
                        <img src={logoMark} className="h-5 w-5" alt="" />
                    ) : (
                        <div className="h-5 w-5" style={{ backgroundColor: fixed.text }} />
                    )}
                    <span className="text-[10px] font-bold" style={{ color: fixed.text }}>
                        {organizationName}
                    </span>
                </div>
                <p className="text-[9px]" style={{ color: fixed.textMuted }}>
                    Saldo kas
                </p>
                <p className="text-lg font-extrabold" style={{ color: fixed.text }}>
                    Rp8.450.000
                </p>
                <button
                    className="mt-2 w-full px-2 py-1.5 text-left text-[10px] font-extrabold"
                    style={{ backgroundColor: colors.accent, color: colors.onAccent }}
                >
                    Lihat transparansi
                </button>
            </div>

            {/* Kas rows */}
            <div className="p-3" style={{ borderBottom: `2px solid ${fixed.rule}` }}>
                <div className="flex items-center justify-between border-b py-1.5" style={{ borderColor: fixed.divider }}>
                    <span className="text-[10px]" style={{ color: fixed.text }}>
                        Iuran anggota
                    </span>
                    <span className="text-[10px] font-extrabold" style={{ color: fixed.text }}>
                        +Rp500.000
                    </span>
                </div>
                <div className="flex items-center justify-between py-1.5">
                    <span className="text-[10px]" style={{ color: fixed.text }}>
                        Konsumsi
                    </span>
                    <span className="text-[10px] font-extrabold" style={{ color: fixed.expense }}>
                        −Rp350.000
                    </span>
                </div>
            </div>

            {/* Poster block */}
            <div className="flex h-16 items-center justify-center" style={{ backgroundColor: colors.accent }}>
                <span className="text-[11px] font-extrabold" style={{ color: colors.onAccent }}>
                    Laporan Agustus terbit
                </span>
            </div>
        </div>
    );
}

function rasterizeSvgToPng(file: File): Promise<File> {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = 1024;
                canvas.height = 1024;
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    reject(new Error('Canvas not supported'));

                    return;
                }
                const scale = Math.min(1024 / img.width, 1024 / img.height);
                const w = img.width * scale;
                const h = img.height * scale;
                ctx.drawImage(img, (1024 - w) / 2, (1024 - h) / 2, w, h);
                canvas.toBlob((blob) => {
                    if (!blob) {
                        reject(new Error('Rasterization failed'));

                        return;
                    }
                    resolve(new File([blob], 'logo.png', { type: 'image/png' }));
                }, 'image/png');
            };
            img.onerror = reject;
            img.src = reader.result as string;
        };
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}
