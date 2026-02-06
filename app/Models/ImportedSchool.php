<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ImportedSchool extends Model
{
    use HasFactory;

    // Table name (optional if it follows Laravel naming convention)
    protected $table = 'imported_schools';

    // Primary key type is UUID
    protected $primaryKey = 'id';
    public $incrementing = false; // Because UUIDs are not auto-increment
    protected $keyType = 'string';

    // Mass assignable fields
    protected $fillable = [
        'id',
        'title',
        'type',
        'author',
        'publisher',
        'volume',
        'copyright_year',
        'pages',
        'source',
        'status',
        'remarks',
        'short_name',
        'quantity',
        'subject_grade_level',
    ];

    // Automatically cast fields
    protected $casts = [
        'id' => 'string',
        'copyright_year' => 'integer',
        'pages' => 'integer',
        'quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Boot function to generate UUID automatically
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
