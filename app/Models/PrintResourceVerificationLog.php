<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintResourceVerificationLog extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    const UPDATED_AT = null;

    protected $fillable = [
        'id',
        'print_resource_id',
        'user_id',
        'user_level',
        'user_role',
        'action_type',
        'comment',
        'previous_metadata',
        'new_metadata',
        'created_at',
    ];

    protected $casts = [
        'id' => 'string',
        'print_resource_id' => 'string',
        'user_id' => 'string',
        'user_level' => 'integer',
        'previous_metadata' => 'array',
        'new_metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function printResource(): BelongsTo
    {
        return $this->belongsTo(PrintResource::class, 'print_resource_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
