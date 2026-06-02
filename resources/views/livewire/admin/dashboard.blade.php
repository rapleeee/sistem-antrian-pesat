<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Dashboard Admin</h1>
        <span class="text-sm text-gray-500">{{ now()->format('l, d F Y') }}</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($panels as $panel)
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold text-lg">{{ $panel->name }}</h2>
                <span @class([
                    'px-2 py-0.5 rounded-full text-xs font-medium',
                    'bg-gray-100 text-gray-600'   => $panel->status === 'inactive',
                    'bg-green-100 text-green-700'  => $panel->status === 'active',
                    'bg-red-100 text-red-700'      => $panel->status === 'closed',
                ])>
                    {{ match($panel->status) {
                        'inactive' => 'Tidak Aktif',
                        'active'   => 'Aktif',
                        'closed'   => 'Selesai',
                    } }}
                </span>
            </div>

            <div class="text-sm text-gray-500 space-y-1 mb-4">
                <div>Kelas: <span class="font-medium text-gray-700">{{ $panel->grade }}</span></div>
                <div>Jurusan: <span class="font-medium text-gray-700">{{ $panel->major }}</span></div>
                <div>Total peserta: <span class="font-medium text-gray-700">{{ $panel->participants_count }}</span></div>
                <div>Sudah selesai: <span class="font-medium text-gray-700">{{ $panel->done_count }}</span></div>
            </div>

            @if ($panel->participants_count > 0)
            <div class="mb-4">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Progress</span>
                    <span>{{ $panel->participants_count > 0 ? round($panel->done_count / $panel->participants_count * 100) : 0 }}%</span>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 rounded-full transition-all"
                         style="width: {{ $panel->participants_count > 0 ? round($panel->done_count / $panel->participants_count * 100) : 0 }}%">
                    </div>
                </div>
            </div>
            @endif

            <div class="flex gap-2 flex-wrap">
                <a href="{{ route('admin.participants', $panel) }}"
                   class="text-xs bg-blue-50 text-blue-700 hover:bg-blue-100 px-3 py-1.5 rounded-lg font-medium transition">
                    Peserta
                </a>
                <a href="{{ route('display.panel', $panel) }}" target="_blank"
                   class="text-xs bg-gray-50 text-gray-700 hover:bg-gray-100 px-3 py-1.5 rounded-lg font-medium transition">
                    Display
                </a>
                <a href="{{ route('operator.panel', $panel) }}"
                   class="text-xs bg-orange-50 text-orange-700 hover:bg-orange-100 px-3 py-1.5 rounded-lg font-medium transition">
                    Operator
                </a>
            </div>
        </div>
        @endforeach
    </div>
</div>
