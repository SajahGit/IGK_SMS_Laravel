<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class LearningMaterialAccessNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $body,
        public string $relativeUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->relativeUrl,
        ];
    }
}
