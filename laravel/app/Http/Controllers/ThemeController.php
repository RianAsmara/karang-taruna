<?php

namespace App\Http\Controllers;

use App\Actions\Theme\ApplyOrganizationThemeAction;
use App\Actions\Theme\AttachOrganizationLogoAction;
use App\Http\Requests\Theme\AttachOrganizationLogoRequest;
use App\Http\Requests\Theme\UpdateOrganizationThemeRequest;
use App\Http\Resources\OrganizationThemeResource;
use App\Models\AuditLog;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The one-page Theme Builder — Upload, Color, and Preview are sections on
 * this single page, not separate routes (docs/design/docs/theme-builder.md
 * § Scope). Ketua-only, and never a superadmin (OrganizationPolicy::manageTheme):
 * the nav entry is simply absent for anyone else (rendered conditionally
 * in the sidebar), and this controller still enforces `manageTheme`
 * itself as the real boundary.
 */
class ThemeController extends Controller
{
    public function edit(Organization $organization): Response
    {
        $this->authorize('manageTheme', $organization);

        $theme = $organization->theme;

        return Inertia::render('organization/theme', [
            'organizationName' => $organization->name,
            'theme' => $theme ? (new OrganizationThemeResource($theme))->resolve() : null,
            'history' => $this->recentHistory($organization),
        ]);
    }

    public function uploadLogo(AttachOrganizationLogoRequest $request, Organization $organization, AttachOrganizationLogoAction $attachLogo): JsonResponse
    {
        $result = $attachLogo->handle($organization, $request->file('logo'), $request->file('source'));

        return response()->json([
            'theme' => (new OrganizationThemeResource($result['theme']->fresh()))->resolve(),
            'paletteSuggestions' => $result['paletteSuggestions'],
        ]);
    }

    public function update(UpdateOrganizationThemeRequest $request, Organization $organization, ApplyOrganizationThemeAction $applyTheme): RedirectResponse
    {
        $applyTheme->handle($organization, Auth::user(), $request->string('primary')->value());

        return back()->with('success', 'Tema diterapkan.');
    }

    /**
     * @return array<int, array{primary: string, actorName: string, appliedAt: string}>
     */
    private function recentHistory(Organization $organization): array
    {
        return AuditLog::query()
            ->where('organization_id', $organization->id)
            ->where('action', 'organization.theme_applied')
            ->with('actor:id,name')
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(function (AuditLog $log) {
                $primary = $log->new_values['primary'] ?? '';

                return [
                    'primary' => is_string($primary) ? $primary : '',
                    'actorName' => $log->actor->name,
                    'appliedAt' => $log->created_at->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }
}
