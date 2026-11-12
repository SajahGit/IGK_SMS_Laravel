<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DailyAbsenteeDigest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $attendanceDate,
        public readonly Collection $absentees,
    ) {}

    public function build(): self
    {
        return $this->subject('Daily student absentee report - '.$this->attendanceDate)
            ->view('emails.daily-absentee-digest');
    }
}
