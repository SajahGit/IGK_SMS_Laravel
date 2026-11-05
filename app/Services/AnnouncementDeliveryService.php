<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementEmail;
use App\Notifications\AnnouncementPublished;
use Illuminate\Database\Eloquent\Builder;

class AnnouncementDeliveryService
{
    public function deliver(Announcement $announcement): bool
    {
        if (
            ! $announcement->published_at
            || $announcement->published_at->isFuture()
            || ($announcement->expires_at && $announcement->expires_at->isPast())
            || $announcement->notifications_sent_at !== null
        ) {
            return false;
        }

        $recipients = $this->recipientQuery($announcement)->orderBy('id')->get();
        foreach ($recipients as $recipient) {
            $recipient->notify(new AnnouncementPublished($announcement));
            $recipient->notify(new AnnouncementEmail($announcement));
        }

        $announcement->forceFill(['notifications_sent_at' => now()])->saveQuietly();

        return true;
    }

    public function deliverDue(): int
    {
        $delivered = 0;
        Announcement::where('published_at', '<=', now())
            ->whereNull('notifications_sent_at')
            ->orderBy('id')
            ->get()
            ->each(function (Announcement $announcement) use (&$delivered) {
                if ($this->deliver($announcement)) {
                    $delivered++;
                }
            });

        return $delivered;
    }

    private function recipientQuery(Announcement $announcement): Builder
    {
        $query = User::query()->where('is_active', true);
        if ($announcement->target_role !== 'all') {
            $query->where('role', $announcement->target_role);
        }

        if ($announcement->class_id || $announcement->program_id) {
            $query->where(function (Builder $associated) use ($announcement) {
                $associated->whereHas('student', fn (Builder $students) => $this->applyStudentScope($students, $announcement))
                    ->orWhereHas('teacher.classSubjects.schoolClass', fn (Builder $classes) => $this->applyClassScope($classes, $announcement))
                    ->orWhereHas('guardian.students', fn (Builder $students) => $this->applyStudentScope($students, $announcement));
            });
        }

        return $query;
    }

    private function applyStudentScope(Builder $students, Announcement $announcement): Builder
    {
        if ($announcement->class_id) {
            $students->where('class_id', $announcement->class_id);
        }

        if ($announcement->program_id) {
            $students->where(function (Builder $programs) use ($announcement) {
                $programs->where('program_id', $announcement->program_id)
                    ->orWhereHas('class', fn (Builder $classes) => $classes->where('program_id', $announcement->program_id));
            });
        }

        return $students;
    }

    private function applyClassScope(Builder $classes, Announcement $announcement): Builder
    {
        if ($announcement->class_id) {
            $classes->whereKey($announcement->class_id);
        }

        if ($announcement->program_id) {
            $classes->where('program_id', $announcement->program_id);
        }

        return $classes;
    }
}
