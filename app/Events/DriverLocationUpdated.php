<?php

namespace App\Events;

use App\Models\Driver;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DriverLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Driver $driver;
    public array $telemetry;

    /**
     * Create a new event instance.
     */
    public function __construct(Driver $driver, array $telemetry = [])
    {
        $this->driver = $driver;
        $this->telemetry = $telemetry;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('drivers'),
            new Channel('driver.' . $this->driver->id),
        ];
    }

    public static function safeDispatch(Driver $driver, array $telemetry = []): void
    {
        try {
            self::dispatch($driver, $telemetry);
        } catch (\Throwable $e) {
            Log::warning('Broadcasting DriverLocationUpdated skipped: ' . $e->getMessage());
        }
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'driver_id'   => $this->driver->id,
            'driver_name' => $this->driver->name,
            'status'      => $this->driver->status,
            'latitude'    => $this->driver->latitude,
            'longitude'   => $this->driver->longitude,
            'heading'     => $this->driver->heading,
            'speed'       => $this->driver->speed,
            'last_ping_at'=> $this->driver->last_ping_at?->toIso8601String() ?? now()->toIso8601String(),
            'telemetry'   => $this->telemetry,
        ];
    }
}
