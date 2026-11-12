<?php

namespace App\Notifications;

use App\Models\MessageConversation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

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
        return [
            'title' => 'New private message',
            'body' => 'You received a new message.',
            'url' => route('messages.show', $this->conversation),
        ];
    }
}
