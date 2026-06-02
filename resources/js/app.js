import './bootstrap';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import Swal from 'sweetalert2';

window.Pusher = Pusher;

// ─── SweetAlert2 Global Config ────────────────────────────────────────────────
const SwalBase = Swal.mixin({
    customClass: {
        popup:         'swal-popup',
        title:         'swal-title',
        htmlContainer: 'swal-text',
        confirmButton: 'swal-btn-confirm',
        cancelButton:  'swal-btn-cancel',
        icon:          'swal-icon',
    },
    buttonsStyling: false,
    focusConfirm: false,
});

window.Swal = SwalBase;

// Toast helper (top-right, auto close)
window.swalToast = function ({ title, icon = 'success', timer = 2800 }) {
    SwalBase.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer,
        timerProgressBar: true,
        title,
        icon,
    });
};

// Confirm helper — callback dipanggil jika user konfirmasi
window.swalConfirm = function ({ title, text = '', icon = 'warning', confirmText = 'Ya, Lanjutkan', cancelText = 'Batal' }, callback) {
    SwalBase.fire({
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed && typeof callback === 'function') callback();
    });
};

// Livewire event listener untuk toast notifikasi dari PHP
document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener('swal-toast', (event) => {
        const { title, icon } = event.detail ?? {};
        if (title) swalToast({ title, icon: icon ?? 'success' });
    });
});

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});

/**
 * TTS Manager — dipakai di halaman display via Alpine.js
 * Usage: x-data="ttsManager()"  x-init="initEcho(panelId)"
 *
 * Dua jalur TTS:
 *  1. WebSocket (Echo/Reverb) — langsung via initEcho()
 *  2. Poll fallback — via @display-updated.window → handleDisplayUpdate()
 *     PHP dispatch 'display-updated' di setiap render(), Alpine membandingkan
 *     presenter sebelumnya. Bicara hanya jika presenter BERUBAH.
 */
window.ttsManager = function () {
    return {
        unlocked: false,
        lastPresenters: {}, // { [panelId]: presenterId | null }

        // Harus dipanggil setelah user klik — unlock browser speech policy
        unlock() {
            this.unlocked = true;
            // Ucapkan satu kata pelan untuk "panas-in" API speech browser
            const utter = new SpeechSynthesisUtterance('Siap');
            utter.lang   = 'id-ID';
            utter.volume = 0.01;
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(utter);
            // Chrome workaround: speechSynthesis bisa "dormant" setelah ~15 detik idle
            setInterval(() => {
                if (!window.speechSynthesis.speaking) {
                    window.speechSynthesis.pause();
                    window.speechSynthesis.resume();
                }
            }, 10000);
        },

        speak(text) {
            if (!('speechSynthesis' in window) || !text || !this.unlocked) return;
            window.speechSynthesis.cancel();
            const utter = new SpeechSynthesisUtterance(text);
            utter.lang  = 'id-ID';
            utter.rate  = 0.9;
            utter.pitch = 1.0;
            window.speechSynthesis.speak(utter);
        },

        // Dipanggil oleh @display-updated.window setelah setiap render Livewire (poll)
        // PHP mengirim { panels: { [panelId]: { presenterId, ttsMessage } | null } }
        handleDisplayUpdate(detail) {
            const panels = detail?.panels ?? {};
            Object.entries(panels).forEach(([panelId, info]) => {
                const pid    = parseInt(panelId, 10);
                const prevId = this.lastPresenters[pid];

                if (!info || !info.presenterId) {
                    this.lastPresenters[pid] = null;
                    return;
                }

                if (prevId === undefined) {
                    // Inisialisasi halaman pertama — simpan state, JANGAN speak
                    this.lastPresenters[pid] = info.presenterId;
                    return;
                }

                if (info.presenterId !== prevId) {
                    // Presenter baru terdeteksi via poll → speak
                    this.lastPresenters[pid] = info.presenterId;
                    if (info.ttsMessage) this.speak(info.ttsMessage);
                }
            });
        },

        initEcho(panelId = null) {
            const channels = ['display.all'];
            if (panelId) channels.push(`panel.${panelId}`);

            channels.forEach((ch) => {
                window.Echo.channel(ch).listen('.queue.updated', (data) => {
                    if (window.Livewire) {
                        window.Livewire.dispatch('queue-updated', data);
                    }
                    if (data.ttsMessage) {
                        this.speak(data.ttsMessage);
                    }
                    // Sinkronkan lastPresenters agar poll tidak double-speak
                    if (data.panelId != null && data.slot?.presenter?.id != null) {
                        this.lastPresenters[data.panelId] = data.slot.presenter.id;
                    }
                });
            });
        },

        onQueueUpdated(data) {
            if (data?.ttsMessage) this.speak(data.ttsMessage);
        },
    };
};
