<?php

namespace App\Http\Controllers;

use App\Actions\Vote\CreateVoteAction;
use App\Actions\Vote\SubmitVoteResponseAction;
use App\Enums\VoteEligibleScope;
use App\Http\Requests\Vote\StoreVoteRequest;
use App\Http\Requests\Vote\StoreVoteResponseRequest;
use App\Http\Resources\VoteResource;
use App\Http\Resources\VoteResultResource;
use App\Models\Organization;
use App\Models\Vote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
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
            'canCreate' => Auth::user()->can('create', [Vote::class, $organization]),
        ]);
    }

    public function create(Organization $organization): Response
    {
        $this->authorize('create', [Vote::class, $organization]);

        return Inertia::render('votes/create', [
            'events' => $organization->events()->orderByDesc('start_at')->get(['id', 'title']),
            'eligibleScopes' => array_map(
                fn (VoteEligibleScope $s) => ['value' => $s->value, 'label' => $s->label()],
                VoteEligibleScope::cases(),
            ),
        ]);
    }

    public function store(StoreVoteRequest $request, Organization $organization, CreateVoteAction $createVote): RedirectResponse
    {
        $vote = $createVote->handle(
            $organization,
            $request->user(),
            $request->string('question')->value(),
            $request->string('description')->value() ?: null,
            $request->boolean('anonymous'),
            $request->boolean('editable'),
            (int) $request->integer('max_selections'),
            VoteEligibleScope::from($request->string('eligible_scope')->value()),
            $request->string('event_id')->value() ?: null,
            Carbon::parse($request->string('start_at')->value()),
            Carbon::parse($request->string('end_at')->value()),
            $request->input('options'),
        );

        return to_route('votes.show', $vote);
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
