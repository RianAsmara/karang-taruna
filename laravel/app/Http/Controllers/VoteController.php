<?php

namespace App\Http\Controllers;

use App\Actions\Vote\SubmitVoteResponseAction;
use App\Http\Requests\Vote\StoreVoteResponseRequest;
use App\Http\Resources\VoteResource;
use App\Http\Resources\VoteResultResource;
use App\Models\Organization;
use App\Models\Vote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class VoteController extends Controller
{
    /**
     * Open votes first (soonest-closing at the top), then closed —
     * mobile-screens.md §26.
     */
    public function index(Organization $organization): Response
    {
        $this->authorize('viewAny', [Vote::class, $organization]);

        $now = now();
        $votes = $organization->votes()
            ->with('options')
            ->get()
            ->sortBy(fn (Vote $vote) => [$vote->end_at->lt($now) ? 1 : 0, $vote->end_at])
            ->values();

        return Inertia::render('votes/index', [
            'votes' => VoteResource::collection($votes)->resolve(),
        ]);
    }

    public function show(Vote $vote): Response
    {
        $this->authorize('view', $vote);

        $vote->load(['options', 'event:id,title']);
        $user = Auth::user();
        $membership = $user->membershipIn($vote->organization);

        // "Results are live" once you've responded, or once the vote has
        // closed for everyone — mobile-screens.md §27's "Lihat hasil".
        $canSeeResults = ! $vote->isOpen() || ($membership && $vote->hasResponded($membership));

        return Inertia::render('votes/show', [
            'vote' => (new VoteResource($vote))->resolve(),
            'canRespond' => $user->can('respond', $vote),
            'results' => $canSeeResults ? (new VoteResultResource($vote))->resolve() : null,
        ]);
    }

    public function storeResponse(StoreVoteResponseRequest $request, Vote $vote, SubmitVoteResponseAction $submit): RedirectResponse
    {
        $membership = $request->user()->membershipIn($vote->organization);

        $submit->handle($vote, $membership, $request->input('option_ids'));

        return back();
    }
}
