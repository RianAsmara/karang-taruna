<?php

namespace App\Http\Controllers;

use App\Actions\Invite\AcceptOrganizationInviteAction;
use App\Actions\Invite\CreateOrganizationInviteAction;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OrganizationInviteController extends Controller
{
    public function store(Request $request, Organization $organization, CreateOrganizationInviteAction $createInvite): RedirectResponse
    {
        $this->authorize('create', [OrganizationInvite::class, $organization]);

        $validated = $request->validate([
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $createInvite->handle($organization, $request->user(), $validated['max_uses'] ?? null);

        return back();
    }

    public function destroy(OrganizationInvite $invite): RedirectResponse
    {
        $this->authorize('revoke', $invite);

        $invite->update(['revoked_at' => now()]);

        return back();
    }

    /**
     * The link itself. Deliberately outside the `current-org` middleware —
     * the whole point is that the visitor may not belong to any organization
     * yet. Guests are sent to log in and returned here afterwards.
     */
    public function accept(string $token, AcceptOrganizationInviteAction $acceptInvite): RedirectResponse
    {
        $invite = OrganizationInvite::where('token', $token)->firstOrFail();

        try {
            $acceptInvite->handle($invite, Auth::user());
        } catch (ValidationException $e) {
            return to_route('dashboard')->with('error', $e->getMessage());
        }

        return to_route('dashboard');
    }
}
