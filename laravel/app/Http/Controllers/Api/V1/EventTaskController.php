<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Event\UpdateEventTaskStatusAction;
use App\Enums\EventTaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventTask\StoreEventTaskRequest;
use App\Http\Requests\EventTask\UpdateEventTaskStatusRequest;
use App\Http\Resources\EventTaskResource;
use App\Models\Event;
use App\Models\EventTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class EventTaskController extends Controller
{
    public function index(Event $event): AnonymousResourceCollection
    {
        $this->authorize('view', $event);

        $tasks = $event->tasks()->with('assignee.user:id,name')->get();

        return EventTaskResource::collection($tasks);
    }

    public function store(StoreEventTaskRequest $request, Event $event): JsonResponse
    {
        $task = $event->tasks()->create([
            ...$request->validated(),
            'created_by' => Auth::id(),
        ]);

        return (new EventTaskResource($task->refresh()->load('assignee.user:id,name')))
            ->response()
            ->setStatusCode(201);
    }

    public function updateStatus(UpdateEventTaskStatusRequest $request, Event $event, EventTask $task, UpdateEventTaskStatusAction $updateStatus): JsonResource
    {
        $updateStatus->handle($task, EventTaskStatus::from($request->string('status')->value()));

        return new EventTaskResource($task->refresh()->load('assignee.user:id,name'));
    }
}
