<?php

namespace App\Http\Controllers;

use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Announcement\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(Organization $organization): Response
    {
        $this->authorize('viewAny', [Announcement::class, $organization]);

        $announcements = $organization->announcements()
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (Announcement $announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'publishedAt' => $announcement->published_at?->toIso8601String(),
            ]);

        return Inertia::render('announcements/index', [
            'announcements' => $announcements,
            'canCreate' => Auth::user()->can('create', [Announcement::class, $organization]),
        ]);
    }

    public function create(Organization $organization): Response
    {
        $this->authorize('create', [Announcement::class, $organization]);

        return Inertia::render('announcements/create');
    }

    public function store(StoreAnnouncementRequest $request, Organization $organization): RedirectResponse
    {
        $announcement = $organization->announcements()->create([
            ...$request->validated(),
            'created_by' => Auth::id(),
        ]);

        return to_route('announcements.show', $announcement);
    }

    public function show(Announcement $announcement): Response
    {
        $this->authorize('view', $announcement);

        $announcement->load('creator:id,name');

        return Inertia::render('announcements/show', [
            'announcement' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'publishedAt' => $announcement->published_at?->toIso8601String(),
                'authorName' => $announcement->creator->name,
            ],
            'canManage' => Auth::user()->can('update', $announcement),
        ]);
    }

    public function edit(Announcement $announcement): Response
    {
        $this->authorize('update', $announcement);

        return Inertia::render('announcements/edit', [
            'announcement' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'publishedAt' => $announcement->published_at?->toIso8601String(),
            ],
        ]);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($request->validated());

        return to_route('announcements.show', $announcement);
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->authorize('delete', $announcement);

        $announcement->delete();

        return to_route('announcements.index');
    }
}
