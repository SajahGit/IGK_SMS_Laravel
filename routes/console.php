<?php

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
