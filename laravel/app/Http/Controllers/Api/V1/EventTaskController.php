<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventTask\StoreEventTaskRequest;
use App\Http\Resources\EventTaskResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
}
