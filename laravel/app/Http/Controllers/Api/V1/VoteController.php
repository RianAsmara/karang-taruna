<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Vote\SubmitVoteResponseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vote\StoreVoteResponseRequest;
use App\Http\Resources\VoteResource;
use App\Http\Resources\VoteResultResource;
use App\Models\Organization;
use App\Models\Vote;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class VoteController extends Controller
{
    /**
     * Open votes first (soonest-closing at the top), then closed —
     * screen 26.
     */
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Vote::class, $organization]);

        $now = now();
        $votes = $organization->votes()
            ->with('options')
            ->get()
            ->sortBy(fn (Vote $vote) => [$vote->end_at->lt($now) ? 1 : 0, $vote->end_at])
            ->values();

        return VoteResource::collection($votes);
    }

    public function show(Vote $vote): JsonResource
    {
        $this->authorize('view', $vote);

        return new VoteResource($vote->load(['options', 'event:id,title']));
    }

    public function storeResponse(StoreVoteResponseRequest $request, Vote $vote, SubmitVoteResponseAction $submit): JsonResource
    {
        $membership = $request->user()->membershipIn($vote->organization);

        $submit->handle($vote, $membership, $request->input('option_ids'));

        return new VoteResource($vote->fresh()->load(['options', 'event:id,title']));
    }

    public function results(Vote $vote): JsonResource
    {
        $this->authorize('view', $vote);

        return new VoteResultResource($vote->load('options'));
    }
}
