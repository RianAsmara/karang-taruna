<?php

namespace App\Http\Controllers;

use App\Enums\EventParticipantStatus;
use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EventParticipantController extends Controller
{
    public function store(Event $event): RedirectResponse
    {
        $this->authorize('create', [EventParticipant::class, $event]);

        $membership = Auth::user()->membershipIn($event->organization);

        if ($event->participants()->where('membership_id', $membership->id)->exists()) {
            throw ValidationException::withMessages([
                'participant' => 'Anda sudah terdaftar di kegiatan ini.',
            ]);
        }

        $event->participants()->create([
            'membership_id' => $membership->id,
            'status' => EventParticipantStatus::Registered,
        ]);

        return back();
    }

    public function destroy(Event $event, EventParticipant $participant): RedirectResponse
    {
        $this->authorize('delete', $participant);

        $participant->delete();

        return back();
    }
}
