<?php

namespace App\Notifications;

use App\Models\MessageConversation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SchoolMessageReceived extends Notification
{
    use Queueable;

    public function __construct(private readonly MessageConversation $conversation) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $latestMessage = $this->conversation->messages()->with('sender:id,name,role')->latest()->first();
        $senderName = $latestMessage?->sender?->name ?? 'School Member';
        $senderRole = ucfirst(str_replace('-', ' ', $latestMessage?->sender?->role ?? 'User'));

        return [
            'title' => 'Private message from ' . $senderName . ' (' . $senderRole . ')',
            'body' => Str::limit($latestMessage?->body ?? 'You received a new private message.', 90),
            'url' => route('messages.show', $this->conversation),
        ];
    }
}
