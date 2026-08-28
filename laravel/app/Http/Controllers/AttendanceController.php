<?php

namespace App\Http\Controllers;

use App\Actions\Event\CheckInAttendanceAction;
use App\Enums\AttendanceMethod;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Support\ReportQrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AttendanceController extends Controller
{
    /**
     * Self check-in — "Konfirmasi kehadiran saya", mobile-screens.md's
     * screen 05 action bar button, and its web equivalent.
     */
    public function store(Event $event, CheckInAttendanceAction $checkIn): RedirectResponse
    {
        $this->authorize('checkIn', [Attendance::class, $event]);

        $membership = Auth::user()->membershipIn($event->organization);
        $checkIn->handle($event, $membership, AttendanceMethod::Manual);

        return back()->with('success', 'Kehadiran Anda tercatat.');
    }

    public function index(Event $event): InertiaResponse
    {
        $this->authorize('manage', [Attendance::class, $event]);

        $event->load('attendanceSession.attendances.membership.user:id,name');
        $session = $event->attendanceSession;
        $attendances = $session === null ? collect() : $session->attendances;

        return Inertia::render('events/attendance', [
            'event' => ['id' => $event->id, 'title' => $event->title],
            'attendances' => $attendances
                ->sortBy('checked_in_at')
                ->values()
                ->map(fn (Attendance $attendance) => [
                    'id' => $attendance->id,
                    'name' => $attendance->membership->user->name,
                    'method' => $attendance->method->value,
                    'methodLabel' => $attendance->method->label(),
                    'checkedInAt' => $attendance->checked_in_at->toIso8601String(),
                ]),
        ]);
    }

    public function qr(Event $event): Response
    {
        $this->authorize('manage', [Attendance::class, $event]);

        $session = AttendanceSession::firstOrCreate(
            ['event_id' => $event->id],
            ['created_by' => Auth::id()],
        );

        return ReportQrCode::png(url("/attendance/{$session->qr_token}"));
    }
}
