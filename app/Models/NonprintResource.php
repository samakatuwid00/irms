<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NonprintResource extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'nonprint_title_id',
        'nonprint_type_id',
        'library_id',
        'brand',
        'code',
        'version',
        'url',
        'size',
        'model',
        'description',
        'subject_grade_level_ids',
        'created_at',
        'updated_at',
        'cover'
    ];
    protected $table = 'nonprint_resources';

    protected $appends = ['library_name'];

    public function nonprintTitle(): BelongsTo
    {
        return $this->belongsTo(NonprintTitle::class, 'nonprint_title_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(NonPrintType::class, 'nonprint_type_id');
    }

    public function nonprintAcquisitions(): HasMany
    {
        return $this->hasMany(NonprintAcquisition::class, 'nonprint_id');
    }

    // Quantities summed from acquisitions
    public function getQuantitiesAttribute()
    {
        $acqs = $this->nonprintAcquisitions;
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

    public function getLibraryNameAttribute(): string
    {
        if (!$this->library_id) {
            return 'No Library Assigned';
        }

        // Check school library
        $schoolLibrary = SchoolLibrary::find($this->library_id);
        if ($schoolLibrary) {
            return $schoolLibrary->library_name;
        }

        // Check division library
        $divisionLibrary = DivisionLibrary::find($this->library_id);
        if ($divisionLibrary) {
            return $divisionLibrary->library_name;
        }

        // Check region library
        $regionLibrary = RegionLibrary::find($this->library_id);
        if ($regionLibrary) {
            return $regionLibrary->library_name;
        }

        return 'Unknown Library';
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
        if ($this->relationLoaded('nonprintAcquisitions')) {
            foreach ($this->nonprintAcquisitions as $acquisition) {
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
            'image' => $this->cover
                ? asset('storage/' . $this->cover)
                : asset('assets/images/default.jpg'),
            'title' => $this->nonprintTitle->title ?? 'N/A',
            'type' => $this->type->type_name ?? '-',
            'brand' => $this->brand ?? '-',
            'code' => $this->code ?? 'N/A',
            'version' => $this->version ?? '-',
            'url' => $this->url ?? '-',
            'size' => $this->size ?? '-',
            'model' => $this->model ?? '-',
            'subjects' => $subjects,
            'acquisitions' => $acquisitions,
            'quantities' => $quantities,
            'library_name' => $this->library_name,
            // 'edit_url' => route('update-nonprint-resource', $this->id)
        ];
    }
}
