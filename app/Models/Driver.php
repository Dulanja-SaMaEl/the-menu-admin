<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Authenticatable
{
    use HasApiTokens, Notifiable;
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'status',
        'vehicle_type',
        'vehicle_number',
        'total_deliveries',
        'max_orders',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'total_deliveries' => 'integer',
        'max_orders' => 'integer',
        'password' => 'hashed',
    ];

    protected $appends = [
        'is_online',
        'is_active',
        'active_orders_count',
        'is_at_capacity',
        'completed_orders_count',
    ];

    public function getActiveOrdersCountAttribute(): int
    {
        if ($this->relationLoaded('assignedOrders')) {
            return $this->assignedOrders
                ->whereIn('status', ['pending', 'processing', 'assigned'])
                ->count();
        }

        return $this->assignedOrders()
            ->whereIn('status', ['pending', 'processing', 'assigned'])
            ->count();
    }

    /**
     * Dynamically calculate driver status based on active/assigned orders.
     * If driver currently has active orders, status is automatically "on_delivery".
     * If 0 active orders, status is automatically "available".
     */
    public function getStatusAttribute(?string $value): string
    {
        return $this->active_orders_count > 0 ? 'on_delivery' : 'available';
    }

    public function getIsAtCapacityAttribute(): bool
    {
        $limit = $this->max_orders ?? 5;
        return $limit > 0 && $this->active_orders_count >= $limit;
    }

    public function getIsOnlineAttribute(): bool
    {
        return true;
    }

    public function getIsActiveAttribute(): bool
    {
        return true;
    }

    public function scopeAvailable($query)
    {
        return $query->whereDoesntHave('assignedOrders', function ($q) {
            $q->whereIn('status', ['pending', 'processing', 'assigned']);
        });
    }

    public function assignedOrders()
    {
        return $this->hasMany(Order::class);
    }

    public function completedOrders()
    {
        return $this->hasMany(Order::class)->whereIn('status', ['completed', 'delivered']);
    }

    public function getCompletedOrdersCountAttribute(): int
    {
        if (array_key_exists('completed_orders_count', $this->attributes)) {
            return (int) $this->attributes['completed_orders_count'];
        }

        return $this->completedOrders()->count();
    }

    public function getTotalDeliveriesAttribute($value): int
    {
        return $this->completed_orders_count;
    }

    public function assignedEvents()
    {
        return $this->hasMany(Event::class);
    }
}
