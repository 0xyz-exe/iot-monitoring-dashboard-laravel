<?php

namespace Database\Seeders;

use App\Models\AlertRule;
use App\Models\Device;
use App\Models\DeviceAlert;
use App\Models\TelemetryReading;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@demo.test'],
            [
                'name' => 'Alexander Wright',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 1. HVAC Sensor A3
        $devHvac = Device::firstOrCreate(
            ['device_uuid' => 'iot-hvac-cikarang-01'],
            [
                'user_id' => $user->id,
                'name' => 'HVAC Thermal Sensor Core-01',
                'type' => 'temperature',
                'location' => 'Data Center Server Room A3, Cikarang',
                'status' => 'online',
                'battery_percent' => 98,
                'firmware_version' => '2.4.1',
                'last_ping_at' => Carbon::now()->subMinutes(2),
            ]
        );

        $ruleHvacWarn = AlertRule::firstOrCreate(
            ['device_id' => $devHvac->id, 'metric' => 'temperature', 'operator' => '>', 'threshold_value' => 25.00],
            ['severity' => 'warning', 'is_active' => true]
        );
        AlertRule::firstOrCreate(
            ['device_id' => $devHvac->id, 'metric' => 'temperature', 'operator' => '>', 'threshold_value' => 28.00],
            ['severity' => 'critical', 'is_active' => true]
        );

        // Telemetry stream for HVAC
        $hvacTemps = [22.8, 23.2, 23.5, 24.1, 24.6, 25.2, 25.8, 26.5, 27.10];
        foreach ($hvacTemps as $idx => $temp) {
            TelemetryReading::firstOrCreate(
                ['device_id' => $devHvac->id, 'recorded_at' => Carbon::now()->subMinutes((count($hvacTemps) - $idx) * 10)],
                ['metric' => 'temperature', 'value' => $temp, 'unit' => '°C']
            );
        }

        DeviceAlert::firstOrCreate(
            ['device_id' => $devHvac->id, 'message' => 'Temperature threshold breached: 27.10°C (Limit: 25.00°C)'],
            [
                'alert_rule_id' => $ruleHvacWarn->id,
                'severity' => 'warning',
                'is_resolved' => false,
                'triggered_at' => Carbon::now()->subMinutes(15),
            ]
        );

        // 2. Cold Storage B3
        $devCryo = Device::firstOrCreate(
            ['device_uuid' => 'iot-cryo-surabaya-02'],
            [
                'user_id' => $user->id,
                'name' => 'Cold Storage B3 Cryo Monitor',
                'type' => 'temperature',
                'location' => 'Pharmaceutical Warehouse Bay 3, Surabaya',
                'status' => 'online',
                'battery_percent' => 89,
                'firmware_version' => '2.2.0',
                'last_ping_at' => Carbon::now()->subMinutes(4),
            ]
        );
        AlertRule::firstOrCreate(
            ['device_id' => $devCryo->id, 'metric' => 'temperature', 'operator' => '>', 'threshold_value' => -18.00],
            ['severity' => 'critical', 'is_active' => true]
        );
        foreach ([-22.5, -22.1, -21.9, -22.0, -21.8] as $idx => $temp) {
            TelemetryReading::firstOrCreate(
                ['device_id' => $devCryo->id, 'recorded_at' => Carbon::now()->subMinutes((5 - $idx) * 12)],
                ['metric' => 'temperature', 'value' => $temp, 'unit' => '°C']
            );
        }

        // 3. Substation Main Power Meter
        $devPower = Device::firstOrCreate(
            ['device_uuid' => 'iot-pwr-grid-sub04'],
            [
                'user_id' => $user->id,
                'name' => 'Substation Main Power Meter',
                'type' => 'power_meter',
                'location' => 'Electrical Distribution Vault 02, Karawang',
                'status' => 'online',
                'battery_percent' => 100,
                'firmware_version' => '3.1.2',
                'last_ping_at' => Carbon::now()->subMinute(),
            ]
        );
        AlertRule::firstOrCreate(
            ['device_id' => $devPower->id, 'metric' => 'power', 'operator' => '>', 'threshold_value' => 45000.00],
            ['severity' => 'critical', 'is_active' => true]
        );
        foreach ([34200.0, 36800.0, 38100.0, 39400.0, 41250.0] as $idx => $val) {
            TelemetryReading::firstOrCreate(
                ['device_id' => $devPower->id, 'recorded_at' => Carbon::now()->subMinutes((5 - $idx) * 15)],
                ['metric' => 'power', 'value' => $val, 'unit' => 'W']
            );
        }

        // 4. Cleanroom Air Quality Multi-Probe
        $devAir = Device::firstOrCreate(
            ['device_uuid' => 'iot-air-cleanroom-09'],
            [
                'user_id' => $user->id,
                'name' => 'Cleanroom Air Quality Multi-Probe',
                'type' => 'air_quality',
                'location' => 'Semiconductor Fab Lab 102, Serpong',
                'status' => 'online',
                'battery_percent' => 94,
                'firmware_version' => '2.4.0',
                'last_ping_at' => Carbon::now()->subMinutes(3),
            ]
        );
        AlertRule::firstOrCreate(
            ['device_id' => $devAir->id, 'metric' => 'co2', 'operator' => '>', 'threshold_value' => 800.00],
            ['severity' => 'warning', 'is_active' => true]
        );
        foreach ([410.0, 425.0, 440.0, 455.0, 468.0] as $idx => $val) {
            TelemetryReading::firstOrCreate(
                ['device_id' => $devAir->id, 'recorded_at' => Carbon::now()->subMinutes((5 - $idx) * 10)],
                ['metric' => 'co2', 'value' => $val, 'unit' => 'ppm']
            );
        }

        // 5. Turbine 04 Vibration Monitor
        $devVib = Device::firstOrCreate(
            ['device_uuid' => 'iot-vib-turb-04'],
            [
                'user_id' => $user->id,
                'name' => 'Turbine 04 Bearing Vibration Monitor',
                'type' => 'vibration',
                'location' => 'Generator Hall Floor B, Tanjung Priok',
                'status' => 'maintenance',
                'battery_percent' => 76,
                'firmware_version' => '1.9.8',
                'last_ping_at' => Carbon::now()->subHours(5),
            ]
        );
        $ruleVib = AlertRule::firstOrCreate(
            ['device_id' => $devVib->id, 'metric' => 'vibration', 'operator' => '>', 'threshold_value' => 4.50],
            ['severity' => 'critical', 'is_active' => true]
        );
        foreach ([2.1, 2.8, 3.5, 4.2, 4.85] as $idx => $val) {
            TelemetryReading::firstOrCreate(
                ['device_id' => $devVib->id, 'recorded_at' => Carbon::now()->subMinutes((5 - $idx) * 20)],
                ['metric' => 'vibration', 'value' => $val, 'unit' => 'g']
            );
        }
        DeviceAlert::firstOrCreate(
            ['device_id' => $devVib->id, 'message' => 'Vibration excursion detected: 4.85g on bearing axis Z (Limit: 4.50g)'],
            [
                'alert_rule_id' => $ruleVib->id,
                'severity' => 'critical',
                'is_resolved' => false,
                'triggered_at' => Carbon::now()->subHours(2),
            ]
        );

        // 6. Greenhouse Humidity Probe
        $devHum = Device::firstOrCreate(
            ['device_uuid' => 'iot-hum-gh-zone2'],
            [
                'user_id' => $user->id,
                'name' => 'Greenhouse Zone 2 Humidity Probe',
                'type' => 'humidity',
                'location' => 'AgriTech Experimental Greenhouse, Lembang',
                'status' => 'offline',
                'battery_percent' => 12,
                'firmware_version' => '2.0.1',
                'last_ping_at' => Carbon::now()->subHours(18),
            ]
        );
        foreach ([68.0, 72.5, 75.0, 78.2] as $idx => $val) {
            TelemetryReading::firstOrCreate(
                ['device_id' => $devHum->id, 'recorded_at' => Carbon::now()->subHours((4 - $idx) * 4)],
                ['metric' => 'humidity', 'value' => $val, 'unit' => '%']
            );
        }
        DeviceAlert::firstOrCreate(
            ['device_id' => $devHum->id, 'message' => 'Telemetry heartbeat timeout: sensor unresponsive > 12h'],
            [
                'severity' => 'warning',
                'is_resolved' => true,
                'triggered_at' => Carbon::now()->subHours(14),
                'resolved_at' => Carbon::now()->subHours(3),
            ]
        );
    }
}
