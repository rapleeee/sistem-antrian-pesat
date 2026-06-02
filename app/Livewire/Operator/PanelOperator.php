<?php

namespace App\Livewire\Operator;

use App\Events\QueueUpdated;
use App\Models\Panel;
use Livewire\Component;

class PanelOperator extends Component
{
    public Panel $panel;

    public bool $authenticated = false;

    public string $pin = '';

    public string $pinError = '';

    public function mount(Panel $panel): void
    {
        $this->panel = $panel;

        // Jika panel tidak punya PIN, langsung masuk
        if (! $panel->operator_pin) {
            $this->authenticated = true;
        }

        // Cek session auth
        if (session('operator_panel_'.$panel->id)) {
            $this->authenticated = true;
        }
    }

    public function submitPin(): void
    {
        $this->pinError = '';

        if ($this->panel->verifyPin($this->pin)) {
            $this->authenticated = true;
            session(['operator_panel_'.$this->panel->id => true]);
        } else {
            $this->pinError = 'PIN salah. Silakan coba lagi.';
        }

        $this->pin = '';
    }

    public function callNext(): void
    {
        if (! $this->authenticated) {
            return;
        }

        $currentSlot = $this->panel->currentSlot();
        if ($currentSlot['presenter']) {
            // Sudah ada presenter aktif
            return;
        }

        $log = $this->panel->advanceQueue();

        if ($log) {
            broadcast(new QueueUpdated($this->panel->fresh(), $this->currentTtsMessage()));
        }
    }

    public function markDone(): void
    {
        if (! $this->authenticated) {
            return;
        }

        $log = $this->panel->completePresenter();

        if ($log) {
            broadcast(new QueueUpdated($this->panel->fresh()));
        }
    }

    public function skip(): void
    {
        if (! $this->authenticated) {
            return;
        }

        $log = $this->panel->skipPresenter();

        if ($log) {
            broadcast(new QueueUpdated($this->panel->fresh()));
        }
    }

    public function recallSkipped(int $presenterId): void
    {
        if (! $this->authenticated) {
            return;
        }

        $log = $this->panel->recallSkippedPresenter($presenterId);

        if ($log) {
            broadcast(new QueueUpdated($this->panel->fresh(), $this->currentTtsMessage()));
        }
    }

    public function reorderQueue(array $presenterIds): void
    {
        if (! $this->authenticated) {
            return;
        }

        $this->panel->reorderPresenterGroups($presenterIds);
    }

    private function currentTtsMessage(): string
    {
        $panel = $this->panel->fresh();
        $currentSlot = $panel->currentSlot();
        $presName = $currentSlot['presenter']?->name ?? '';
        $obs1Name = $currentSlot['observer1']?->name ?? '';
        $obs2Name = $currentSlot['observer2']?->name ?? '';

        $tts = "Perhatian! {$presName} silakan menuju meja ujian.";
        if ($obs1Name || $obs2Name) {
            $observerList = trim("{$obs1Name} dan {$obs2Name}", ' dan');
            $tts .= " Observer: {$observerList}.";
        }

        return $tts;
    }

    public function render()
    {
        $this->panel->refresh();

        $currentSlot = $this->panel->currentSlot();
        $nextParticipants = $this->panel->participants()
            ->where('role', 'presenter')
            ->where('status', 'waiting')
            ->orderBy('queue_order')
            ->get();

        $skippedToday = $this->panel->participants()
            ->where('role', 'presenter')
            ->where('status', 'skipped')
            ->orderBy('queue_order')
            ->get();

        $stats = [
            'total' => $this->panel->activePresenterCount(),
            'done' => $this->panel->donePresenterCount(),
            'waiting' => $this->panel->participants()->where('role', 'presenter')->where('status', 'waiting')->count(),
            'skipped' => $skippedToday->count(),
        ];

        return view('livewire.operator.panel', compact('currentSlot', 'nextParticipants', 'skippedToday', 'stats'))
            ->layout('layouts.operator', ['title' => 'Operator — '.$this->panel->name]);
    }
}
