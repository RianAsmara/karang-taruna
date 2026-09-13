<?php

namespace App\Http\Controllers;

use App\Actions\Membership\DecideMembershipExitAction;
use App\Actions\Membership\RequestMembershipExitAction;
use App\Http\Requests\Membership\DecideMembershipExitRequest;
use App\Http\Requests\Membership\StoreMembershipExitRequest;
use App\Models\MembershipExitRequest;
use Illuminate\Http\RedirectResponse;

class MembershipExitRequestController extends Controller
{
    public function store(StoreMembershipExitRequest $request, RequestMembershipExitAction $requestExit): RedirectResponse
    {
        $requestExit->handle(
            $request->user()->currentMembership(),
            $request->string('reason')->value() ?: null,
        );

        return back();
    }

    public function decide(
        DecideMembershipExitRequest $request,
        MembershipExitRequest $exitRequest,
        DecideMembershipExitAction $decide,
    ): RedirectResponse {
        $decide->handle(
            $exitRequest,
            $request->user(),
            $request->boolean('approve'),
            $request->string('note')->value() ?: null,
        );

        return back();
    }
}
