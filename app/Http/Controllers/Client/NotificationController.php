<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, string $notificationId): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->hasPermission('notifications.read'), 403);

        $notification = $user->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        $notification->markAsRead();

        return back()->with('status', 'Notification marquee comme lue.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->hasPermission('notifications.read'), 403);

        $user->unreadNotifications->markAsRead();

        return back()->with('status', 'Toutes les notifications ont ete marquees comme lues.');
    }
}

