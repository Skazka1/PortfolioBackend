<?php

namespace App\Models;

use Database\Factories\CampusEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampusEvent extends Model
{
    /** @use HasFactory<CampusEventFactory> */
    use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'title', 'description', 'date_time', 'location', 'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_time' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
