<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['lat', 'lng'];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
