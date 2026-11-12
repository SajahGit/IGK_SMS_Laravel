<?php

namespace App\Console\Commands;

use App\Mail\DailyAbsenteeDigest;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EmailDailyAbsentees extends Command
{
    protected $signature = 'attendance:email-daily-absentees {--date= : Attendance date in YYYY-MM-DD format}';

    protected $description = 'Email the daily student absentee list to active Registrars';

    public function handle(): int
    {
        $date = $this->option('date') ?: today()->toDateString();
        validator(['date' => $date], ['date' => ['required', 'date_format:Y-m-d']])->validate();

        $recipients = User::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('role', 'registrar')
                    ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'Registrar'));
            })
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($recipients === []) {
            $this->error('No active Registrar email recipients are configured.');

            return self::FAILURE;
        }

        $absentees = Attendance::query()
            ->with(['student.user', 'schoolClass', 'session.classSubject.subject'])
            ->whereDate('date', $date)
            ->where('status', 'absent')
            ->orderBy('class_id')
            ->orderBy('student_id')
            ->get()
            ->map(fn (Attendance $attendance) => [
                'admission_no' => $attendance->student->admission_no,
                'student_name' => $attendance->student->user->name,
                'class_name' => $attendance->schoolClass->full_name,
                'subject_name' => $attendance->session?->classSubject?->subject?->name
                    ?? $attendance->session?->session_label
                    ?? 'Daily attendance',
                'remarks' => $attendance->remarks,
            ]);

        Mail::to($recipients)->send(new DailyAbsenteeDigest($date, $absentees));

        $this->info('Sent the '.$date.' absentee digest to '.count($recipients).' active Registrar(s).');

        return self::SUCCESS;
    }
}
