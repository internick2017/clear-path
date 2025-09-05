<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily reminder processing
Schedule::command('reminders:process')
    ->dailyAt('09:00')
    ->description('Process daily reminders and send notifications');

Schedule::command('reminders:process')
    ->dailyAt('14:00')
    ->description('Process afternoon reminders');

Schedule::command('reminders:process')
    ->dailyAt('19:00')
    ->description('Process evening reminders');