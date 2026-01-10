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

    public function showDetails(): array
    {
        // Get quantities from the quantities accessor/attribute
        $quantities = $this->quantities ?? [
            'usable' => 0,
            'partially_damaged' => 0,
            'damaged' => 0,
            'lost' => 0,
            'condemnable' => 0
        ];

        // Format subjects
        $subjects = [];
        if (method_exists($this, 'subjects')) {
            foreach ($this->subjects() as $subjectGradeLevel) {
                $subjects[] = [
                    'subject' => $subjectGradeLevel->subject->subject_name ?? 'N/A',
                    'grade' => $subjectGradeLevel->gradeLevel->grade ?? 'N/A'
                ];
            }
        }

        // Format acquisitions
        $acquisitions = [];
        if ($this->relationLoaded('printAcquisitions')) {
            foreach ($this->printAcquisitions as $acquisition) {
                $acquisitions[] = [
                    'source' => $acquisition->source ?? '-',
                    'date_acquired' => $acquisition->date_acquired
                        ? date('M d, Y', strtotime($acquisition->date_acquired))
                        : '-',
                    'cost' => $acquisition->cost ?? null,
                    'iar' => $acquisition->iar ?? '-',
                    'remarks' => $acquisition->remarks ?? '-',
                    'usable' => $acquisition->usable ?? 0,
                    'partially_damaged' => $acquisition->partially_damaged ?? 0,
                    'damaged' => $acquisition->damaged ?? 0,
                    'lost' => $acquisition->lost ?? 0,
                    'condemnable' => $acquisition->condemnable ?? 0,
                    'total_quantity' => ($acquisition->usable ?? 0) +
                                      ($acquisition->partially_damaged ?? 0) +
                                      ($acquisition->damaged ?? 0) +
                                      ($acquisition->lost ?? 0) +
                                      ($acquisition->condemnable ?? 0)
                ];
            }
        }

        return [
            'id' => $this->id,
            'image' => $this->image
                ? asset('assets/images/' . $this->image)
                : asset('assets/images/default.jpg'),
            'title' => $this->printTitle->title ?? 'N/A',
            'author' => $this->printTitle->authors->pluck('author_name')->join(', ') ?: '-',
            'publisher' => $this->publisher ?? '-',
            'type' => $this->type->type_name ?? '-',
            'isbn' => $this->isbn ?? 'N/A',
            'copyright' => $this->copyright ?? '-',
            'pages' => $this->pages ?? '-',
            'subjects' => $subjects,
            'acquisitions' => $acquisitions,
            'quantities' => $quantities,
            // 'edit_url' => route('edit-resource', $this->id)
        ];
    }

}
