<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemTranslation extends Model
{
    use HasFactory;

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
