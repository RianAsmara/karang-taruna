<?php

namespace App\Http\Controllers;

use App\Actions\Organization\CreateOrganizationAction;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use Illuminate\Http\RedirectResponse;

class OrganizationController extends Controller
{
    public function store(StoreOrganizationRequest $request, CreateOrganizationAction $createOrganization): RedirectResponse
    {
        $createOrganization->handle($request->user(), $request->string('name')->value());

        return to_route('dashboard');
    }
}
