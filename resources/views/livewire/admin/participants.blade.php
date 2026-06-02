<div class="p-6">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.panels') }}" class="text-gray-400 hover:text-gray-700">← Kembali</a>
        <h1 class="text-2xl font-bold">Peserta — {{ $panel->name }}</h1>
    </div>

    {{-- Toolbar --}}
    <div class="flex flex-wrap gap-3 mb-5">
        <button wire:click="openCreate"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
            + Tambah Kelompok
        </button>
        <button wire:click="shuffle"
                class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium transition inline-flex items-center gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 3M21 7.5H7.5" />
            </svg>
            Acak Urutan
        </button>
        <button @click="swalConfirm({ title: 'Reset Antrian?', text: 'Semua status peserta akan dikembalikan ke waiting.', icon: 'warning', confirmText: 'Ya, Reset' }, () => $wire.resetQueue())"
                class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
            ↺ Reset Antrian
        </button>
        <button wire:click="downloadTemplate"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition inline-flex items-center gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Unduh Template Excel
        </button>
    </div>

    {{-- Import Excel --}}
    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-5">
        <h3 class="font-medium text-sm text-slate-700 mb-3">Import Peserta dari Excel</h3>
        <div class="flex gap-3 items-start">
            <input type="file" wire:model="importFile" accept=".xlsx,.xls"
                   class="text-sm text-gray-600 flex-1" />
            <button wire:click="previewImport"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium shrink-0 transition">
                Preview
            </button>
        </div>
        @error('importFile') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Preview Import --}}
    @if ($showPreview)
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm mb-5 overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
            <span class="font-medium text-sm text-slate-700">Preview Import ({{ count($importPreview) }} kelompok giliran)</span>
            <div class="flex gap-2">
                <button wire:click="confirmImport"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition inline-flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    Import
                </button>
                <button wire:click="cancelImport"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-medium transition">
                    Batal
                </button>
            </div>
        </div>
        <div class="overflow-auto max-h-64">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">#</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Presenter</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Observer 1</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Observer 2</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">Tanggal Ujian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($importPreview as $i => $row)
                    <tr>
                        <td class="px-3 py-2 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-3 py-2 font-medium text-indigo-700">{{ $row['presenter'] ?? '—' }}</td>
                        <td class="px-3 py-2 text-amber-700">{{ $row['observer1'] ?: '—' }}</td>
                        <td class="px-3 py-2 text-amber-700">{{ $row['observer2'] ?: '—' }}</td>
                        <td class="px-3 py-2 text-gray-500">
                            {{ $row['exam_date'] ? \Carbon\Carbon::parse($row['exam_date'])->translatedFormat('d M Y') : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Tabel Kelompok Giliran --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
         x-data="{ get allIds() { return {{ $participants->pluck('id')->toJson() }} } }">

        {{-- Bulk action bar (muncul saat ada yang tercentang) --}}
        @if (count($selectedIds) > 0)
        <div class="px-4 py-3 bg-indigo-50 border-b border-indigo-200 flex items-center gap-3">
            <span class="text-sm font-medium text-indigo-700">{{ count($selectedIds) }} kelompok dipilih</span>
            <button wire:click="openBulkDateModal"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition inline-flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                Atur Tanggal
            </button>
            <button @click="swalConfirm({ title: 'Hapus {{ count($selectedIds) }} kelompok?', text: 'Presenter dan observer dalam kelompok ini akan dihapus.', icon: 'warning', confirmText: 'Ya, Hapus' }, () => $wire.bulkDelete())"
                    class="bg-rose-600 hover:bg-rose-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition inline-flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                Hapus Terpilih
            </button>
            <button wire:click="$set('selectedIds', [])" class="text-xs text-gray-500 hover:text-gray-700 ml-auto">Batal pilih</button>
        </div>
        @endif

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 w-10">
                        <input type="checkbox"
                               @change="$event.target.checked ? $wire.set('selectedIds', allIds) : $wire.set('selectedIds', [])"
                               :checked="{{ json_encode($selectedIds) }}.length === allIds.length && allIds.length > 0"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    </th>
                    <th class="px-4 py-3 text-left font-medium w-10">#</th>
                    <th class="px-4 py-3 text-left font-medium">Kelompok Giliran</th>
                    <th class="px-4 py-3 text-left font-medium">Observer</th>
                    <th class="px-4 py-3 text-left font-medium">Tanggal Ujian</th>
                    <th class="px-4 py-3 text-left font-medium">Status</th>
                    <th class="px-4 py-3 text-left font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($participants as $p)
                @php $observers = $panel->observersFor($p); @endphp
                <tr @class([
                    'hover:bg-gray-50',
                    'bg-emerald-50' => $p->status === 'done',
                    'bg-indigo-50'  => $p->status === 'presenting',
                    'bg-amber-50'   => $p->status === 'observing',
                ])>
                    <td class="px-4 py-3">
                        <input type="checkbox" wire:model="selectedIds" value="{{ $p->id }}"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    </td>
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $loop->iteration }}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $p->name }}</div>
                        @if ($p->student_number)
                        <div class="text-xs text-gray-400 font-mono">NIS: {{ $p->student_number }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="space-y-1">
                            <div class="text-xs text-amber-700">
                                <span class="font-medium">Observer 1:</span>
                                {{ $observers->get(0)?->name ?? '—' }}
                            </div>
                            <div class="text-xs text-amber-700">
                                <span class="font-medium">Observer 2:</span>
                                {{ $observers->get(1)?->name ?? '—' }}
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">
                        {{ $p->exam_date ? $p->exam_date->translatedFormat('d M Y') : '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <span @class([
                            'px-2 py-0.5 rounded-full text-xs font-medium',
                            'bg-slate-100 text-slate-600'     => $p->status === 'waiting',
                            'bg-indigo-100 text-indigo-700'   => $p->status === 'presenting',
                            'bg-amber-100 text-amber-700'     => $p->status === 'observing',
                            'bg-emerald-100 text-emerald-700' => $p->status === 'done',
                            'bg-rose-100 text-rose-700'       => $p->status === 'skipped',
                        ])>
                            {{ $p->statusLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <button wire:click="openEdit({{ $p->id }})"
                                    class="text-gray-600 hover:text-gray-900 text-xs">Edit</button>
                            <button @click="swalConfirm({ title: 'Hapus kelompok {{ addslashes($p->name) }}?', text: 'Presenter dan observer akan dihapus dari antrian.', icon: 'warning', confirmText: 'Ya, Hapus' }, () => $wire.delete({{ $p->id }}))"
                                    class="text-rose-600 hover:text-rose-800 text-xs">Hapus</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada kelompok giliran.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal Tambah/Edit Kelompok --}}
    @if ($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
            <h2 class="text-lg font-semibold mb-5">
                {{ $editingId ? 'Edit Kelompok Giliran' : 'Tambah Kelompok Giliran' }}
            </h2>

            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Presenter</label>
                    <input wire:model="name" type="text"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIS (opsional)</label>
                    <input wire:model="student_number" type="text"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observer 1</label>
                    <input wire:model="observer1" type="text"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    @error('observer1') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observer 2</label>
                    <input wire:model="observer2" type="text"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    @error('observer2') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg text-sm font-medium transition">
                        Simpan
                    </button>
                    <button type="button" wire:click="$set('showModal', false)"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg text-sm font-medium transition">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Modal Bulk Edit Tanggal --}}
    @if ($showBulkDateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
            <h2 class="text-lg font-semibold mb-1">Atur Tanggal Ujian</h2>
            <p class="text-sm text-gray-500 mb-5">
                Akan diterapkan ke <strong>{{ count($selectedIds) }}</strong> kelompok yang dipilih.
            </p>

            <form wire:submit="saveBulkDate" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Ujian</label>
                    <input wire:model="bulkDate" type="date"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    @error('bulkDate') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg text-sm font-medium transition">
                        Simpan
                    </button>
                    <button type="button" wire:click="$set('showBulkDateModal', false)"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg text-sm font-medium transition">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
