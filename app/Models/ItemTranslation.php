<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemTranslation extends Model
{
    protected $fillable = [
        'locale',
        'title',
        'description',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
