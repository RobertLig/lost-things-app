<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsCollection;

class Item extends Model
{
    protected $fillable = [
        'title',
        'description',
        'lat',
        'lng',
        'lost_at',
        'user_id',
        'library',
        'location_id'
    ];

    protected $casts = [
        'lost_at' => 'datetime',
        'library' => AsCollection::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
