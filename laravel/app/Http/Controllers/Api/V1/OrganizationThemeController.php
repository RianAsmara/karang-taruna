<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Theme\ApplyOrganizationThemeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Theme\UpdateOrganizationThemeRequest;
use App\Http\Resources\OrganizationThemeResource;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationThemeController extends Controller
{
    /**
     * What mobile calls at boot (docs/design/docs/theme-builder.md § Output
     * contract). Absent theme → 404, which the mobile client treats as
     * "silently fall back to the default red" — never a blocked boot.
     */
    public function show(Organization $organization): JsonResource
    {
        $this->authorize('view', $organization);

        abort_if($organization->theme === null, 404);

        return new OrganizationThemeResource($organization->theme);
    }

    /**
     * Kept for API completeness per the spec's literal "GET/PUT" — the web
     * builder page itself submits through its own web route
     * (routes/organization.php), both calling this same Action so the
     * guard-check-and-audit invariant never forks between the two.
     */
    public function update(UpdateOrganizationThemeRequest $request, Organization $organization, ApplyOrganizationThemeAction $applyTheme): JsonResource
    {
        $theme = $applyTheme->handle($organization, $request->user(), $request->string('primary')->value());

        return new OrganizationThemeResource($theme->fresh());
    }
}
