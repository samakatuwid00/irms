<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DivisionLibrary extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'division_id', 'librarian', 'library_name', 'net_expected_count'];
    protected $casts = [
        'id' => 'string',
        'net_expected_count' => 'integer'
    ];
    public $timestamps = false;

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function librarianUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'librarian');
    }
}
