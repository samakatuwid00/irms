<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrintResource extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'print_title_id', 'print_type_id', 'publisher', 'volume', 'edition',
        'copyright', 'pages', 'isbn', 'remarks', 'subject_grade_level_ids', 'created_at', 'updated_at', 'library_id'
    ];

    // Relationship to PrintTitle
    public function printTitle(): BelongsTo
    {
        return $this->belongsTo(PrintTitle::class, 'print_title_id');
    }

    // Relationship to PrintType
    public function type(): BelongsTo
    {
        return $this->belongsTo(PrintType::class, 'print_type_id');
    }

    // Relationship to acquisitions
    public function printAcquisitions(): HasMany
    {
        return $this->hasMany(PrintAcquisition::class, 'print_id');
    }

    // Quantities summed from acquisitions
    public function getQuantitiesAttribute()
    {
        $acqs = $this->printAcquisitions;
        return [
            'usable' => $acqs->sum('usable'),
            'partially_damaged' => $acqs->sum('partially_damaged'),
            'damaged' => $acqs->sum('damaged'),
            'lost' => $acqs->sum('lost'),
            'condemnable' => $acqs->sum('condemnable'),
        ];
    }

    // Fetch subject-grade levels
    public function subjects()
    {
        if(!$this->subject_grade_level_ids) return collect();
        $ids = explode(',', $this->subject_grade_level_ids);

        return SubjectGradeLevel::with(['subject', 'gradeLevel'])
            ->whereIn('id', $ids)
            ->get();
    }

}
