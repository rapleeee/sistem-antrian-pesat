<div class="min-h-screen bg-gray-50 flex flex-col">
    @if (! $authenticated)
    {{-- PIN Login --}}
    <div class="flex-1 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-sm p-8">
            <h1 class="text-xl font-bold text-center mb-1">{{ $panel->name }}</h1>
            <p class="text-sm text-gray-500 text-center mb-6">Masukkan PIN Operator</p>

            <form wire:submit="submitPin" class="space-y-4">
                <input wire:model="pin" type="password" maxlength="6" placeholder="PIN"
                       autofocus
                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-center text-2xl font-mono tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500" />

                @if ($pinError)
                <p class="text-red-500 text-sm text-center">{{ $pinError }}</p>
                @endif

                <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-xl font-medium transition">
                    Masuk
                </button>
            </form>
        </div>
    </div>
    @else
    {{-- Operator Dashboard --}}
    <div class="p-5 flex-1 flex flex-col gap-4 max-w-3xl mx-auto w-full">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold">{{ $panel->name }}</h1>
                <p class="text-sm text-gray-500">
                    Selesai: {{ $stats['done'] }}/{{ $stats['total'] }} •
                    Menunggu: {{ $stats['waiting'] }} •
                    Terlewati hari ini: {{ $stats['skipped'] }}
                </p>
            </div>
            <span @class([
                'px-3 py-1 rounded-full text-sm font-medium',
                'bg-slate-100 text-slate-600'    => $panel->status === 'inactive',
                'bg-emerald-100 text-emerald-700' => $panel->status === 'active',
                'bg-rose-100 text-rose-700'       => $panel->status === 'closed',
            ])>
                {{ match($panel->status) {
                    'inactive' => 'Tidak Aktif',
                    'active'   => 'Aktif',
                    'closed'   => 'Selesai',
                    default    => $panel->status,
                } }}
            </span>
        </div>

        {{-- Slot Saat Ini --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-4">Giliran Saat Ini</h2>

            @if ($currentSlot['presenter'])
            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-3 md:col-span-1 text-center bg-indigo-50 border border-indigo-100 rounded-xl p-4">
                    <div class="text-xs font-medium text-indigo-500 uppercase mb-1">Presenter</div>
                    <div class="text-lg font-bold text-indigo-800">{{ $currentSlot['presenter']->name }}</div>
                    @if ($currentSlot['presenter']->student_number)
                    <div class="text-xs text-indigo-400 font-mono">{{ $currentSlot['presenter']->student_number }}</div>
                    @endif
                </div>

                <div class="text-center bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <div class="text-xs font-medium text-slate-500 uppercase mb-1">Observer 1</div>
                    <div class="font-semibold text-slate-700">
                        {{ $currentSlot['observer1']?->name ?? '—' }}
                    </div>
                </div>

                <div class="text-center bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <div class="text-xs font-medium text-slate-500 uppercase mb-1">Observer 2</div>
                    <div class="font-semibold text-slate-700">
                        {{ $currentSlot['observer2']?->name ?? '—' }}
                    </div>
                </div>
            </div>

            {{-- Aksi --}}
            <div class="flex gap-3 mt-5">
                <button wire:click="markDone"
                        class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-xl font-semibold text-sm transition inline-flex items-center justify-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    Selesai
                </button>
                <button wire:click="skip"
                        class="flex-1 bg-rose-500 hover:bg-rose-600 text-white py-3 rounded-xl font-semibold text-sm transition inline-flex items-center justify-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.689c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 1 0 1.954l-7.108 4.061A1.125 1.125 0 0 1 3 16.811V8.69ZM12.75 8.689c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 1 0 1.954l-7.108 4.061a1.125 1.125 0 0 1-1.683-.977V8.69Z" />
                    </svg>
                    Lewati
                </button>
            </div>

            @else
            <div class="text-center py-6 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="size-12 mx-auto mb-2 text-gray-300">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.1 13.177a2.25 2.25 0 0 0-.1.661Z" />
                </svg>
                <p class="text-sm">Tidak ada peserta aktif.</p>
            </div>

            {{-- Tombol Panggil --}}
            @if ($stats['waiting'] > 0)
            <div class="mt-4">
                <button wire:click="callNext"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-xl font-semibold text-sm transition inline-flex items-center justify-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 1 8.835-2.535m0 0A23.74 23.74 0 0 1 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
                    </svg>
                    Panggil Berikutnya
                </button>
            </div>
            @endif
            @endif

            @if ($currentSlot['presenter'])
            <div class="mt-3 border-t pt-3">
                <button wire:click="callNext"
                        disabled
                        class="w-full bg-gray-100 text-gray-400 py-2 rounded-xl font-medium text-sm cursor-not-allowed">
                    Selesaikan giliran ini dulu sebelum memanggil berikutnya
                </button>
            </div>
            @endif
        </div>

        {{-- Antrian & Atur Urutan --}}
        @if ($nextParticipants->isNotEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5"
             x-data="{
                dragging: null,
                order: @js($nextParticipants->pluck('id')->values()),
                move(target) {
                    if (!this.dragging || this.dragging === target) return;
                    const from = this.order.indexOf(this.dragging);
                    const to = this.order.indexOf(target);
                    if (from === -1 || to === -1) return;
                    this.order.splice(from, 1);
                    this.order.splice(to, 0, this.dragging);
                    $wire.reorderQueue(this.order);
                }
             }">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-medium text-slate-500 uppercase tracking-wide">Atur Urutan Antrian</h2>
                <span class="text-xs text-slate-400">Drag kelompok untuk ubah posisi</span>
            </div>
            <ol class="space-y-1.5">
                @foreach ($nextParticipants as $i => $p)
                @php $observers = $panel->observersFor($p); @endphp
                <li draggable="true"
                    @dragstart="dragging = {{ $p->id }}"
                    @dragover.prevent
                    @drop.prevent="move({{ $p->id }})"
                    @dragend="dragging = null"
                    class="flex items-center gap-2.5 py-2 px-2 rounded-lg border border-transparent hover:bg-slate-50 hover:border-slate-200 cursor-move">
                    <span class="text-slate-300">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h.008v.008H8.25V6.75Zm0 5.25h.008v.008H8.25V12Zm0 5.25h.008v.008H8.25v-.008Zm7.5-10.5h.008v.008h-.008V6.75Zm0 5.25h.008v.008h-.008V12Zm0 5.25h.008v.008h-.008v-.008Z" />
                        </svg>
                    </span>
                    <span class="w-5 h-5 flex items-center justify-center rounded-full text-xs font-mono shrink-0
                        @if($i === 0) bg-indigo-100 text-indigo-600
                        @else bg-slate-100 text-slate-500
                        @endif">
                        {{ $i + 1 }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-slate-800 truncate">{{ $p->name }}</div>
                        <div class="text-xs text-slate-500 truncate">
                            Observer: {{ $observers->pluck('name')->filter()->join(' dan ') ?: '—' }}
                        </div>
                    </div>
                </li>
                @endforeach
            </ol>
        </div>
        @endif

        @if ($skippedToday->isNotEmpty())
        <div class="bg-white rounded-2xl border border-rose-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-medium text-rose-500 uppercase tracking-wide">Terlewati Hari Ini</h2>
                <span class="text-xs text-slate-400">Bisa dipanggil ulang selama tidak ada giliran aktif</span>
            </div>
            <div class="space-y-2">
                @foreach ($skippedToday as $p)
                @php $observers = $panel->observersFor($p); @endphp
                <div class="flex items-center gap-3 rounded-xl border border-rose-100 bg-rose-50/50 px-3 py-2">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-slate-800 truncate">{{ $p->name }}</div>
                        <div class="text-xs text-slate-500 truncate">
                            Observer: {{ $observers->pluck('name')->filter()->join(' dan ') ?: '—' }}
                        </div>
                    </div>
                    <button wire:click="recallSkipped({{ $p->id }})"
                            @disabled((bool) $currentSlot['presenter'])
                            @class([
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition',
                                'bg-rose-600 hover:bg-rose-700 text-white' => ! $currentSlot['presenter'],
                                'bg-slate-100 text-slate-400 cursor-not-allowed' => (bool) $currentSlot['presenter'],
                            ])>
                        Panggil Ulang
                    </button>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif
</div>
