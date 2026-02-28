<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Materialized View Refresh Schedule
|--------------------------------------------------------------------------
|
| Region  (mv_bosy_division_status)         → every 6 hours
| Division (mv_bosy_division_schools_status) → every 4 hours
| District (mv_bosy_district_schools_status) → every 2 hours
| Population summary                         → every 2 hours (no CONCURRENTLY)
| School-level data                          → real-time via DB schema (no MV)
|
*/

// ── District tier: every 2 hours ────────────────────────────────────────────
// Also includes population summary since it needs frequent updates
Schedule::command('summaries:refresh --tier=district')
    ->everyTwoHours()
    ->runInBackground()
    ->onOneServer()
    ->withoutOverlapping(110)
    ->appendOutputTo(storage_path('logs/mv-refresh-district.log'));

Schedule::command('summaries:refresh --tier=population')
    ->everyTwoHours()
    ->runInBackground()
    ->onOneServer()
    ->withoutOverlapping(110)
    ->appendOutputTo(storage_path('logs/mv-refresh-population.log'));

// ── Division tier: every 4 hours ────────────────────────────────────────────
Schedule::command('summaries:refresh --tier=division')
    ->everyFourHours()
    ->runInBackground()
    ->onOneServer()
    ->withoutOverlapping(230)
    ->appendOutputTo(storage_path('logs/mv-refresh-division.log'));

// ── Region tier: every 6 hours ──────────────────────────────────────────────
Schedule::command('summaries:refresh --tier=region')
    ->everySixHours()
    ->runInBackground()
    ->onOneServer()
    ->withoutOverlapping(350)
    ->appendOutputTo(storage_path('logs/mv-refresh-region.log'));