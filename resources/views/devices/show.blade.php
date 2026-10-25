<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="w-3 h-3 rounded-full {{ $device->status === 'online' ? 'bg-emerald-500 animate-pulse' : ($device->status === 'maintenance' ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $device->name }}
                </h2>
                <span class="text-xs font-mono bg-gray-100 text-gray-700 px-2.5 py-1 rounded">{{ $device->device_uuid }}</span>
            </div>
            <a href="{{ route('devices.index') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-700">
                &larr; Back to fleet
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Hardware Spec Card & Status Switcher -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 text-xs">
                    <div>
                        <span class="text-gray-400 uppercase font-bold block">Type</span>
                        <span class="font-bold text-gray-900 text-sm">{{ ucfirst($device->type) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 uppercase font-bold block">Location</span>
                        <span class="font-bold text-gray-900 text-sm">{{ $device->location }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 uppercase font-bold block">Battery</span>
                        <span class="font-bold font-mono text-sm {{ $device->battery_percent < 20 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $device->battery_percent }}%</span>
                    </div>
                    <div>
                        <span class="text-gray-400 uppercase font-bold block">Firmware</span>
                        <span class="font-bold font-mono text-gray-900 text-sm">v{{ $device->firmware_version }}</span>
                    </div>
                </div>

                <!-- Status Update Form -->
                <form method="POST" action="{{ route('devices.status', $device) }}" class="flex items-center space-x-2">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="text-xs border-gray-300 rounded-lg">
                        <option value="online" @selected($device->status === 'online')>Online</option>
                        <option value="offline" @selected($device->status === 'offline')>Offline</option>
                        <option value="maintenance" @selected($device->status === 'maintenance')>Maintenance</option>
                    </select>
                    <button type="submit" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-lg text-xs">
                        Update
                    </button>
                </form>
            </div>

            <!-- Two Columns: Simulated Ingest & Threshold Rules -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Ingest Telemetry Reading Form -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-900 text-base mb-1">Simulate Sensor Telemetry Ingestion</h3>
                    <p class="text-xs text-gray-500 mb-4">Post a live data packet to test threshold triggers and metric logging.</p>

                    <form method="POST" action="{{ route('devices.telemetry.store', $device) }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label for="metric" class="block text-xs font-bold uppercase text-gray-600 mb-1">Metric</label>
                                <input type="text" id="metric" name="metric" value="{{ $device->type }}" required class="w-full text-xs border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label for="value" class="block text-xs font-bold uppercase text-gray-600 mb-1">Value</label>
                                <input type="number" step="0.1" id="value" name="value" value="28.5" required class="w-full text-xs font-mono border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label for="unit" class="block text-xs font-bold uppercase text-gray-600 mb-1">Unit</label>
                                <input type="text" id="unit" name="unit" value="°C" required class="w-full text-xs font-mono border-gray-300 rounded-lg">
                            </div>
                        </div>

                        <button type="submit" class="w-full py-2.5 px-4 bg-cyan-600 hover:bg-cyan-700 text-white font-bold rounded-xl text-xs shadow transition">
                            Ingest Data Packet &rarr;
                        </button>
                    </form>
                </div>

                <!-- Add Alert Rule Form -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-900 text-base mb-1">Set Threshold Alert Rule</h3>
                    <p class="text-xs text-gray-500 mb-4">Trigger automatic notifications when telemetric values cross limits.</p>

                    <form method="POST" action="{{ route('devices.rules.store', $device) }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Metric</label>
                                <input type="text" name="metric" value="{{ $device->type }}" required class="w-full text-xs border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Severity</label>
                                <select name="severity" class="w-full text-xs border-gray-300 rounded-lg">
                                    <option value="warning">Warning (Amber)</option>
                                    <option value="critical">Critical (Red)</option>
                                    <option value="info">Informational</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Condition</label>
                                <select name="operator" class="w-full text-xs border-gray-300 rounded-lg font-mono">
                                    <option value=">">Greater than (&gt;)</option>
                                    <option value=">=">Greater or equal (&gt;=)</option>
                                    <option value="<">Less than (&lt;)</option>
                                    <option value="<=">Less or equal (&lt;=)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Threshold</label>
                                <input type="number" step="0.1" name="threshold_value" value="35.0" required class="w-full text-xs font-mono border-gray-300 rounded-lg">
                            </div>
                        </div>

                        <button type="submit" class="w-full py-2.5 px-4 bg-gray-900 hover:bg-black text-white font-bold rounded-xl text-xs transition">
                            Save Alert Rule
                        </button>
                    </form>
                </div>
            </div>

            <!-- Telemetry History Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-base">Telemetry Ingestion Log</h3>
                    <span class="text-xs text-gray-400 font-mono">{{ $readings->total() }} total points</span>
                </div>
                @if($readings->isEmpty())
                    <div class="p-10 text-center text-gray-400 text-xs">
                        No telemetry readings logged yet. Use the simulation box above to push data.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 border-b border-gray-100 text-gray-500 uppercase text-[10px] tracking-wider">
                                <tr>
                                    <th class="p-4">Timestamp</th>
                                    <th class="p-4">Metric</th>
                                    <th class="p-4">Recorded Value</th>
                                    <th class="p-4">Unit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 font-mono">
                                @foreach($readings as $r)
                                    <tr class="hover:bg-gray-50">
                                        <td class="p-4 text-gray-500 font-sans">{{ $r->recorded_at->format('M d, Y H:i:s') }}</td>
                                        <td class="p-4 font-bold text-gray-900 font-sans">{{ $r->metric }}</td>
                                        <td class="p-4 font-bold text-cyan-700 text-sm">{{ $r->value }}</td>
                                        <td class="p-4 text-gray-600">{{ $r->unit }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-100">
                        {{ $readings->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
