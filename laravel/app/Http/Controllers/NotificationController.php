<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(): Response
    {
        $notifications = Auth::user()->notifications()->latest()->limit(50)->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'type' => class_basename($notification->type),
                'data' => $notification->data,
                'readAt' => $notification->read_at?->toIso8601String(),
                'createdAt' => $notification->created_at->toIso8601String(),
            ]);

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $record = Auth::user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        return back();
    }
}
