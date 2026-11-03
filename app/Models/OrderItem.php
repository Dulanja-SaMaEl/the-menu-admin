<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'food_id',
        'quantity',
        'price',
        'meal_type',
        'scheduled_date',
        'is_paused',
        'swapped_from_food_id',
    ];

    protected $casts = [
        'price' => 'float',
        'scheduled_date' => 'date:Y-m-d',
        'is_paused' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($item) {
            if (empty($item->meal_type) && $item->food_id) {
                $food = $item->food ?: Food::find($item->food_id);
                if ($food && !empty($food->type)) {
                    $item->meal_type = \App\Http\Controllers\OrderController::normalizeMealType(null, $food->type);
                }
            }
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function food()
    {
        return $this->belongsTo(Food::class);
    }

    public function swappedFromFood()
    {
        return $this->belongsTo(Food::class, 'swapped_from_food_id');
    }
}
