<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Event\CreateEventAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Event::class, $organization]);

        $events = $organization->events()->orderByDesc('start_at')->get();

        return EventResource::collection($events);
    }

    public function store(StoreEventRequest $request, Organization $organization, CreateEventAction $createEvent): JsonResponse
    {
        $event = $createEvent->handle($organization, Auth::user(), $request->validated());

        return (new EventResource($event->refresh()))->response()->setStatusCode(201);
    }

    public function show(Event $event): JsonResource
    {
        $this->authorize('view', $event);

        $event->load([
            'pic.user:id,name',
            'tasks.assignee.user:id,name',
            'participants.membership.user:id,name',
        ]);

        return new EventResource($event);
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResource
    {
        $event->update($request->validated());

        return new EventResource($event);
    }
}
