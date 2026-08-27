<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Sponsor\RecordSponsorContributionAction;
use App\Actions\Sponsor\UpdateSponsorContributionStatusAction;
use App\Enums\SponsorContributionStatus;
use App\Enums\SponsorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sponsor\StoreSponsorContributionRequest;
use App\Http\Requests\Sponsor\UpdateSponsorContributionStatusRequest;
use App\Http\Resources\SponsorContributionResource;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\Organization;
use App\Models\SponsorContribution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class SponsorController extends Controller
{
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [SponsorContribution::class, $organization]);

        $contributions = $organization->sponsorContributions()
            ->with(['sponsor', 'event:id,title'])
            ->orderByDesc('created_at')
            ->get();

        return SponsorContributionResource::collection($contributions);
    }

    public function store(StoreSponsorContributionRequest $request, Organization $organization, RecordSponsorContributionAction $record): JsonResponse
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

        return (new SponsorContributionResource($contribution->load(['sponsor', 'event:id,title'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(SponsorContribution $sponsor): JsonResource
    {
        $this->authorize('view', $sponsor);

        return new SponsorContributionResource($sponsor->load(['sponsor', 'event:id,title']));
    }

    public function updateStatus(UpdateSponsorContributionStatusRequest $request, SponsorContribution $sponsor, UpdateSponsorContributionStatusAction $updateStatus): JsonResource
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

        return new SponsorContributionResource($sponsor->fresh()->load(['sponsor', 'event:id,title']));
    }
}
