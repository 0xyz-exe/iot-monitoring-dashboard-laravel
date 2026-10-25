<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Connected Devices') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Register Device Form -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 text-base mb-4">Register New IoT Device</h3>
                    <form method="POST" action="{{ route('devices.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="name" class="block text-xs font-bold uppercase text-gray-600 mb-1">Device Name <span class="text-rose-500">*</span></label>
                            <input type="text" id="name" name="name" required placeholder="e.g. Server Room HVAC Sensor" class="w-full text-xs border-gray-300 rounded-lg focus:border-cyan-500 focus:ring-cyan-500">
                        </div>

                        <div>
                            <label for="type" class="block text-xs font-bold uppercase text-gray-600 mb-1">Sensor Type <span class="text-rose-500">*</span></label>
                            <select id="type" name="type" class="w-full text-xs border-gray-300 rounded-lg focus:border-cyan-500 focus:ring-cyan-500">
                                <option value="temperature">Temperature Sensor (°C)</option>
                                <option value="humidity">Humidity Sensor (%)</option>
                                <option value="air_quality">Air Quality / CO2 (ppm)</option>
                                <option value="power_meter">Power Meter (W)</option>
                                <option value="vibration">Vibration Sensor (g)</option>
                            </select>
                        </div>

                        <div>
                            <label for="location" class="block text-xs font-bold uppercase text-gray-600 mb-1">Deployment Location <span class="text-rose-500">*</span></label>
                            <input type="text" id="location" name="location" required placeholder="e.g. Building B - Rack 04" class="w-full text-xs border-gray-300 rounded-lg focus:border-cyan-500 focus:ring-cyan-500">
                        </div>

                        <div>
                            <label for="battery_percent" class="block text-xs font-bold uppercase text-gray-600 mb-1">Initial Battery %</label>
                            <input type="number" id="battery_percent" name="battery_percent" value="100" min="0" max="100" class="w-full text-xs border-gray-300 rounded-lg focus:border-cyan-500 focus:ring-cyan-500">
                        </div>

                        <div>
                            <label for="firmware_version" class="block text-xs font-bold uppercase text-gray-600 mb-1">Firmware Version</label>
                            <input type="text" id="firmware_version" name="firmware_version" value="1.0.0" class="w-full text-xs border-gray-300 rounded-lg focus:border-cyan-500 focus:ring-cyan-500">
                        </div>

                        <button type="submit" class="w-full py-2.5 px-4 bg-cyan-600 hover:bg-cyan-700 text-white font-bold rounded-xl text-xs shadow transition">
                            Register Device
                        </button>
                    </form>
                </div>

                <!-- Devices Grid / Filter -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col sm:flex-row gap-3 items-center justify-between">
                        <form method="GET" action="{{ route('devices.index') }}" class="w-full flex flex-col sm:flex-row gap-3">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, location, uuid..." class="flex-1 text-xs border-gray-300 rounded-lg focus:border-cyan-500 focus:ring-cyan-500">
                            <select name="type" class="text-xs border-gray-300 rounded-lg">
                                <option value="">All Types</option>
                                <option value="temperature" @selected(request('type') === 'temperature')>Temperature</option>
                                <option value="humidity" @selected(request('type') === 'humidity')>Humidity</option>
                                <option value="air_quality" @selected(request('type') === 'air_quality')>Air Quality</option>
                                <option value="power_meter" @selected(request('type') === 'power_meter')>Power Meter</option>
                            </select>
                            <select name="status" class="text-xs border-gray-300 rounded-lg">
                                <option value="">All Statuses</option>
                                <option value="online" @selected(request('status') === 'online')>Online</option>
                                <option value="offline" @selected(request('status') === 'offline')>Offline</option>
                                <option value="maintenance" @selected(request('status') === 'maintenance')>Maintenance</option>
                            </select>
                            <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg">Filter</button>
                        </form>
                    </div>

                    @if($devices->isEmpty())
                        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400 text-xs">
                            No devices found matching your criteria.
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($devices as $d)
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col justify-between hover:border-cyan-300 transition">
                                    <div>
                                        <div class="flex justify-between items-center">
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $d->status === 'online' ? 'bg-emerald-100 text-emerald-800' : ($d->status === 'maintenance' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                                {{ $d->status }}
                                            </span>
                                            <span class="text-xs font-semibold {{ $d->battery_percent < 20 ? 'text-rose-600' : 'text-gray-500' }}">
                                                🔋 {{ $d->battery_percent }}%
                                            </span>
                                        </div>
                                        <h4 class="mt-3 font-bold text-gray-900 text-base">
                                            <a href="{{ route('devices.show', $d) }}" class="hover:text-cyan-600">{{ $d->name }}</a>
                                        </h4>
                                        <p class="text-xs text-gray-500 mt-1">📍 {{ $d->location }}</p>
                                        <div class="mt-2 text-[11px] font-mono text-gray-400">{{ $d->device_uuid }}</div>
                                    </div>
                                    <div class="mt-5 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                                        <span class="text-gray-400 font-sans">Type: <strong>{{ ucfirst($d->type) }}</strong></span>
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('devices.show', $d) }}" class="px-3 py-1.5 bg-cyan-50 text-cyan-700 hover:bg-cyan-100 rounded-lg text-xs font-bold transition">
                                                Manage &rarr;
                                            </a>
                                            <form method="POST" action="{{ route('devices.destroy', $d) }}" onsubmit="return confirm('Delete this device?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs px-2 py-1">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div>
                            {{ $devices->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
