<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Event\CheckInAttendanceAction;
use App\Enums\AttendanceMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Support\ReportQrCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function store(Event $event, CheckInAttendanceAction $checkIn): JsonResponse
    {
        $this->authorize('checkIn', [Attendance::class, $event]);

        $membership = Auth::user()->membershipIn($event->organization);
        $checkIn->handle($event, $membership, AttendanceMethod::Manual);

        return response()->json(['message' => 'Kehadiran Anda tercatat.']);
    }

    public function index(Event $event): AnonymousResourceCollection
    {
        $this->authorize('manage', [Attendance::class, $event]);

        $event->load('attendanceSession.attendances.membership.user:id,name');
        $session = $event->attendanceSession;
        $attendances = $session === null ? collect() : $session->attendances;

        return AttendanceResource::collection($attendances->sortBy('checked_in_at')->values());
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
