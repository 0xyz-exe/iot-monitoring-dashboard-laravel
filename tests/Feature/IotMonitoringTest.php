<?php

namespace Tests\Feature;

use App\Models\AlertRule;
use App\Models\Device;
use App\Models\DeviceAlert;
use App\Models\TelemetryReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IotMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_dashboard_with_fleet_stats(): void
    {
        $user = User::factory()->create();
        Device::create([
            'user_id' => $user->id,
            'name' => 'HVAC Sensor A',
            'type' => 'temperature',
            'location' => 'Data Center 1',
            'status' => 'online',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('IoT Fleet Overview');
        $response->assertSee('HVAC Sensor A');
    }

    public function test_user_can_register_new_iot_device(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/devices', [
            'name' => 'Cold Storage Temp Sensor',
            'type' => 'temperature',
            'location' => 'Warehouse Bay 3',
            'battery_percent' => 95,
            'firmware_version' => '2.1.0',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('devices', [
            'user_id' => $user->id,
            'name' => 'Cold Storage Temp Sensor',
            'location' => 'Warehouse Bay 3',
            'status' => 'online',
        ]);
    }

    public function test_user_can_update_device_operational_status(): void
    {
        $user = User::factory()->create();
        $device = Device::create([
            'user_id' => $user->id,
            'name' => 'Pressure Monitor',
            'type' => 'vibration',
            'location' => 'Turbine 2',
            'status' => 'online',
        ]);

        $response = $this->actingAs($user)->patch("/devices/{$device->id}/status", [
            'status' => 'maintenance',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('maintenance', $device->fresh()->status);
    }

    public function test_user_can_delete_own_device(): void
    {
        $user = User::factory()->create();
        $device = Device::create([
            'user_id' => $user->id,
            'name' => 'Obsolete Beacon',
            'type' => 'humidity',
            'location' => 'Hallway 4',
        ]);

        $response = $this->actingAs($user)->delete("/devices/{$device->id}");
        $response->assertRedirect('/devices');
        $this->assertDatabaseMissing('devices', ['id' => $device->id]);
    }

    public function test_user_cannot_view_or_modify_other_users_device(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $deviceA = Device::create([
            'user_id' => $userA->id,
            'name' => 'Secure Facility Sensor',
            'type' => 'air_quality',
            'location' => 'Vault',
        ]);

        $response = $this->actingAs($userB)->get("/devices/{$deviceA->id}");
        $response->assertStatus(403);

        $responseDelete = $this->actingAs($userB)->delete("/devices/{$deviceA->id}");
        $responseDelete->assertStatus(403);
    }

    public function test_user_can_ingest_telemetry_reading_without_alerts(): void
    {
        $user = User::factory()->create();
        $device = Device::create([
            'user_id' => $user->id,
            'name' => 'Server Thermostat',
            'type' => 'temperature',
            'location' => 'Server Room A',
        ]);

        // Rule for temp > 40
        AlertRule::create([
            'device_id' => $device->id,
            'metric' => 'temperature',
            'operator' => '>',
            'threshold_value' => 40.0,
            'severity' => 'critical',
            'is_active' => true,
        ]);

        // Ingest normal temp 24.5
        $response = $this->actingAs($user)->post("/devices/{$device->id}/telemetry", [
            'metric' => 'temperature',
            'value' => 24.5,
            'unit' => '°C',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('telemetry_readings', [
            'device_id' => $device->id,
            'metric' => 'temperature',
            'value' => 24.5,
        ]);
        $this->assertDatabaseCount('device_alerts', 0);
    }

    public function test_telemetry_reading_triggers_alert_when_threshold_breached(): void
    {
        $user = User::factory()->create();
        $device = Device::create([
            'user_id' => $user->id,
            'name' => 'Boiler Sensor',
            'type' => 'temperature',
            'location' => 'Basement',
        ]);

        AlertRule::create([
            'device_id' => $device->id,
            'metric' => 'temperature',
            'operator' => '>',
            'threshold_value' => 75.0,
            'severity' => 'critical',
            'is_active' => true,
        ]);

        // Ingest overheating temp 88.0
        $response = $this->actingAs($user)->post("/devices/{$device->id}/telemetry", [
            'metric' => 'temperature',
            'value' => 88.0,
            'unit' => '°C',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseCount('device_alerts', 1);
        $this->assertDatabaseHas('device_alerts', [
            'device_id' => $device->id,
            'severity' => 'critical',
            'is_resolved' => false,
        ]);
    }

    public function test_user_can_create_custom_alert_rule(): void
    {
        $user = User::factory()->create();
        $device = Device::create([
            'user_id' => $user->id,
            'name' => 'Office Air Quality',
            'type' => 'air_quality',
            'location' => 'Floor 3',
        ]);

        $response = $this->actingAs($user)->post("/devices/{$device->id}/rules", [
            'metric' => 'co2',
            'operator' => '>=',
            'threshold_value' => 1200,
            'severity' => 'warning',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('alert_rules', [
            'device_id' => $device->id,
            'metric' => 'co2',
            'operator' => '>=',
            'threshold_value' => 1200,
            'severity' => 'warning',
        ]);
    }

    public function test_user_can_resolve_incident_alert(): void
    {
        $user = User::factory()->create();
        $device = Device::create([
            'user_id' => $user->id,
            'name' => 'Substation Meter',
            'type' => 'power_meter',
            'location' => 'Grid 1',
        ]);

        $alert = DeviceAlert::create([
            'device_id' => $device->id,
            'message' => 'Power surge detected: 4500W',
            'severity' => 'critical',
            'is_resolved' => false,
            'triggered_at' => now(),
        ]);

        $response = $this->actingAs($user)->post("/alerts/{$alert->id}/resolve");
        $response->assertSessionHas('success');
        $this->assertTrue($alert->fresh()->is_resolved);
        $this->assertNotNull($alert->fresh()->resolved_at);
    }
}
