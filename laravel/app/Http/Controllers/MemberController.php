<?php

namespace App\Http\Controllers;

use App\Actions\Organization\AddMemberAction;
use App\Enums\OrganizationRole;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\UpdateMemberRoleRequest;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(Request $request, Organization $organization): Response
    {
        $this->authorize('viewAny', [OrganizationMembership::class, $organization]);

        $search = trim((string) $request->query('search', ''));

        $members = $organization->memberships()
            ->with('user:id,name,email')
            ->withSum('activityLogs as activity_points', 'points')
            ->when($search !== '', fn ($query) => $query->whereHas(
                'user',
                fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%'),
            ))
            ->orderBy('created_at')
            ->get()
            ->map(fn (OrganizationMembership $membership) => [
                'id' => $membership->id,
                'name' => $membership->user->name,
                'email' => $membership->user->email,
                'role' => $membership->role->value,
                'roleLabel' => $membership->role->label(),
                'joinedAt' => $membership->created_at->toIso8601String(),
                'isChair' => $membership->role === OrganizationRole::Ketua,
                'activityPoints' => (int) ($membership->activity_points ?? 0),
            ]);

        return Inertia::render('members/index', [
            'members' => $members,
            'roles' => array_map(
                fn (OrganizationRole $role) => ['value' => $role->value, 'label' => $role->label()],
                OrganizationRole::cases(),
            ),
            'canManageMembers' => Auth::user()->can('manageMembers', $organization),
            'filters' => ['search' => $search !== '' ? $search : null],
        ]);
    }

    public function store(StoreMemberRequest $request, Organization $organization, AddMemberAction $addMember): RedirectResponse
    {
        $addMember->handle(
            $organization,
            $request->string('email')->value(),
            OrganizationRole::from($request->string('role')->value()),
        );

        return back();
    }

    public function updateRole(UpdateMemberRoleRequest $request, OrganizationMembership $member): RedirectResponse
    {
        $member->update(['role' => $request->string('role')->value()]);

        return back();
    }

    public function destroy(OrganizationMembership $member): RedirectResponse
    {
        $this->authorize('delete', $member);

        $member->delete();

        return back();
    }
}
