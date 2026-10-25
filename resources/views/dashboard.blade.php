<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('IoT Fleet Overview') }}
            </h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('devices.index') }}" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-sm font-semibold shadow transition">
                    + Register Device
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <span class="text-xs font-semibold text-gray-500 uppercase">Total Sensors</span>
                    <div class="text-3xl font-extrabold text-gray-900 mt-2 font-mono">{{ $stats['total_devices'] }}</div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <span class="text-xs font-semibold text-gray-500 uppercase">Online Devices</span>
                    <div class="text-3xl font-extrabold text-emerald-600 mt-2 font-mono">{{ $stats['online_devices'] }}</div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <span class="text-xs font-semibold text-gray-500 uppercase">Offline / Down</span>
                    <div class="text-3xl font-extrabold text-rose-600 mt-2 font-mono">{{ $stats['offline_devices'] }}</div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <span class="text-xs font-semibold text-gray-500 uppercase">Active Alerts</span>
                    <div class="text-3xl font-extrabold text-amber-600 mt-2 font-mono">{{ $stats['active_alerts'] }}</div>
                </div>
            </div>

            <!-- Active Alerts Banner if Any -->
            @if($recentAlerts->isNotEmpty())
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 shadow-sm space-y-3">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full bg-amber-500 animate-pulse"></span>
                            <h3 class="text-sm font-bold text-amber-900 uppercase tracking-wider">Unresolved Threshold Alerts</h3>
                        </div>
                        <a href="{{ route('alerts.index') }}" class="text-xs font-semibold text-amber-700 hover:underline">View All Alerts &rarr;</a>
                    </div>
                    <div class="divide-y divide-amber-200/60 text-xs">
                        @foreach($recentAlerts as $a)
                            <div class="py-2.5 flex justify-between items-center">
                                <div>
                                    <span class="font-bold text-gray-900">{{ $a->device->name }}</span>:
                                    <span class="text-amber-800">{{ $a->message }}</span>
                                    <span class="text-gray-400 text-[11px] ml-2 font-mono">{{ $a->triggered_at->diffForHumans() }}</span>
                                </div>
                                <form method="POST" action="{{ route('alerts.resolve', $a) }}">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 bg-white hover:bg-amber-100 text-amber-800 rounded font-bold text-[11px] border border-amber-300">
                                        Resolve
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Devices Fleet Stream -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-base">Monitored Sensor Fleet</h3>
                    <a href="{{ route('devices.index') }}" class="text-xs font-semibold text-cyan-600 hover:text-cyan-800">All Devices &rarr;</a>
                </div>
                @if($devices->isEmpty())
                    <div class="p-10 text-center text-gray-400 text-xs">
                        No IoT devices registered yet. Click "+ Register Device" to connect your hardware.
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach($devices as $d)
                            <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-gray-50 transition">
                                <div class="space-y-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="w-2.5 h-2.5 rounded-full {{ $d->status === 'online' ? 'bg-emerald-500' : ($d->status === 'maintenance' ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                                        <a href="{{ route('devices.show', $d) }}" class="font-bold text-gray-900 text-sm hover:text-cyan-600">
                                            {{ $d->name }}
                                        </a>
                                        <span class="font-mono text-[11px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $d->device_uuid }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500 flex items-center space-x-3">
                                        <span>Type: <strong>{{ ucfirst($d->type) }}</strong></span>
                                        <span>&bull;</span>
                                        <span>Location: {{ $d->location }}</span>
                                        <span>&bull;</span>
                                        <span>Battery: <strong class="{{ $d->battery_percent < 20 ? 'text-rose-600' : 'text-gray-700' }}">{{ $d->battery_percent }}%</strong></span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="text-right text-xs">
                                        @if($d->telemetryReadings->isNotEmpty())
                                            @php $reading = $d->telemetryReadings->first(); @endphp
                                            <span class="font-mono font-bold text-gray-900">{{ $reading->value }} {{ $reading->unit }}</span>
                                            <span class="text-[10px] text-gray-400 block">{{ $reading->recorded_at->diffForHumans() }}</span>
                                        @else
                                            <span class="text-gray-400 text-[11px]">No data</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('devices.show', $d) }}" class="px-3 py-1.5 bg-gray-100 hover:bg-cyan-50 hover:text-cyan-700 text-gray-700 rounded-lg text-xs font-semibold transition">
                                        Telemetry &rarr;
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
