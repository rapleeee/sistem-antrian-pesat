<?php

namespace App\Livewire\Admin;

use App\Models\Panel;
use App\Models\QueueLog;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class Reports extends Component
{
    public ?int $selectedPanelId = null;

    public function mount(): void
    {
        $this->selectedPanelId = Panel::value('id');
    }

    public function exportExcel()
    {
        $panelId = $this->selectedPanelId;

        return Excel::download(new \App\Exports\QueueLogsExport($panelId), 'laporan-antrian.xlsx');
    }

    public function render()
    {
        $panels = Panel::orderBy('grade')->orderBy('major')->get();
        $panel  = $this->selectedPanelId ? Panel::find($this->selectedPanelId) : null;

        $logs = collect();
        $avgDuration = null;
        $doneCount = 0;

        if ($panel) {
            $logs = QueueLog::with(['presenter', 'observer1', 'observer2'])
                ->where('panel_id', $panel->id)
                ->latest()
                ->get();

            $doneLogs = $logs->where('action', 'done')
                ->filter(fn ($l) => $l->started_at && $l->ended_at);

            $doneCount = $doneLogs->count();

            if ($doneCount > 0) {
                $avgDuration = $doneLogs->avg(fn ($l) => $l->started_at->diffInMinutes($l->ended_at));
            }
        }

        return view('livewire.admin.reports', compact('panels', 'panel', 'logs', 'avgDuration', 'doneCount'))
            ->layout('layouts.app', ['title' => 'Laporan']);
    }
}
