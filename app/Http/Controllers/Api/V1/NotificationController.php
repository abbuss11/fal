<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $items = $user->notifications()
            ->latest()
            ->paginate(20);

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $items,
        ]);
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $notification = $user->notifications()->where('id', $notificationId)->firstOrFail();
        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marquee comme lue.',
        ]);
    }
}

