<?php

namespace App\Http\Middleware;

use App\Http\Resources\OrganizationThemeResource;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $membership = $request->user()?->currentMembership();
        $theme = $membership?->organization->theme;

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            'currentOrganization' => $membership ? [
                'id' => $membership->organization->id,
                'name' => $membership->organization->name,
                'slug' => $membership->organization->slug,
                'role' => $membership->role->value,
                'roleLabel' => $membership->role->label(),
            ] : null,
            // Five separate places flash a message (attendance check-in, theme
            // save, invite failure, the no-organization redirect); none of them
            // reached the user before this, so a failed join or a successful
            // check-in both looked like nothing happened at all.
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'unreadNotificationsCount' => $request->user()?->unreadNotifications()->count() ?? 0,
            'organizationTheme' => $theme ? (new OrganizationThemeResource($theme))->resolve() : null,
        ]);
    }
}
