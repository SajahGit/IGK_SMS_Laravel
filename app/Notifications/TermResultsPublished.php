<?php

namespace App\Notifications;

use App\Models\SchoolClass;
use App\Models\Term;
use Illuminate\Notifications\Notification;

class TermResultsPublished extends Notification
{
    public function __construct(
        public Term $term,
        public SchoolClass $schoolClass
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Term results published',
            'body' => $this->term->name.' results are now available for '.$this->schoolClass->full_name.'.',
            'term_id' => $this->term->id,
            'class_id' => $this->schoolClass->id,
            'url' => in_array(strtolower((string) ($notifiable->role ?? '')), ['parent', 'guardian'], true)
                ? route('guardian.dashboard', [], false)
                : route('student.results', [], false),
            'published_at' => now()->toIso8601String(),
        ];
    }
}
