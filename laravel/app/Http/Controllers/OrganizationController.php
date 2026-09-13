<?php

namespace App\Http\Controllers;

use App\Actions\Organization\CreateOrganizationAction;
use App\Actions\Organization\SwitchOrganizationAction;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\SwitchOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class OrganizationController extends Controller
{
    public function store(StoreOrganizationRequest $request, CreateOrganizationAction $createOrganization): RedirectResponse
    {
        $createOrganization->handle($request->user(), $request->string('name')->value());

        return to_route('dashboard');
    }

    public function options(SwitchOrganizationAction $switchOrganization): JsonResponse
    {
        return response()->json([
            'organizations' => $switchOrganization->options(Auth::user()),
            'canCreate' => Auth::user()->can('create', Organization::class),
        ]);
    }

    public function switch(SwitchOrganizationRequest $request, SwitchOrganizationAction $switchOrganization): RedirectResponse
    {
        $switchOrganization->handle($request->user(), $request->string('organization_id')->value());

        return to_route('dashboard');
    }
}
