<?php

namespace App\Actions\Theme;

use App\Models\Organization;
use App\Models\OrganizationTheme;
use App\Support\Theme\LogoProcessor;
// use App\Support\Theme\PaletteExtractor; // AI color suggestion — paused per user request, kept for later re-enable.
use App\Support\Theme\ThemeColorEngine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Processes and stores a logo upload, creating the organization's theme
 * row if this is its first (with the shipped default primary color, since
 * a theme can't exist without one — the user picks their own color
 * separately in the Color step) or replacing just the logo derivatives if
 * a theme already exists, preserving whatever color is set.
 */
class AttachOrganizationLogoAction
{
    /** The shipped default — used only as the initial primary_hex when a logo is attached before any color has ever been chosen. */
    private const DEFAULT_PRIMARY = '#ec3013';

    public function __construct(
        private readonly LogoProcessor $logoProcessor,
        // private readonly PaletteExtractor $paletteExtractor, // AI color suggestion — paused per user request, kept for later re-enable.
    ) {}

    /**
     * @return array{theme: OrganizationTheme, paletteSuggestions: list<array{hex: string, passesAllGuards: bool, guards: array<string, bool>, nearestPassing: string}>}
     */
    public function handle(Organization $organization, UploadedFile $logo, UploadedFile $source): array
    {
        $sourceImage = imagecreatefrompng($logo->getRealPath());

        if ($sourceImage === false) {
            throw new RuntimeException('Uploaded logo could not be read as PNG.');
        }

        $derivatives = $this->logoProcessor->process($sourceImage);
        // AI color suggestion — paused per user request. Was:
        // $paletteSuggestions = $this->paletteExtractor->extract($derivatives['mark3x']);
        $paletteSuggestions = [];

        $disk = config('filesystems.default');
        $prefix = "theme-logos/{$organization->id}";

        $paths = [
            'logo_source_path' => $this->storeUploadedFile($disk, "{$prefix}/source", $source),
            'logo_source_mime' => $source->getMimeType() ?? 'application/octet-stream',
            'logo_mark_1x_path' => $this->storeGdImage($disk, "{$prefix}/mark@1x.png", $derivatives['mark1x']),
            'logo_mark_2x_path' => $this->storeGdImage($disk, "{$prefix}/mark@2x.png", $derivatives['mark2x']),
            'logo_mark_3x_path' => $this->storeGdImage($disk, "{$prefix}/mark@3x.png", $derivatives['mark3x']),
            'logo_icon_path' => $this->storeGdImage($disk, "{$prefix}/icon-1024.png", $derivatives['icon']),
            'logo_mono_path' => $this->storeGdImage($disk, "{$prefix}/mono.png", $derivatives['mono']),
            'disk' => $disk,
        ];

        $theme = DB::transaction(function () use ($organization, $paths) {
            $existing = $organization->theme;

            if ($existing !== null) {
                $existing->update($paths);

                return $existing;
            }

            $derived = ThemeColorEngine::derive(self::DEFAULT_PRIMARY);

            return $organization->theme()->create([
                ...$paths,
                'primary_hex' => self::DEFAULT_PRIMARY,
                'color_light' => $derived['light'],
                'color_dark' => $derived['dark'],
            ]);
        });

        return ['theme' => $theme, 'paletteSuggestions' => $paletteSuggestions];
    }

    private function storeUploadedFile(string $disk, string $path, UploadedFile $file): string
    {
        $stored = $file->storeAs($path, 'original.'.$file->getClientOriginalExtension(), $disk);

        if ($stored === false) {
            throw new RuntimeException("Unable to store uploaded file at {$path}.");
        }

        return $stored;
    }

    private function storeGdImage(string $disk, string $path, \GdImage $image): string
    {
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();

        if ($bytes === false) {
            throw new RuntimeException("Unable to encode PNG for {$path}.");
        }

        Storage::disk($disk)->put($path, $bytes);

        return $path;
    }
}
