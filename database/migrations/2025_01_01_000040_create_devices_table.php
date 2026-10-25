<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_uuid')->unique();
            $table->string('name');
            $table->string('type'); // temperature, humidity, air_quality, power_meter, vibration
            $table->string('location');
            $table->string('status')->default('online'); // online, offline, maintenance
            $table->integer('battery_percent')->default(100);
            $table->string('firmware_version')->default('1.0.4');
            $table->dateTime('last_ping_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
