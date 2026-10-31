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

    public function food()
    {
        return $this->belongsTo(Food::class);
    }
}
