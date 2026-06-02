<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Backup & Restore Data</h1>
        <p class="text-gray-500 text-sm mt-1">Simpan data antrian sebagai cadangan atau kembalikan data dari file cadangan sebelumnya.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        {{-- BACKUP SECTION --}}
        <div class="bg-white border border-gray-200 shadow-sm rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-indigo-100 p-2.5 rounded-lg text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Download Backup</h2>
                    <p class="text-sm text-gray-500">Ekspor seluruh panel, peserta, dan riwayat</p>
                </div>
            </div>

            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mb-5 text-sm text-indigo-800">
                Data akan diekspor dalam format <strong>JSON</strong> utuh. Simpan file ini di tempat yang aman. Anda dapat menggunakan file ini untuk memulihkan keadaan sistem seperti saat ini.
            </div>

            <button wire:click="downloadBackup" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-xl font-medium transition inline-flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Download File JSON
            </button>
        </div>


        {{-- RESTORE SECTION --}}
        <div class="bg-white border border-gray-200 shadow-sm rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-amber-100 p-2.5 rounded-lg text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Restore Data</h2>
                    <p class="text-sm text-gray-500">Kembalikan data dari file backup JSON</p>
                </div>
            </div>

            <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 mb-5 text-sm text-rose-800">
                <strong>Peringatan!</strong> Melakukan restore akan <strong>MENGHAPUS</strong> seluruh data panel, peserta, dan antrian yang ada saat ini. Pastikan Anda mengunggah file backup yang benar.
            </div>

            <form wire:submit.prevent="restoreData">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih File Backup (.json)</label>
                    <input type="file" wire:model="backupFile" accept=".json"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    @error('backupFile') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <button type="submit" 
                        class="w-full bg-rose-600 hover:bg-rose-700 text-white py-3 rounded-xl font-medium transition inline-flex items-center justify-center gap-2"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z" />
                    </svg>
                    <span wire:loading.remove wire:target="restoreData">Timpa Data Sekarang</span>
                    <span wire:loading wire:target="restoreData">Memproses Restore...</span>
                </button>
            </form>
        </div>

    </div>
</div>
