<?php

namespace App\Http\Controllers;

use App\Actions\Sponsor\RecordSponsorContributionAction;
use App\Actions\Sponsor\UpdateSponsorContributionStatusAction;
use App\Enums\SponsorContributionStatus;
use App\Enums\SponsorType;
use App\Http\Requests\Sponsor\StoreSponsorContributionRequest;
use App\Http\Requests\Sponsor\UpdateSponsorContributionStatusRequest;
use App\Http\Resources\SponsorContributionResource;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\Organization;
use App\Models\SponsorContribution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SponsorController extends Controller
{
    /**
     * Grouped by event, newest event first; an unassociated contribution
     * lands in a "Tanpa kegiatan" group — mobile-screens.md §23.
     */
    public function index(Organization $organization): Response
    {
        $this->authorize('viewAny', [SponsorContribution::class, $organization]);

        $contributions = $organization->sponsorContributions()
            ->with(['sponsor', 'event:id,title'])
            ->orderByDesc('created_at')
            ->get();

        // Sponsorship totals exclude Diajukan and Batal.
        $totalSupport = (int) $contributions
            ->whereIn('status', [SponsorContributionStatus::Setuju, SponsorContributionStatus::Diterima])
            ->sum('amount');

        return Inertia::render('sponsors/index', [
            'contributions' => SponsorContributionResource::collection($contributions)->resolve(),
            'totalSupport' => $totalSupport,
            'canCreate' => Auth::user()->can('create', [SponsorContribution::class, $organization]),
        ]);
    }

    public function create(Organization $organization): Response
    {
        $this->authorize('create', [SponsorContribution::class, $organization]);

        return Inertia::render('sponsors/create', [
            'types' => array_map(fn (SponsorType $t) => ['value' => $t->value, 'label' => $t->label()], SponsorType::cases()),
            'events' => $organization->events()->orderByDesc('start_at')->get(['id', 'title']),
        ]);
    }

    public function store(StoreSponsorContributionRequest $request, Organization $organization, RecordSponsorContributionAction $record): RedirectResponse
    {
        $contribution = $record->handle(
            $organization,
            $request->user(),
            $request->string('name')->value(),
            SponsorType::from($request->string('type')->value()),
            $request->filled('amount') ? (int) $request->integer('amount') : null,
            $request->string('description')->value() ?: null,
            $request->string('event_id')->value() ?: null,
            $request->string('contact_name')->value() ?: null,
            $request->string('contact_phone')->value() ?: null,
            $request->string('notes')->value() ?: null,
        );

        return to_route('sponsors.show', $contribution);
    }

    public function show(SponsorContribution $sponsor): Response
    {
        $this->authorize('view', $sponsor);

        $sponsor->load(['sponsor', 'event:id,title']);

        $history = $sponsor->sponsor->contributions()
            ->where('id', '!=', $sponsor->id)
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('sponsors/show', [
            'contribution' => (new SponsorContributionResource($sponsor))->resolve(),
            'history' => SponsorContributionResource::collection($history)->resolve(),
            'canManage' => Auth::user()->can('update', $sponsor),
            'accounts' => $sponsor->organization->financialAccounts()->orderBy('name')->get(['id', 'name']),
            'categories' => $sponsor->organization->financialCategories()
                ->where('transaction_type', 'INCOME')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function updateStatus(UpdateSponsorContributionStatusRequest $request, SponsorContribution $sponsor, UpdateSponsorContributionStatusAction $updateStatus): RedirectResponse
    {
        $account = $request->filled('financial_account_id')
            ? FinancialAccount::findOrFail($request->string('financial_account_id')->value())
            : null;
        $category = $request->filled('category_id')
            ? FinancialCategory::findOrFail($request->string('category_id')->value())
            : null;

        $updateStatus->handle(
            $sponsor,
            SponsorContributionStatus::from($request->string('status')->value()),
            $request->user(),
            $account,
            $category,
        );

        return back();
    }
}
