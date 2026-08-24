<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationLandingController extends Controller
{
    /**
     * A public, unauthenticated landing page for an organization. Only
     * safe-to-publish fields are selected here — never the full model,
     * and never anything financial (that's gated behind Phase 4's
     * transparency visibility model, which doesn't exist yet).
     */
    public function show(Organization $organization): Response
    {
        $upcomingEvents = $organization->events()
            ->whereIn('status', [EventStatus::Planned, EventStatus::Ongoing])
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->limit(5)
            ->get(['id', 'title', 'start_at', 'location'])
            ->map(fn (Event $event) => [
                'title' => $event->title,
                'startAt' => $event->start_at->toIso8601String(),
                'location' => $event->location,
            ]);

        $announcements = $organization->announcements()
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get(['title', 'body', 'published_at'])
            ->map(fn (Announcement $announcement) => [
                'title' => $announcement->title,
                'publishedAt' => $announcement->published_at->toIso8601String(),
                'excerpt' => Str::limit(strip_tags($announcement->body), 160),
            ]);

        return Inertia::render('organizations/landing', [
            'organization' => [
                'name' => $organization->name,
                'memberCount' => $organization->memberships()->count(),
            ],
            'upcomingEvents' => $upcomingEvents,
            'announcements' => $announcements,
        ]);
    }
}
