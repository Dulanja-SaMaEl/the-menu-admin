<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Food extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $table = 'food';

    protected $fillable = [
        'name', 'description', 'price', 'image_url', 'type',
        'available_days', 'prep_time', 'calories', 'difficulty',
        'servings', 'rating',
    ];

    protected $casts = [
        'available_days' => 'array',
        'price' => 'float',
        'rating' => 'float',
        'calories' => 'integer',
    ];

    protected static function booted()
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::flush();
        });
        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::flush();
        });
    }

    public function getImageUrlAttribute($value): string
    {
        if (empty($value)) {
            return 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80';
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $url = $value;
        } else {
            $url = asset(ltrim($value, '/'));
        }

        // Force HTTPS in production, on Render, or when requested over secure proxy
        if (app()->environment('production') || str_contains($url, 'onrender.com') || (request() && (request()->isSecure() || request()->header('X-Forwarded-Proto') === 'https'))) {
            $url = preg_replace('/^http:/i', 'https:', $url);
        }

        return $url;
    }

    public function ingredients()
    {
        return $this->hasMany(Ingredient::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function recalculateRating(): float
    {
        $avg = (float) $this->ratings()->avg('rating');
        $this->rating = round($avg ?: 0.0, 1);
        $this->save();
        return $this->rating;
    }
}
