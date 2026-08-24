<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventTask\StoreEventTaskRequest;
use App\Http\Requests\EventTask\UpdateEventTaskRequest;
use App\Http\Requests\EventTask\UpdateEventTaskStatusRequest;
use App\Models\Event;
use App\Models\EventTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class EventTaskController extends Controller
{
    public function store(StoreEventTaskRequest $request, Event $event): RedirectResponse
    {
        $event->tasks()->create([
            ...$request->validated(),
            'created_by' => Auth::id(),
        ]);

        return back();
    }

    public function update(UpdateEventTaskRequest $request, Event $event, EventTask $task): RedirectResponse
    {
        $task->update($request->validated());

        return back();
    }

    public function updateStatus(UpdateEventTaskStatusRequest $request, Event $event, EventTask $task): RedirectResponse
    {
        $task->update(['status' => $request->string('status')->value()]);

        return back();
    }

    public function destroy(Event $event, EventTask $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return back();
    }
}
