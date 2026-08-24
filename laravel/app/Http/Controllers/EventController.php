<?php

namespace App\Http\Controllers;

use App\Actions\Event\CreateEventAction;
use App\Enums\EventLifecycleStage;
use App\Enums\EventStatus;
use App\Enums\EventTaskPriority;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Organization $organization): Response
    {
        $this->authorize('viewAny', [Event::class, $organization]);

        $events = $organization->events()
            ->orderByDesc('start_at')
            ->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'startAt' => $event->start_at->toIso8601String(),
                'status' => $event->status->value,
                'statusLabel' => $event->status->label(),
            ]);

        return Inertia::render('events/index', [
            'events' => $events,
            'canCreate' => Auth::user()->can('create', [Event::class, $organization]),
        ]);
    }

    public function create(Organization $organization): Response
    {
        $this->authorize('create', [Event::class, $organization]);

        return Inertia::render('events/create', [
            'members' => $this->memberOptions($organization),
        ]);
    }

    public function store(StoreEventRequest $request, Organization $organization, CreateEventAction $createEvent): RedirectResponse
    {
        $event = $createEvent->handle($organization, Auth::user(), $request->validated());

        return to_route('events.show', $event);
    }

    public function show(Event $event): Response
    {
        $this->authorize('view', $event);

        $event->load([
            'pic.user:id,name',
            'committees.membership.user:id,name',
            'tasks.assignee.user:id,name',
            'participants.membership.user:id,name',
        ]);

        return Inertia::render('events/show', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'location' => $event->location,
                'startAt' => $event->start_at->toIso8601String(),
                'endAt' => $event->end_at?->toIso8601String(),
                'status' => $event->status->value,
                'statusLabel' => $event->status->label(),
                'lifecycleStage' => $event->lifecycle_stage->value,
                'lifecycleStageLabel' => $event->lifecycle_stage->label(),
                'pic' => $event->pic ? ['id' => $event->pic->id, 'name' => $event->pic->user->name] : null,
            ],
            'committees' => $event->committees->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->membership->user->name,
                'roleTitle' => $c->role_title,
            ]),
            'tasks' => $event->tasks->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'status' => $t->status->value,
                'statusLabel' => $t->status->label(),
                'priority' => $t->priority->value,
                'priorityLabel' => $t->priority->label(),
                'dueDate' => $t->due_date?->toIso8601String(),
                'assignee' => $t->assignee ? ['id' => $t->assignee->id, 'name' => $t->assignee->user->name] : null,
            ]),
            'participants' => $event->participants->map(fn ($p) => [
                'id' => $p->id,
                'membershipId' => $p->membership_id,
                'name' => $p->membership->user->name,
                'status' => $p->status->value,
                'statusLabel' => $p->status->label(),
            ]),
            'members' => $this->memberOptions($event->organization),
            'canManage' => Auth::user()->can('update', $event),
            'canDelete' => Auth::user()->can('delete', $event),
            'currentMembershipId' => Auth::user()->membershipIn($event->organization)?->id,
            'taskPriorities' => array_map(
                fn (EventTaskPriority $p) => ['value' => $p->value, 'label' => $p->label()],
                EventTaskPriority::cases(),
            ),
            'budget' => $this->budgetSummary($event),
        ]);
    }

    public function edit(Event $event): Response
    {
        $this->authorize('update', $event);

        return Inertia::render('events/edit', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'location' => $event->location,
                'startAt' => $event->start_at->toIso8601String(),
                'endAt' => $event->end_at?->toIso8601String(),
                'status' => $event->status->value,
                'lifecycleStage' => $event->lifecycle_stage->value,
                'picMembershipId' => $event->pic_membership_id,
            ],
            'members' => $this->memberOptions($event->organization),
            'statuses' => array_map(
                fn (EventStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                EventStatus::cases(),
            ),
            'lifecycleStages' => array_map(
                fn (EventLifecycleStage $s) => ['value' => $s->value, 'label' => $s->label()],
                EventLifecycleStage::cases(),
            ),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $event->update($request->validated());

        return to_route('events.show', $event);
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return to_route('events.index');
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    private function memberOptions(Organization $organization): array
    {
        return $organization->memberships()
            ->with('user:id,name')
            ->get()
            ->map(fn ($m) => ['id' => $m->id, 'name' => $m->user->name])
            ->all();
    }

    /**
     * Actual figures (APPROVED transactions) are shown to any member —
     * financial transparency is a default. Planned figures (DRAFT/PENDING,
     * i.e. not-yet-final) are internal to the treasury team.
     *
     * @return array<string, mixed>
     */
    private function budgetSummary(Event $event): array
    {
        $actualIncome = (int) $event->financialTransactions()
            ->where('status', TransactionStatus::Approved)
            ->where('transaction_type', TransactionType::Income)
            ->sum('amount');

        $actualExpense = (int) $event->financialTransactions()
            ->where('status', TransactionStatus::Approved)
            ->where('transaction_type', TransactionType::Expense)
            ->sum('amount');

        $summary = [
            'actualIncome' => $actualIncome,
            'actualExpense' => $actualExpense,
            'actualNet' => $actualIncome - $actualExpense,
        ];

        if (Auth::user()->isTreasurerOf($event->organization)) {
            $plannedIncome = (int) $event->financialTransactions()
                ->whereIn('status', [TransactionStatus::Draft, TransactionStatus::Pending])
                ->where('transaction_type', TransactionType::Income)
                ->sum('amount');

            $plannedExpense = (int) $event->financialTransactions()
                ->whereIn('status', [TransactionStatus::Draft, TransactionStatus::Pending])
                ->where('transaction_type', TransactionType::Expense)
                ->sum('amount');

            $summary['plannedIncome'] = $plannedIncome;
            $summary['plannedExpense'] = $plannedExpense;
            $summary['varianceIncome'] = $actualIncome - $plannedIncome;
            $summary['varianceExpense'] = $actualExpense - $plannedExpense;
        }

        return $summary;
    }
}
