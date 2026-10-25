<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceAlert;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $stats = [
            'total_devices' => $user->devices()->count(),
            'online_devices' => $user->devices()->where('status', 'online')->count(),
            'offline_devices' => $user->devices()->where('status', 'offline')->count(),
            'active_alerts' => $user->deviceAlerts()->where('is_resolved', false)->count(),
        ];

        $devices = $user->devices()
            ->with(['activeAlerts', 'telemetryReadings' => fn ($q) => $q->latest('recorded_at')->take(1)])
            ->latest()
            ->take(6)
            ->get();

        $recentAlerts = $user->deviceAlerts()
            ->with('device')
            ->where('is_resolved', false)
            ->latest('triggered_at')
            ->take(5)
            ->get();

        return view('dashboard', compact('stats', 'devices', 'recentAlerts'));
    }
}
