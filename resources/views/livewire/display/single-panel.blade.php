<div class="min-h-screen bg-gray-950 text-white flex flex-col"
     wire:poll.3000ms
     x-data="ttsManager()"
     x-init="initEcho({{ $panel->id }})"
     @queue-updated.window="onQueueUpdated($event.detail)">

    {{-- Header --}}
    <div class="flex items-center justify-between px-8 py-4 border-b border-gray-800">
        <div>
            <h1 class="text-3xl font-bold">{{ $panel->name }}</h1>
            <div class="flex items-center gap-4 mt-1">
                <p class="text-gray-400 text-sm">Kelas {{ $panel->grade }} &bull; {{ $panel->major }}</p>
                @if ($panel->location)
                <span class="flex items-center gap-1 text-indigo-400 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                    {{ $panel->location }}
                </span>
                @endif
            </div>
        </div>
        <div class="text-right">
            <div class="text-2xl font-mono text-gray-300" id="clock"></div>
            @if ($currentSlot['presenter']?->exam_date)
            <div class="text-sm text-indigo-400">
                {{ $currentSlot['presenter']->exam_date->translatedFormat('d M Y') }}
            </div>
            @else
            <div class="text-sm text-gray-500">{{ now()->translatedFormat('d F Y') }}</div>
            @endif
        </div>
    </div>

    {{-- Slot Utama --}}
    <div class="flex-1 flex flex-col justify-center px-8 py-6 gap-6">
        @if ($currentSlot['presenter'])
        <div class="text-center">
            <div class="text-sm text-blue-400 uppercase tracking-widest font-medium mb-2">Presenter</div>
            <div class="text-5xl md:text-7xl font-extrabold text-white tracking-tight mb-1">
                {{ $currentSlot['presenter']->name }}
            </div>
            @if ($currentSlot['presenter']->student_number)
            <div class="text-lg text-gray-400 font-mono">NIS: {{ $currentSlot['presenter']->student_number }}</div>
            @endif
        </div>

        @if ($currentSlot['observer1'] || $currentSlot['observer2'])
        <div class="flex gap-6 justify-center mt-2">
            @foreach (['observer1' => 'Observer 1', 'observer2' => 'Observer 2'] as $key => $label)
            @if ($currentSlot[$key])
            <div class="bg-orange-900/30 border border-orange-800 rounded-2xl px-8 py-5 text-center min-w-[200px]">
                <div class="text-xs font-medium text-orange-400 uppercase tracking-widest mb-1">{{ $label }}</div>
                <div class="text-2xl font-bold text-orange-100">{{ $currentSlot[$key]->name }}</div>
                @if ($currentSlot[$key]->student_number)
                <div class="text-xs text-orange-300 font-mono mt-1">{{ $currentSlot[$key]->student_number }}</div>
                @endif
            </div>
            @endif
            @endforeach
        </div>
        @endif

        @else
        <div class="text-center py-16">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="size-16 mx-auto mb-4 text-gray-600">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <div class="text-2xl text-gray-400 font-medium">Menunggu Giliran Berikutnya...</div>
        </div>
        @endif
    </div>

    {{-- Footer: Antrian Berikutnya --}}
    <div class="border-t border-gray-800 px-8 py-4 bg-gray-900/50">
        <div class="text-xs text-gray-500 uppercase tracking-wide mb-2">Antrian Selanjutnya</div>
        <div class="flex gap-4 flex-wrap">
            @foreach ($upcoming->take(5) as $u)
            <div class="bg-gray-800 rounded-lg px-4 py-2 text-sm">
                <span class="text-gray-400 mr-2">{{ $loop->iteration }}.</span>
                <span class="text-white">{{ $u->name }}</span>
            </div>
            @endforeach
            @if ($upcoming->isEmpty())
            <span class="text-gray-500 text-sm italic">—</span>
            @endif
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="h-1.5 bg-gray-800">
        <div class="h-full bg-blue-500 transition-all duration-700"
             style="width: {{ $total > 0 ? round($done / $total * 100) : 0 }}%">
        </div>
    </div>

    <script>
        setInterval(() => {
            const el = document.getElementById('clock');
            if (el) el.textContent = new Date().toLocaleTimeString('id-ID');
        }, 1000);
    </script>
</div>
