<?php

namespace App\Notifications;

use App\Models\LibraryLoan;
use Illuminate\Notifications\Notification;

class LibraryLoanOverdueNotification extends Notification
{
    public function __construct(public LibraryLoan $loan) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $title = $this->loan->copy?->book?->title ?? 'a library book';

        return [
            'title' => 'Library book overdue',
            'body' => '"'.$title.'" was due on '.$this->loan->due_at->toDateString()
                .'. Please return it to avoid fines.',
            'loan_id' => $this->loan->id,
            'url' => url('/'),
        ];
    }
}
