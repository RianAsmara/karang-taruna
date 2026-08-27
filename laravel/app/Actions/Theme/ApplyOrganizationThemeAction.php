<?php

namespace App\Actions\Theme;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationTheme;
use App\Models\User;
use App\Support\Theme\ThemeColorEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Shared between the web builder page and the api/v1 theme endpoint (same
 * shape as ViewOrganizationOverviewAction) so "guard-checked, audited"
 * can't drift between the two entry points. This is the only thing that
 * decides whether a submitted primary color may be persisted — never trust
 * a client's copy of the guard math (resources/js/lib/theme/derive.ts is
 * for instant preview only).
 */
class ApplyOrganizationThemeAction
{
    /**
     * @throws ValidationException if no logo has been attached yet, or the
     *                             primary fails a guard — a guard failure's message bag also
     *                             carries `primary_failing_guards` (comma-joined guard names)
     *                             and `primary_nearest_passing` (the one-tap-fix hex) so the
     *                             picker can explain itself and offer the fix, per
     *                             docs/design/docs/theme-builder.md § Guards.
     */
    public function handle(Organization $organization, User $actor, string $primaryHex): OrganizationTheme
    {
        $existing = $organization->theme;

        if ($existing === null) {
            throw ValidationException::withMessages([
                'primary' => 'Unggah logo terlebih dahulu sebelum menerapkan warna.',
            ]);
        }

        $failing = ThemeColorEngine::failingGuards($primaryHex);

        if ($failing !== []) {
            throw ValidationException::withMessages([
                'primary' => 'Warna ini tidak lolos pemeriksaan keterbacaan.',
                'primary_failing_guards' => implode(',', $failing),
                'primary_nearest_passing' => ThemeColorEngine::nearestPassing($primaryHex),
            ]);
        }

        return DB::transaction(function () use ($organization, $actor, $primaryHex, $existing) {
            $derived = ThemeColorEngine::derive($primaryHex);
            $previousPrimary = $existing->primary_hex;

            $existing->update([
                'primary_hex' => $primaryHex,
                'color_light' => $derived['light'],
                'color_dark' => $derived['dark'],
            ]);
            $theme = $existing;

            AuditLog::create([
                'organization_id' => $organization->id,
                'actor_id' => $actor->id,
                'action' => 'organization.theme_applied',
                'model_type' => Organization::class,
                'model_id' => $organization->id,
                'previous_values' => ['primary' => $previousPrimary],
                'new_values' => ['primary' => $primaryHex],
            ]);

            return $theme;
        });
    }
}
