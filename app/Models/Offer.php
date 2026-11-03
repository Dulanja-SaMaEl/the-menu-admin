<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'badge_text',
        'discount_percentage',
        'food_id',
        'banner_image_url',
        'is_active',
    ];

    protected $casts = [
        'discount_percentage' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('api_active_offers');
        });
        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('api_active_offers');
        });
    }

    public function getBannerImageUrlAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $url = $value;
        } else {
            $url = asset(ltrim($value, '/'));
        }

        if (app()->environment('production') || str_contains($url, 'onrender.com') || (request() && (request()->isSecure() || request()->header('X-Forwarded-Proto') === 'https'))) {
            $url = preg_replace('/^http:/i', 'https:', $url);
        }

        return $url;
    }

    public function food()
    {
        return $this->belongsTo(Food::class);
    }
}
