<?php

namespace App\Http\Controllers;

use App\Actions\Event\CheckInAttendanceAction;
use App\Enums\AttendanceMethod;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The landing page a QR-attendance code points to — mirrors
 * ReportQrCode's "QR points to the official record" pattern (master
 * prompt §28): a plain web URL, scannable by any camera, no app
 * install required. Deliberately not under the `current-org`
 * middleware group — the token itself identifies the event/organization,
 * independent of whichever org happens to be the scanning user's
 * "current" one.
 */
class AttendanceScanController extends Controller
{
    public function show(string $qrToken): Response
    {
        $session = AttendanceSession::where('qr_token', $qrToken)->with('event')->firstOrFail();
        $event = $session->event;

        $this->authorize('view', [Attendance::class, $event]);

        $membership = Auth::user()->membershipIn($event->organization);

        return Inertia::render('attendance/scan', [
            'event' => ['id' => $event->id, 'title' => $event->title, 'statusLabel' => $event->status->label()],
            'alreadyCheckedIn' => $session->attendances()->where('membership_id', $membership->id)->exists(),
            'canCheckIn' => Auth::user()->can('checkIn', [Attendance::class, $event]),
        ]);
    }

    public function store(string $qrToken, CheckInAttendanceAction $checkIn): RedirectResponse
    {
        $session = AttendanceSession::where('qr_token', $qrToken)->with('event')->firstOrFail();
        $event = $session->event;

        $this->authorize('checkIn', [Attendance::class, $event]);

        $membership = Auth::user()->membershipIn($event->organization);
        $checkIn->handle($event, $membership, AttendanceMethod::Qr);

        return back()->with('success', 'Kehadiran Anda tercatat.');
    }
}
