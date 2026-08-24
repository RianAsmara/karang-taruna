<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventCommittee\StoreEventCommitteeRequest;
use App\Models\Event;
use App\Models\EventCommittee;
use Illuminate\Http\RedirectResponse;

class EventCommitteeController extends Controller
{
    public function store(StoreEventCommitteeRequest $request, Event $event): RedirectResponse
    {
        $event->committees()->create($request->validated());

        return back();
    }

    public function destroy(Event $event, EventCommittee $committee): RedirectResponse
    {
        $this->authorize('update', $event);

        $committee->delete();

        return back();
    }
}
