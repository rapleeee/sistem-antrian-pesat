<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Kelola Panel</h1>
        <button wire:click="openCreate"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
            + Tambah Panel
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Nama Panel</th>
                    <th class="px-4 py-3 text-left font-medium">Kelas</th>
                    <th class="px-4 py-3 text-left font-medium">Jurusan</th>
                    <th class="px-4 py-3 text-left font-medium">Status</th>
                    <th class="px-4 py-3 text-left font-medium">Peserta</th>
                    <th class="px-4 py-3 text-left font-medium">PIN</th>
                    <th class="px-4 py-3 text-left font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($panels as $panel)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">{{ $panel->name }}</td>
                    <td class="px-4 py-3">{{ $panel->grade }}</td>
                    <td class="px-4 py-3">{{ $panel->major }}</td>
                    <td class="px-4 py-3">
                        <button wire:click="toggleStatus({{ $panel->id }})"
                                @class([
                                    'px-2 py-0.5 rounded-full text-xs font-medium cursor-pointer hover:opacity-80',
                                    'bg-slate-100 text-slate-600'    => $panel->status === 'inactive',
                                    'bg-emerald-100 text-emerald-700' => $panel->status === 'active',
                                    'bg-rose-100 text-rose-700'       => $panel->status === 'closed',
                                ])>
                            {{ match($panel->status) {
                                'inactive' => 'Tidak Aktif',
                                'active'   => 'Aktif',
                                'closed'   => 'Selesai',
                            } }}
                        </button>
                    </td>
                    <td class="px-4 py-3">{{ $panel->participants_count }}</td>
                    <td class="px-4 py-3 font-mono text-gray-400">
                        {{ $panel->operator_pin ? str_repeat('•', strlen($panel->operator_pin)) : '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <a href="{{ route('admin.participants', $panel) }}"
                               class="text-indigo-600 hover:underline text-xs">Peserta</a>
                            <button wire:click="openEdit({{ $panel->id }})"
                                    class="text-gray-600 hover:text-gray-900 text-xs">Edit</button>
                            <button
                                    @click="swalConfirm({ title: 'Hapus Panel?', text: 'Semua peserta akan ikut terhapus. Tindakan ini tidak bisa dibatalkan.', confirmText: 'Ya, Hapus', icon: 'warning' }, () => $wire.delete({{ $panel->id }}))"
                                    class="text-rose-600 hover:text-rose-800 text-xs">Hapus</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada panel.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal Tambah/Edit Panel --}}
    @if ($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <h2 class="text-lg font-semibold mb-5">
                {{ $editingId ? 'Edit Panel' : 'Tambah Panel Baru' }}
            </h2>

            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Panel</label>
                    <input wire:model="name" type="text" placeholder="cth: Panel RPL Kelas 10"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
                        <select wire:model="grade"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                        </select>
                        @error('grade') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jurusan</label>
                        <select wire:model="major"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="RPL">RPL</option>
                            <option value="DKV">DKV</option>
                            <option value="TKJ">TKJ</option>
                        </select>
                        @error('major') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select wire:model="status"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="inactive">Tidak Aktif</option>
                        <option value="active">Aktif</option>
                        <option value="closed">Selesai</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PIN Operator</label>
                    <input wire:model="operator_pin" type="text" maxlength="6" placeholder="4–6 digit (opsional)"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono" />
                    @error('operator_pin') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
</div>
