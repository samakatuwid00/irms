<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'package';
    protected $fillable = ['id', 'name', 'description', 'type'];
    protected $casts = [
        'id' => 'string',
    ];
    public $timestamps = false;

    /**
     * Get all acquisitions belonging to this package
     */
    public function acquisitions()
    {
        return $this->hasMany(NonprintAcquisition::class, 'package_id', 'id');
    }

    /**
     * Prepare data for Package View Modal
     * @param array|null $libraryIds  — filter to specific library IDs (null = all)
     */
public function toModalData(?array $libraryIds = null)
{
    $query = $this->acquisitions()
        ->with([
            'nonprintResource.nonprintTitle',
            'nonprintResource.type',
        ]);

    if (!empty($libraryIds)) {
        $query->whereIn('library_id', $libraryIds);
    }

    $acquisitions = $query->get();

    $grouped = $acquisitions->groupBy(function ($acq) {
        return $acq->nonprintResource->id ?? null;
    })->filter(fn($group, $key) => $key !== null);

    return [
        'id'          => $this->id,
        'name'        => $this->name,
        'description' => $this->description ?? '',
        'total_items' => $grouped->count(),
        'created_at'  => '—',
        'status'      => 'Active',
        'edit_url'    => route('search-nonprint-resource.add-package-form', $this->id),
        'resources'   => $grouped->map(function ($group) use ($libraryIds) {
            $acq      = $group->first();
            $resource = $acq->nonprintResource;

            $usable            = $group->sum('usable');
            $partially_damaged = $group->sum('partially_damaged');
            $damaged           = $group->sum('damaged');
            $lost              = $group->sum('lost');
            $condemnable       = $group->sum('condemnable');
            $total             = $usable + $partially_damaged + $damaged + $lost + $condemnable;

            $subjects = $resource->subjects()
                ->map(function ($s) {
                    $name  = $s->subject->abbrv ?? $s->subject->subject_name ?? '';
                    $grade = $s->gradeLevel->grade ?? '';
                    return trim("$name - $grade", ' -');
                })
                ->filter()
                ->values()
                ->toArray();

            return [
                'id'        => $resource->id ?? null,
                'title'     => $resource->nonprintTitle->title ?? 'Untitled',
                'code'      => $resource->code ?? '',
                'brand'     => $resource->brand ?? '',
                'type'      => $resource->type->type_name ?? '',
                'version'   => $resource->version ?? '',
                'url'       => $resource->url ?? '',
                'size'      => $resource->size ?? '',
                'model'     => $resource->model ?? '',
                'subjects'  => $subjects,
                'thumb_url' => $resource->thumb_url ?? asset('assets/images/def.jpg'),
                'quantity'  => [
                    'usable'            => $usable,
                    'partially_damaged' => $partially_damaged,
                    'damaged'           => $damaged,
                    'lost'              => $lost,
                    'condemnable'       => $condemnable,
                    'total'             => $total,
                ],
                'details' => $resource->showDetails($libraryIds, $this->id), // ← add this
            ];
        })->values(),
    ];
}
}