<?php

use App\Jobs\GenerateMonthlyPayroll;
use App\Jobs\NotifyLowStockItems;
use App\Jobs\NotifyOverdueLibraryLoans;
use App\Jobs\SendOverdueInvoiceReminders;
use App\Services\AnnouncementDeliveryService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('announcements:deliver', function () {
    $count = app(AnnouncementDeliveryService::class)->deliverDue();
    $this->info("Delivered notifications for {$count} announcement(s).");
})->purpose('Deliver notifications for newly published announcements');

Schedule::command('announcements:deliver')->everyMinute()->withoutOverlapping();
Schedule::command('attendance:email-daily-absentees')->dailyAt('17:00')->withoutOverlapping();

// Automatic background jobs (need the queue worker + `schedule:run` cron from deploy/)
Schedule::job(new SendOverdueInvoiceReminders)->dailyAt('08:00');
Schedule::job(new NotifyOverdueLibraryLoans)->dailyAt('08:15');
Schedule::job(new NotifyLowStockItems)->dailyAt('08:30');
Schedule::job(new GenerateMonthlyPayroll)->monthlyOn(1, '06:00');
