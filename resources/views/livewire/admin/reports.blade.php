<div class="p-6">
    <h1 class="text-2xl font-bold mb-5">Laporan Antrian</h1>

    <div class="flex gap-3 mb-6 flex-wrap">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Panel</label>
            <select wire:model.live="selectedPanelId"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @foreach ($panels as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        </div>

        @if ($panel)
        <div class="flex items-end">
            <button wire:click="exportExcel"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                ⬇ Export Excel
            </button>
        </div>
        @endif
    </div>

    @if ($panel)
    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center shadow-sm">
            <div class="text-3xl font-bold text-blue-600">{{ $doneCount }}</div>
            <div class="text-xs text-gray-500 mt-1">Selesai Presentasi</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center shadow-sm">
            <div class="text-3xl font-bold text-green-600">
                {{ $avgDuration ? number_format($avgDuration, 1) : '—' }}
            </div>
            <div class="text-xs text-gray-500 mt-1">Rata-rata Durasi (menit)</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center shadow-sm">
            <div class="text-3xl font-bold text-gray-700">{{ $logs->count() }}</div>
            <div class="text-xs text-gray-500 mt-1">Total Giliran Tercatat</div>
        </div>
    </div>

    {{-- Tabel Log --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Presenter</th>
                    <th class="px-4 py-3 text-left font-medium">Observer 1</th>
                    <th class="px-4 py-3 text-left font-medium">Observer 2</th>
                    <th class="px-4 py-3 text-left font-medium">Aksi</th>
                    <th class="px-4 py-3 text-left font-medium">Mulai</th>
                    <th class="px-4 py-3 text-left font-medium">Selesai</th>
                    <th class="px-4 py-3 text-left font-medium">Durasi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">{{ $log->presenter?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $log->observer1?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $log->observer2?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span @class([
                            'px-2 py-0.5 rounded-full text-xs font-medium',
                            'bg-blue-100 text-blue-700'   => $log->action === 'started',
                            'bg-green-100 text-green-700' => $log->action === 'done',
                            'bg-red-100 text-red-700'     => $log->action === 'skipped',
                        ])>
                            {{ ucfirst($log->action) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">
                        {{ $log->started_at?->format('H:i:s') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-gray-500">
                        {{ $log->ended_at?->format('H:i:s') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-gray-500">
                        {{ $log->duration_minutes ? $log->duration_minutes . ' mnt' : '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada data log.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
</div>
