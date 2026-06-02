<?php

namespace App\Livewire\Admin;

use App\Models\Panel;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $panels = Panel::withCount([
            'participants' => fn ($q) => $q->where('role', 'presenter'),
            'participants as waiting_count' => fn ($q) => $q->where('role', 'presenter')->whereIn('status', ['waiting', 'skipped']),
            'participants as done_count' => fn ($q) => $q->where('role', 'presenter')->where('status', 'done'),
        ])->orderBy('grade')->orderBy('major')->get();

        return view('livewire.admin.dashboard', compact('panels'))
            ->layout('layouts.app', ['title' => 'Admin Dashboard']);
    }
}
