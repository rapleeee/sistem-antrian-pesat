<?php

namespace App\Exports;

use App\Models\QueueLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class QueueLogsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private ?int $panelId = null) {}

    public function collection()
    {
        $query = QueueLog::with(['panel', 'presenter', 'observer1', 'observer2'])
            ->orderBy('started_at');

        if ($this->panelId) {
            $query->where('panel_id', $this->panelId);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Panel',
            'Presenter',
            'Observer 1',
            'Observer 2',
            'Aksi',
            'Mulai',
            'Selesai',
            'Durasi (menit)',
        ];
    }

    public function map($log): array
    {
        return [
            $log->panel?->name,
            $log->presenter?->name,
            $log->observer1?->name,
            $log->observer2?->name,
            $log->action,
            $log->started_at?->format('Y-m-d H:i:s'),
            $log->ended_at?->format('Y-m-d H:i:s'),
            $log->duration_minutes,
        ];
    }
}
