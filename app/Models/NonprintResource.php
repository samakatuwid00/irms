<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class NonprintResource extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'nonprint_title_id',
        'nonprint_type_id',
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
        'cover',
        'uniqueness_hash',
        'status',
        'station_type',
        'station_id',
        'encoded_by',
        'approver_station',
    ];
    protected $table = 'nonprint_resources';

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
    // $excludePackaged = true → skip acquisitions that belong to a package (package_id IS NOT NULL)
    public function scopedQuantities(?array $libraryIds, bool $excludePackaged = false): array
    {
        if (empty($libraryIds) && $libraryIds !== null) {
            return ['usable' => 0, 'partially_damaged' => 0, 'damaged' => 0, 'lost' => 0, 'condemnable' => 0];
        }

        $query = $this->nonprintAcquisitions();

        if ($libraryIds !== null) {
            $query->whereIn('library_id', $libraryIds);
        }

        if ($excludePackaged) {
            $query->whereNull('package_id');
        }

        $acqs = $query->get();

        return [
            'usable'            => $acqs->sum('usable'),
            'partially_damaged' => $acqs->sum('partially_damaged'),
            'damaged'           => $acqs->sum('damaged'),
            'lost'              => $acqs->sum('lost'),
            'condemnable'       => $acqs->sum('condemnable'),
        ];
    }

    // Fetch subject-grade levels
    public function subjects()
    {
        if (!$this->subject_grade_level_ids) return collect();

        $ids = explode(',', $this->subject_grade_level_ids);

        return SubjectGradeLevel::whereIn('id', $ids)  // Query Builder
            ->with(['subject', 'gradeLevel'])           // eager load BEFORE get()
            ->get();                                     // NOW returns Collection
    }

    /**
     * Thumbnail URL for table row <img> tags.
     * Falls back: thumbnail → full cover → default placeholder.
     */
    public function getThumbUrlAttribute(): string
    {
        if ($this->cover) {
            $thumbPath = preg_replace('#^nonprint_cover/#', 'nonprint-thumbnails/', $this->cover);

            if ($thumbPath !== $this->cover && Storage::disk('public')->exists($thumbPath)) {
                return asset('storage/' . $thumbPath);
            }

            return asset('storage/' . $this->cover);
        }

        return asset('assets/images/def.jpg');
    }

    /**
     * Build the data array used by the view modal.
     *
     * Behaviour:
     *  - $packageId supplied  → show ONLY acquisitions belonging to that package
     *  - $packageId null      → show ONLY non-packaged acquisitions (package_id IS NULL)
     *
     * In both cases the returned acquisition rows include a `package_id` field so
     * the JS layer always has the raw value available if needed.
     *
     * @param  array|null  $libraryIds  Filter to specific library IDs (null = all libraries)
     * @param  string|null $packageId   If set, show acquisitions for this package only
     */
    public function showDetails(?array $libraryIds = null, ?string $packageId = null): array
    {
        $quantities = $this->scopedQuantities($libraryIds);

        $subjects = [];
        if (method_exists($this, 'subjects')) {
            foreach ($this->subjects() as $subjectGradeLevel) {
                $subjects[] = [
                    'subject' => $subjectGradeLevel->subject->subject_name ?? 'N/A',
                    'grade'   => $subjectGradeLevel->gradeLevel->grade ?? 'N/A',
                ];
            }
        }

        // ── Build acquisition query ────────────────────────────────────────
        $acqQuery = $this->nonprintAcquisitions();

        if ($packageId !== null) {
            // Opened from the Package modal → show only this package's acquisitions
            $acqQuery->where('package_id', $packageId);
        } else {
            // Opened as a standalone resource → exclude any packaged acquisitions
            $acqQuery->whereNull('package_id');
        }

        if ($libraryIds !== null) {
            if (empty($libraryIds)) {
                $acqQuery->whereRaw('1 = 0'); // no results
            } else {
                $acqQuery->whereIn('library_id', $libraryIds);
            }
        }

        $acquisitions = [];
        foreach ($acqQuery->get() as $acquisition) {
            $acquisitions[] = [
                'package_id'        => $acquisition->package_id ?? null, // always expose for JS
                'library_name'      => $acquisition->library_name ?? '-',
                'division_name'     => $acquisition->division_name ?? '-',
                'source'            => $acquisition->source ?? '-',
                'date_acquired'     => $acquisition->date_acquired
                                        ? date('M d, Y', strtotime($acquisition->date_acquired))
                                        : '-',
                'cost'              => $acquisition->cost ?? null,
                'iar'               => $acquisition->iar ?? '-',
                'remarks'           => $acquisition->remarks ?? '-',
                'usable'            => $acquisition->usable ?? 0,
                'partially_damaged' => $acquisition->partially_damaged ?? 0,
                'damaged'           => $acquisition->damaged ?? 0,
                'lost'              => $acquisition->lost ?? 0,
                'condemnable'       => $acquisition->condemnable ?? 0,
                'total_quantity'    => ($acquisition->usable ?? 0)
                                     + ($acquisition->partially_damaged ?? 0)
                                     + ($acquisition->damaged ?? 0)
                                     + ($acquisition->lost ?? 0)
                                     + ($acquisition->condemnable ?? 0),
            ];
        }

        return [
            'id'           => $this->id,
            'image'        => $this->cover
                                ? asset('storage/' . $this->cover)
                                : asset('assets/images/default.jpg'),
            'title'        => $this->nonprintTitle->title ?? 'N/A',
            'type'         => $this->type->type_name ?? '-',
            'brand'        => $this->brand ?? '-',
            'code'         => $this->code ?? 'N/A',
            'version'      => $this->version ?? '-',
            'url'          => $this->url ?? '-',
            'size'         => $this->size ?? '-',
            'model'        => $this->model ?? '-',
            'subjects'     => $subjects,
            'acquisitions' => $acquisitions,
            'quantities'   => $quantities,
            'library_name' => $this->library_name ?? 'No Library Assigned',
        ];
    }
}