<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function read(DatabaseNotification $notification): RedirectResponse
    {
        $ownedNotification = auth()->user()->notifications()->findOrFail($notification->id);
        $ownedNotification->markAsRead();

        return redirect()->to($ownedNotification->data['url'] ?? url('/'));
    }

    public function readAll(): RedirectResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
