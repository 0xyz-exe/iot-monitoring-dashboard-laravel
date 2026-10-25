<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceAlert;
use App\Models\TelemetryReading;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TelemetryController extends Controller
{
    public function store(Request $request, Device $device): RedirectResponse
    {
        if ($device->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'metric' => 'required|string|max:50',
            'value' => 'required|numeric',
            'unit' => 'required|string|max:20',
        ]);

        $val = (float) $validated['value'];

        DB::transaction(function () use ($device, $validated, $val) {
            TelemetryReading::create([
                'device_id' => $device->id,
                'metric' => $validated['metric'],
                'value' => $val,
                'unit' => $validated['unit'],
                'recorded_at' => now(),
            ]);

            $device->update([
                'status' => 'online',
                'last_ping_at' => now(),
            ]);

            // Evaluate Alert Rules
            $rules = $device->alertRules()->where('is_active', true)->where('metric', $validated['metric'])->get();

            foreach ($rules as $rule) {
                if ($rule->evaluate($val)) {
                    DeviceAlert::create([
                        'device_id' => $device->id,
                        'alert_rule_id' => $rule->id,
                        'message' => "Threshold breached for {$rule->metric}: {$val} {$validated['unit']} {$rule->operator} {$rule->threshold_value}",
                        'severity' => $rule->severity,
                        'is_resolved' => false,
                        'triggered_at' => now(),
                    ]);
                }
            }
        });

        return back()->with('success', 'Telemetry reading ingested successfully.');
    }
}
