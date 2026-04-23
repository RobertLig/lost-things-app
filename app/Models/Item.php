<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'lost_at',
        'user_id',
        'library',
        'location_id'
    ];

    protected $casts = [
        'lost_at' => 'datetime',
        'library' => AsCollection::class,
    ];

    protected function library(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => collect(json_decode($value ?? '[]', true)),
            set: fn ($value) => $value ?? [],
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function translations()
    {
        return $this->hasMany(ItemTranslation::class);
    }

    //UI tool
    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        return $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->firstWhere('locale', config('app.fallback_locale'))
            ?? $this->translations->first();
    }

    //database tool (filtering)
    public function translationRelation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        return $this->hasOne(ItemTranslation::class)
            ->where('locale', $locale);
    }

    public function getTitleAttribute()
    {
        return $this->translation()?->title;
    }

    public function getDescriptionAttribute()
    {
        return $this->translation()?->description;
    }
}
