<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolLibrary extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'school_id', 'librarian', 'library_name', 'estimated_resource', 'estimated_resource_np'];

    protected $casts = [
        'id' => 'string',
        'estimated_resource' => 'integer',
        'estimated_resource_np' => 'integer'
    ];

    public $timestamps = false;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}
