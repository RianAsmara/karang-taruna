<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TransparencyController extends Controller
{
    /**
     * "Transparansi" — the answer to "kas sekarang berapa?" without
     * having to ask. Every member can see this; it only ever reflects
     * APPROVED transactions.
     */
    public function index(Organization $organization): Response
    {
        $this->authorize('viewAny', [FinancialTransaction::class, $organization]);

        return Inertia::render('transparency/index', [
            ...$organization->transparencySummary(),
            'canManageTransparency' => Auth::user()->isChairOf($organization),
            'publicTransparencyEnabled' => $organization->public_transparency_enabled,
            'publicUrl' => route('organizations.transparency', $organization->slug),
        ]);
    }

    public function toggle(Organization $organization): RedirectResponse
    {
        $this->authorize('manageOrganization', $organization);

        $organization->update([
            'public_transparency_enabled' => ! $organization->public_transparency_enabled,
        ]);

        return back();
    }
}
