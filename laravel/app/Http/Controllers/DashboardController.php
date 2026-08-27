<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\EventTaskStatus;
use App\Enums\MemberDueType;
use App\Models\EventTask;
use App\Models\MemberDue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The web equivalent of mobile's Home screen. Deliberately NOT gated
     * by the `current-org` middleware (unlike every other org-scoped
     * controller) — a user with no organization yet must still reach
     * this route to see the "create organization" card, exactly like the
     * route it replaces.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $membership = $user->currentMembership();

        if ($membership === null) {
            return Inertia::render('dashboard');
        }

        $organization = $membership->organization;

        $nextEvent = $organization->events()
            ->whereIn('status', [EventStatus::Planned, EventStatus::Ongoing])
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->first();

        $myTasks = EventTask::whereHas('event', fn ($query) => $query->where('organization_id', $organization->id))
            ->where('assignee_membership_id', $membership->id)
            ->whereNot('status', EventTaskStatus::Done)
            ->with('event:id,title')
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        $currentPeriod = Carbon::now()->startOfMonth();
        $myDue = MemberDue::where('organization_id', $organization->id)
            ->where('membership_id', $membership->id)
            ->where('type', MemberDueType::Monthly)
            ->whereDate('period', $currentPeriod->toDateString())
            ->first();

        $announcement = $organization->announcements()
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->first();

        return Inertia::render('dashboard', [
            'summary' => $organization->transparencySummary(),
            'nextEvent' => $nextEvent ? [
                'id' => $nextEvent->id,
                'title' => $nextEvent->title,
                'startAt' => $nextEvent->start_at->toIso8601String(),
                'location' => $nextEvent->location,
            ] : null,
            'myTasks' => $myTasks->map(fn (EventTask $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'statusLabel' => $task->status->label(),
                'dueDate' => $task->due_date?->toIso8601String(),
                'eventTitle' => $task->event->title,
            ])->values(),
            'myDue' => $myDue && ! $myDue->is_exempt && ! $myDue->isPaid() ? [
                'period' => $myDue->period->toDateString(),
                'amountOutstanding' => $myDue->amountOutstanding(),
                'isAwaitingConfirmation' => $myDue->isAwaitingConfirmation(),
            ] : null,
            'announcement' => $announcement ? [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'publishedAt' => $announcement->published_at->toIso8601String(),
            ] : null,
        ]);
    }
}
