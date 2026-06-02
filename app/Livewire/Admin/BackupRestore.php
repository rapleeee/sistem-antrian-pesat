<?php

namespace App\Livewire\Admin;

use App\Models\Panel;
use App\Models\Participant;
use App\Models\QueueLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Carbon;

class BackupRestore extends Component
{
    use WithFileUploads;

    public $backupFile;

    public function downloadBackup()
    {
        $data = [
            'panels' => Panel::all()->toArray(),
            'participants' => Participant::all()->toArray(),
            'queue_logs' => QueueLog::all()->toArray(),
            'meta' => [
                'exported_at' => Carbon::now()->toDateTimeString(),
                'version' => '1.0'
            ]
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT);
        $filename = 'backup-sistem-antrian-' . date('Y-m-d-H-i-s') . '.json';

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $filename);
    }

    public function restoreData()
    {
        $this->validate([
            'backupFile' => 'required|file|mimetypes:application/json,text/plain|max:10240',
        ]);

        $content = file_get_contents($this->backupFile->getRealPath());
        $data = json_decode($content, true);

        if (!$data || !isset($data['panels']) || !isset($data['participants']) || !isset($data['queue_logs'])) {
            $this->dispatch('swal-toast', title: 'File backup tidak valid atau rusak.', icon: 'error');
            return;
        }

        try {
            DB::transaction(function () use ($data) {
                Schema::disableForeignKeyConstraints();

                QueueLog::query()->delete();
                Participant::query()->delete();
                Panel::query()->delete();

                if (!empty($data['panels'])) {
                    DB::table('panels')->insert($this->sanitizeRecords($data['panels']));
                }
                
                if (!empty($data['participants'])) {
                    DB::table('participants')->insert($this->sanitizeRecords($data['participants']));
                }
                
                if (!empty($data['queue_logs'])) {
                    DB::table('queue_logs')->insert($this->sanitizeRecords($data['queue_logs']));
                }

                Schema::enableForeignKeyConstraints();
            });

            $this->backupFile = null;
            $this->dispatch('swal-toast', title: 'Data berhasil direstore!', icon: 'success');
        } catch (\Exception $e) {
            Schema::enableForeignKeyConstraints();
            $this->dispatch('swal-toast', title: 'Gagal merestore data: ' . $e->getMessage(), icon: 'error');
        }
    }

    private function sanitizeRecords(array $records): array
    {
        return array_map(function ($record) {
            foreach ($record as $key => $value) {
                if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value)) {
                    try {
                        if ($key === 'exam_date') {
                            $record[$key] = Carbon::parse($value)->toDateString();
                        } else {
                            $record[$key] = Carbon::parse($value)->toDateTimeString();
                        }
                    } catch (\Exception $e) {
                        // ignore and leave as is if parsing fails
                    }
                }
            }
            return $record;
        }, $records);
    }

    public function render()
    {
        return view('livewire.admin.backup-restore')->layout('layouts.app', ['title' => 'Backup & Restore Data']);
    }
}
