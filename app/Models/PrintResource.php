<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class PrintResource extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'id' => 'string',
        'library_id' => 'string',
    ];

    protected $fillable = [
        'id',
        'print_title_id',
        'print_type_id',
        'publisher',
        'volume',
        'edition',
        'copyright',
        'pages',
        'isbn',
        'remarks',
        'subject_grade_level_ids',
        'created_at',
        'updated_at',
        'cover',
        'uniqueness_hash',
        'status',
        'station_type',
        'station_id',
        'encoded_by',
        'approver_station',
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
            'usable'            => $acqs->sum('usable'),
            'partially_damaged' => $acqs->sum('partially_damaged'),
            'damaged'           => $acqs->sum('damaged'),
            'lost'              => $acqs->sum('lost'),
            'condemnable'       => $acqs->sum('condemnable'),
        ];
    }

    // Quantities scoped to specific library IDs.
    // null  → sum all acquisitions (unfiltered)
    // []    → empty result (no matching library)
    // [ids] → sum only those libraries
    public function scopedQuantities(?array $libraryIds): array
    {
        if ($libraryIds === null) {
            $acqs = $this->printAcquisitions;
        } elseif (empty($libraryIds)) {
            return ['usable' => 0, 'partially_damaged' => 0, 'damaged' => 0, 'lost' => 0, 'condemnable' => 0];
        } else {
            $acqs = $this->printAcquisitions->whereIn('library_id', $libraryIds);
        }

        return [
            'usable'            => $acqs->sum('usable'),
            'partially_damaged' => $acqs->sum('partially_damaged'),
            'damaged'           => $acqs->sum('damaged'),
            'lost'              => $acqs->sum('lost'),
            'condemnable'       => $acqs->sum('condemnable'),
        ];
    }

    /**
     * Thumbnail URL for table row <img> tags.
     * Falls back: thumbnail → full cover → default placeholder.
     */
    public function getThumbUrlAttribute(): string
    {
        if ($this->cover) {
            $thumbPath = preg_replace('#^print_cover/#', 'print-thumbnails/', $this->cover);

            if ($thumbPath !== $this->cover && Storage::disk('public')->exists($thumbPath)) {
                return asset('storage/'.$thumbPath);
            }

            return asset('storage/'.$this->cover);
        }

        return asset('assets/images/def.jpg');
    }

    // Fetch subject-grade levels
    public function subjects()
    {
        if (! $this->subject_grade_level_ids) {
            return collect();
        }
        $ids = explode(',', $this->subject_grade_level_ids);

        return SubjectGradeLevel::with(['subject', 'gradeLevel'])
            ->whereIn('id', $ids)
            ->get();
    }

    public function showDetails(?array $libraryIds = null): array
    {
        // Scope quantities to the relevant library IDs so the modal summary
        // reflects the same filter as the table row that opened it.
        $quantities = $this->scopedQuantities($libraryIds);

        // Format subjects
        $subjects = [];
        if (method_exists($this, 'subjects')) {
            foreach ($this->subjects() as $subjectGradeLevel) {
                $subjects[] = [
                    'subject' => $subjectGradeLevel->subject->subject_name ?? 'N/A',
                    'grade' => $subjectGradeLevel->gradeLevel->grade ?? 'N/A',
                ];
            }
        }

        // Format acquisitions — optionally scoped to specific library IDs
        $acquisitions = [];
        if ($this->relationLoaded('printAcquisitions')) {
            $rows = $this->printAcquisitions;

            if ($libraryIds !== null) {
                $rows = $rows->whereIn('library_id', $libraryIds);
            }

            foreach ($rows as $acquisition) {
                $acquisitions[] = [
                    'division_name'  => $acquisition->division_name,
                    'library_name' => $acquisition->library_name ?? '-',
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
                                      ($acquisition->condemnable ?? 0),
                ];
            }
        }

        return [
            'id' => $this->id,
            'image' => $this->cover
                ? asset('storage/'.$this->cover)
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
            'library_name' => $this->library_name ?? 'No Library Assigned',
            'edit_url' => route('update-print-resource', $this->id),
        ];
    }
    public function encodedBy()
    {
        return $this->belongsTo(User::class, 'encoded_by', 'id');
    }
}