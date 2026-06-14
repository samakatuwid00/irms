<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * BosySetting
 *
 * Single-row config table.  Only one record should ever exist (id = 1).
 * Regional accounts update it via DashboardController@updateBosySettings.
 * All users read it via DashboardController@getBosySettings.
 *
 * @property int         $id
 * @property int         $calendar_year
 * @property string      $period_start   Y-m-d
 * @property string      $period_end     Y-m-d
 * @property string|null $period_label   Human-readable, auto-generated when null
 * @property string|null $updated_by_user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BosySetting extends Model
{
    protected $table = 'bosy_settings';

    protected $fillable = [
        'calendar_year',
        'period_start',
        'period_end',
        'period_label',
        'updated_by_user_id',
    ];

    protected $casts = [
        'calendar_year' => 'integer',
        'period_start'  => 'date',
        'period_end'    => 'date',
    ];

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Always returns the single settings row, creating a safe default if absent.
     */
    public static function current(): self
    {
        return self::firstOrCreate(
            ['id' => 1],
            [
                'calendar_year' => (int) now()->year,
                'period_start'  => now()->year . '-06-05',
                'period_end'    => now()->year . '-12-25',
                'period_label'  => null,
            ]
        );
    }

    /**
     * Returns the human-readable period label.
     * Falls back to auto-formatting period_start – period_end if period_label is null.
     */
    public function getPeriodDisplayAttribute(): string
    {
        if ($this->period_label) {
            return $this->period_label;
        }

        $start = $this->period_start ? $this->period_start->format('d M') : '—';
        $end   = $this->period_end   ? $this->period_end->format('d M')   : '—';

        return "{$start} – {$end}";
    }
}