<?php

namespace App\Livewire\Display;

use App\Models\Panel;
use Livewire\Attributes\On;
use Livewire\Component;

class AllPanels extends Component
{
    #[On('queue-updated')]
    public function refresh(): void
    {
        // re-render dipicu event dari Echo
    }

    public function render()
    {
        $panels = Panel::with(['participants' => function ($q) {
            $q->whereIn('status', ['presenting', 'observing', 'waiting', 'skipped'])
                ->orderBy('queue_order');
        }])->orderBy('grade')->orderBy('major')->get();

        $panelsData = $panels->map(function (Panel $p) {
            $slot = $p->currentSlot();

            $upcoming = $p->participants()
                ->where('role', 'presenter')
                ->whereIn('status', ['waiting', 'skipped'])
                ->orderBy('queue_order')
                ->take(5)
                ->get();

            return [
                'panel' => $p,
                'slot' => $slot,
                'upcoming' => $upcoming,
                'done' => $p->donePresenterCount(),
                'total' => $p->activePresenterCount(),
            ];
        });

        // Build display state untuk poll-based TTS di Alpine
        $displayState = [];
        foreach ($panelsData as $d) {
            $presenter = $d['slot']['presenter'];
            $obs1 = $d['slot']['observer1'];
            $obs2 = $d['slot']['observer2'];

            if ($presenter) {
                $tts = "Perhatian! {$presenter->name} silakan menuju meja ujian.";
                $names = collect([$obs1?->name, $obs2?->name])->filter()->values()->join(' dan ');
                if ($names) {
                    $tts .= " Observer: {$names}.";
                }
                $displayState[$d['panel']->id] = [
                    'presenterId' => $presenter->id,
                    'ttsMessage' => $tts,
                ];
            } else {
                $displayState[$d['panel']->id] = null;
            }
        }

        $this->dispatch('display-updated', panels: $displayState);

        return view('livewire.display.all-panels', compact('panelsData'))
            ->layout('layouts.display');
    }
}
