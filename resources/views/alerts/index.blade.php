<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Telemetry Incident & Threshold Alerts') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Tabs Filter -->
            <div class="flex items-center space-x-3 text-xs font-bold">
                <a href="{{ route('alerts.index') }}" class="px-4 py-2 rounded-xl {{ !request()->has('resolved') ? 'bg-cyan-600 text-white' : 'bg-white border text-gray-700 hover:bg-gray-50' }}">
                    Active Unresolved
                </a>
                <a href="{{ route('alerts.index', ['resolved' => 1]) }}" class="px-4 py-2 rounded-xl {{ request('resolved') == '1' ? 'bg-cyan-600 text-white' : 'bg-white border text-gray-700 hover:bg-gray-50' }}">
                    Resolved History
                </a>
            </div>

            <!-- Alerts Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                @if($alerts->isEmpty())
                    <div class="p-12 text-center text-gray-400 text-xs">
                        {{ request('resolved') ? 'No resolved alert records.' : 'All clear! No active threshold breaches detected.' }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 border-b border-gray-100 text-gray-500 uppercase text-[10px] tracking-wider">
                                <tr>
                                    <th class="p-4">Triggered Time</th>
                                    <th class="p-4">Device</th>
                                    <th class="p-4">Severity</th>
                                    <th class="p-4">Incident Message</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 font-mono">
                                @foreach($alerts as $a)
                                    <tr class="hover:bg-gray-50">
                                        <td class="p-4 text-gray-500 font-sans">{{ $a->triggered_at->format('M d, H:i:s') }}</td>
                                        <td class="p-4 font-bold text-gray-900 font-sans">
                                            <a href="{{ route('devices.show', $a->device) }}" class="hover:underline text-cyan-700">
                                                {{ $a->device->name }}
                                            </a>
                                        </td>
                                        <td class="p-4 font-sans">
                                            <span class="px-2 py-0.5 rounded font-bold uppercase text-[10px] {{ $a->severity === 'critical' ? 'bg-rose-100 text-rose-800' : ($a->severity === 'warning' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800') }}">
                                                {{ $a->severity }}
                                            </span>
                                        </td>
                                        <td class="p-4 font-sans text-gray-700">{{ $a->message }}</td>
                                        <td class="p-4 font-sans">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $a->is_resolved ? 'bg-gray-100 text-gray-600' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                                {{ $a->is_resolved ? 'Resolved' : 'Active' }}
                                            </span>
                                        </td>
                                        <td class="p-4 text-right font-sans">
                                            @if(!$a->is_resolved)
                                                <form method="POST" action="{{ route('alerts.resolve', $a) }}">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 rounded-lg text-xs font-bold transition">
                                                        Resolve
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-gray-400 text-xs">Resolved {{ $a->resolved_at?->diffForHumans() }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-100">
                        {{ $alerts->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
