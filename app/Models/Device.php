<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_uuid',
        'name',
        'type',
        'location',
        'status',
        'battery_percent',
        'firmware_version',
        'last_ping_at',
    ];

    protected function casts(): array
    {
        return [
            'battery_percent' => 'integer',
            'last_ping_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($device) {
            if (empty($device->device_uuid)) {
                $device->device_uuid = 'iot-' . Str::lower(Str::random(12));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function telemetryReadings(): HasMany
    {
        return $this->hasMany(TelemetryReading::class);
    }

    public function latestReading(): HasMany
    {
        return $this->hasMany(TelemetryReading::class)->latest('recorded_at');
    }

    public function alertRules(): HasMany
    {
        return $this->hasMany(AlertRule::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(DeviceAlert::class);
    }

    public function activeAlerts(): HasMany
    {
        return $this->hasMany(DeviceAlert::class)->where('is_resolved', false);
    }
}
