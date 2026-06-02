<div class="min-h-screen bg-gray-950 text-white p-4"
     wire:poll.3000ms
     x-data="ttsManager()"
     x-init="initEcho()"
     @queue-updated.window="onQueueUpdated($event.detail)">

    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight">Board Antrian Sertifikasi</h1>
        <span class="text-sm text-gray-400" id="clock"></span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($panelsData as $data)
        @php
            $p    = $data['panel'];
            $slot = $data['slot'];
        @endphp
        <a href="{{ route('display.panel', $p) }}"
           class="block bg-gray-900 rounded-2xl border border-gray-800 hover:border-blue-500 transition-colors p-5 no-underline"
           id="panel-card-{{ $p->id }}">

            {{-- Header Panel --}}
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h2 class="font-bold text-white text-base leading-tight">{{ $p->name }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Kelas {{ $p->grade }} {{ $p->major }}</p>
                    @if ($p->location)
                    <div class="flex items-center gap-1 mt-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3 text-indigo-400 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                        <span class="text-xs text-indigo-300 font-medium">{{ $p->location }}</span>
                    </div>
                    @endif
                </div>
                <span @class([
                    'text-xs px-2 py-0.5 rounded-full font-medium shrink-0',
                    'bg-gray-700 text-gray-300'   => $p->status === 'inactive',
                    'bg-green-900 text-green-300'  => $p->status === 'active',
                    'bg-red-900 text-red-300'      => $p->status === 'closed',
                ])>
                    {{ match($p->status) {
                        'inactive' => 'Tidak Aktif',
                        'active'   => 'Aktif',
                        'closed'   => 'Selesai',
                        default    => $p->status,
                    } }}
                </span>
            </div>

            {{-- Progress --}}
            <div class="mb-3">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>{{ $data['done'] }} selesai</span>
                    <span>{{ $data['total'] }} total</span>
                </div>
                <div class="h-1.5 bg-gray-800 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 rounded-full transition-all"
                         style="width: {{ $data['total'] > 0 ? round($data['done'] / $data['total'] * 100) : 0 }}%">
                    </div>
                </div>
            </div>

            {{-- Slot Aktif --}}
            @if ($slot['presenter'])
            @php $examDate = $slot['presenter']->exam_date; @endphp
            @if ($examDate)
            <div class="flex items-center gap-1 mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3 text-gray-500 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <span class="text-xs text-gray-400">{{ $examDate->translatedFormat('d M Y') }}</span>
            </div>
            @endif
            <div class="bg-blue-900/40 rounded-xl p-3 mb-2">
                <div class="text-xs text-blue-300 font-medium mb-1 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />
                    </svg>
                    Presenter
                </div>
                <div class="text-white font-semibold">{{ $slot['presenter']->name }}</div>
            </div>
            @if ($slot['observer1'] || $slot['observer2'])
            <div class="flex gap-2">
                @if ($slot['observer1'])
                <div class="flex-1 bg-orange-900/30 rounded-lg p-2">
                    <div class="text-xs text-orange-300 mb-0.5">Observer 1</div>
                    <div class="text-sm font-medium text-orange-100">{{ $slot['observer1']->name }}</div>
                </div>
                @endif
                @if ($slot['observer2'])
                <div class="flex-1 bg-orange-900/30 rounded-lg p-2">
                    <div class="text-xs text-orange-300 mb-0.5">Observer 2</div>
                    <div class="text-sm font-medium text-orange-100">{{ $slot['observer2']->name }}</div>
                </div>
                @endif
            </div>
            @endif
            @else
            <div class="text-center py-4 text-gray-500 text-sm">Menunggu giliran...</div>
            @endif

            {{-- Antrian selanjutnya --}}
            @if ($data['upcoming']->isNotEmpty())
            <div class="mt-3 border-t border-gray-800 pt-3">
                <div class="text-xs text-gray-500 mb-1">Berikutnya</div>
                @foreach ($data['upcoming']->take(3) as $u)
                <div class="text-xs text-gray-400 truncate">{{ $loop->iteration }}. {{ $u->name }}</div>
                @endforeach
            </div>
            @endif
        </a>
        @endforeach
    </div>

    <script>
        // Clock
        setInterval(() => {
            const el = document.getElementById('clock');
            if (el) el.textContent = new Date().toLocaleTimeString('id-ID');
        }, 1000);
    </script>
</div>
