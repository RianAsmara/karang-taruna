<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementController extends Controller
{
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Announcement::class, $organization]);

        $announcements = $organization->announcements()->orderByDesc('published_at')->get();

        return AnnouncementResource::collection($announcements);
    }

    public function show(Announcement $announcement): JsonResource
    {
        $this->authorize('view', $announcement);

        return new AnnouncementResource($announcement->load('creator:id,name'));
    }
}
