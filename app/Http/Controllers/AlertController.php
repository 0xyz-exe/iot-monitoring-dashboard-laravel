<?php

namespace App\Http\Controllers;

use App\Models\AlertRule;
use App\Models\Device;
use App\Models\DeviceAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = $user->deviceAlerts()->with('device')->latest('triggered_at');

        if ($request->has('resolved')) {
            $query->where('is_resolved', $request->boolean('resolved'));
        } else {
            $query->where('is_resolved', false);
        }

        $alerts = $query->paginate(15)->withQueryString();

        return view('alerts.index', compact('alerts'));
    }

    public function resolve(Request $request, DeviceAlert $alert): RedirectResponse
    {
        if ($alert->device->user_id !== $request->user()->id) {
            abort(403);
        }

        $alert->update([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Alert resolved.');
    }

    public function storeRule(Request $request, Device $device): RedirectResponse
    {
        if ($device->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'metric' => 'required|string|max:50',
            'operator' => 'required|in:>,<,>=,<=',
            'threshold_value' => 'required|numeric',
            'severity' => 'required|in:info,warning,critical',
        ]);

        $device->alertRules()->create($validated);

        return back()->with('success', 'Alert threshold rule created.');
    }
}
