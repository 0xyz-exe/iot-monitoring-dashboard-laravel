<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = $user->devices()->withCount('activeAlerts')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('device_uuid', 'like', "%{$search}%");
            });
        }

        $devices = $query->paginate(12)->withQueryString();

        return view('devices.index', compact('devices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:temperature,humidity,air_quality,power_meter,vibration',
            'location' => 'required|string|max:255',
            'battery_percent' => 'nullable|integer|min:0|max:100',
            'firmware_version' => 'nullable|string|max:50',
        ]);

        $device = $request->user()->devices()->create([
            'device_uuid' => 'iot-' . Str::lower(Str::random(10)),
            'name' => $validated['name'],
            'type' => $validated['type'],
            'location' => $validated['location'],
            'battery_percent' => $validated['battery_percent'] ?? 100,
            'firmware_version' => $validated['firmware_version'] ?? '1.0.0',
            'status' => 'online',
            'last_ping_at' => now(),
        ]);

        return redirect()->route('devices.show', $device)->with('success', 'IoT Device registered successfully.');
    }

    public function show(Request $request, Device $device): View
    {
        if ($device->user_id !== $request->user()->id) {
            abort(403);
        }

        $device->load(['alertRules', 'activeAlerts']);
        $readings = $device->telemetryReadings()->latest('recorded_at')->paginate(20);

        return view('devices.show', compact('device', 'readings'));
    }

    public function updateStatus(Request $request, Device $device): RedirectResponse
    {
        if ($device->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:online,offline,maintenance',
        ]);

        $device->update($validated);

        return back()->with('success', 'Device status changed to ' . ucfirst($validated['status']));
    }

    public function destroy(Request $request, Device $device): RedirectResponse
    {
        if ($device->user_id !== $request->user()->id) {
            abort(403);
        }

        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Device deleted.');
    }
}
