<?php

namespace App\Livewire\Display;

use App\Models\Panel;
use Livewire\Attributes\On;
use Livewire\Component;

class SinglePanel extends Component
{
    public Panel $panel;

    public function mount(Panel $panel): void
    {
        $this->panel = $panel;
    }

    #[On('queue-updated')]
    public function refresh(): void
    {
        // re-render dipicu event dari Echo
    }

    public function render()
    {
        $this->panel->refresh();

        $currentSlot = $this->panel->currentSlot();
        $presenter = $currentSlot['presenter'];
        $obs1 = $currentSlot['observer1'];
        $obs2 = $currentSlot['observer2'];

        // Build display state untuk poll-based TTS di Alpine
        if ($presenter) {
            $tts = "Perhatian! {$presenter->name} silakan menuju meja ujian.";
            $names = collect([$obs1?->name, $obs2?->name])->filter()->values()->join(' dan ');
            if ($names) {
                $tts .= " Observer: {$names}.";
            }
            $displayState = [$this->panel->id => [
                'presenterId' => $presenter->id,
                'ttsMessage' => $tts,
            ]];
        } else {
            $displayState = [$this->panel->id => null];
        }

        $this->dispatch('display-updated', panels: $displayState);

        $upcoming = $this->panel->participants()
            ->where('role', 'presenter')
            ->whereIn('status', ['waiting', 'skipped'])
            ->orderBy('queue_order')
            ->get();

        $done = $this->panel->donePresenterCount();
        $total = $this->panel->activePresenterCount();

        return view('livewire.display.single-panel', compact('currentSlot', 'upcoming', 'done', 'total'))
            ->layout('layouts.display');
    }
}
