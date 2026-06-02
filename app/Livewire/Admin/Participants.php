<?php

namespace App\Livewire\Admin;

use App\Models\Panel;
use App\Models\Participant;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class Participants extends Component
{
    use WithFileUploads;

    public Panel $panel;

    // Form tambah/edit manual
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $student_number = '';

    public string $observer1 = '';

    public string $observer2 = '';

    // Import Excel
    public $importFile = null;

    public array $importPreview = [];

    public bool $showPreview = false;

    // Bulk actions
    public array $selectedIds = [];

    public bool $showBulkDateModal = false;

    public string $bulkDate = '';

    public function mount(Panel $panel): void
    {
        $this->panel = $panel;
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'student_number' => 'nullable|string|max:50',
            'observer1' => 'nullable|string|max:255',
            'observer2' => 'nullable|string|max:255',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'student_number', 'observer1', 'observer2']);
        $this->showModal = true;
    }

    public function openEdit(Participant $participant): void
    {
        abort_unless($participant->panel_id === $this->panel->id && $participant->role === 'presenter', 404);

        $observers = $this->panel->observersFor($participant);

        $this->editingId = $participant->id;
        $this->name = $participant->name;
        $this->student_number = $participant->student_number ?? '';
        $this->observer1 = $observers->get(0)?->name ?? '';
        $this->observer2 = $observers->get(1)?->name ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $presenter = $this->panel->participants()
                ->where('role', 'presenter')
                ->findOrFail($this->editingId);

            $presenter->update([
                'name' => $this->name,
                'student_number' => $this->student_number ?: null,
            ]);

            $this->syncObservers($presenter);
        } else {
            $groupOrder = $this->panel->nextGroupOrder();
            $queueOrder = $this->panel->nextQueueOrder();

            $presenter = $this->panel->participants()->create([
                'name' => $this->name,
                'student_number' => $this->student_number ?: null,
                'role' => 'presenter',
                'group_order' => $groupOrder,
                'queue_order' => $queueOrder,
                'status' => 'waiting',
            ]);

            $this->createObserver($presenter, $this->observer1, $queueOrder + 1);
            $this->createObserver($presenter, $this->observer2, $queueOrder + 2);
        }

        $isEdit = (bool) $this->editingId;
        $this->showModal = false;
        $this->reset(['editingId', 'name', 'student_number', 'observer1', 'observer2']);
        $this->dispatch('swal-toast', title: $isEdit ? 'Peserta berhasil diperbarui.' : 'Peserta berhasil ditambahkan.', icon: 'success');
    }

    public function delete(Participant $participant): void
    {
        abort_unless($participant->panel_id === $this->panel->id && $participant->role === 'presenter', 404);

        $this->deletePresenterGroup($participant);
        $this->panel->normalizePresenterGroupOrder();
        $this->dispatch('swal-toast', title: 'Kelompok giliran berhasil dihapus.', icon: 'success');
    }

    public function bulkDelete(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        $presenters = Participant::whereIn('id', $this->selectedIds)
            ->where('panel_id', $this->panel->id)
            ->where('role', 'presenter')
            ->get();

        foreach ($presenters as $presenter) {
            $this->deletePresenterGroup($presenter);
        }

        $this->panel->normalizePresenterGroupOrder();
        $count = $presenters->count();
        $this->selectedIds = [];
        $this->dispatch('swal-toast', title: $count.' kelompok giliran berhasil dihapus.', icon: 'success');
    }

    public function openBulkDateModal(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }
        $this->bulkDate = '';
        $this->showBulkDateModal = true;
    }

    public function saveBulkDate(): void
    {
        $this->validate(['bulkDate' => 'required|date']);

        $presenters = Participant::whereIn('id', $this->selectedIds)
            ->where('panel_id', $this->panel->id)
            ->where('role', 'presenter')
            ->get();

        foreach ($presenters as $presenter) {
            $ids = $this->groupMemberIds($presenter);
            Participant::whereIn('id', $ids)->update(['exam_date' => $this->bulkDate]);
        }

        $count = $presenters->count();
        $this->showBulkDateModal = false;
        $this->selectedIds = [];
        $this->bulkDate = '';
        $this->dispatch('swal-toast', title: 'Tanggal ujian '.$count.' kelompok diperbarui.', icon: 'success');
    }

    public function shuffle(): void
    {
        $presenters = $this->panel->participants()
            ->where('role', 'presenter')
            ->whereIn('status', ['waiting', 'skipped'])
            ->get()
            ->shuffle()
            ->pluck('id')
            ->all();

        $this->panel->reorderPresenterGroups($presenters);

        $this->dispatch('swal-toast', title: 'Urutan berhasil diacak.', icon: 'success');
    }

    public function resetQueue(): void
    {
        $this->panel->participants()->update([
            'status' => 'waiting',
            'presented_at' => null,
            'done_at' => null,
        ]);

        $this->panel->resetPresenterGroupOrder();
        $this->dispatch('swal-toast', title: 'Antrian berhasil direset.', icon: 'success');
    }

    private function syncObservers(Participant $presenter): void
    {
        $existing = $this->panel->observersFor($presenter)->values();

        foreach ([$this->observer1, $this->observer2] as $index => $name) {
            $observer = $existing->get($index);
            $name = trim($name);

            if ($observer && $name === '') {
                $observer->delete();

                continue;
            }

            if ($observer) {
                $observer->update(['name' => $name]);

                continue;
            }

            $this->createObserver($presenter, $name, $presenter->queue_order + $index + 1);
        }
    }

    private function createObserver(Participant $presenter, string $name, int $queueOrder): void
    {
        $name = trim($name);

        if ($name === '') {
            return;
        }

        $this->panel->participants()->create([
            'name' => $name,
            'role' => 'observer',
            'group_order' => $presenter->group_order,
            'queue_order' => $queueOrder,
            'exam_date' => $presenter->exam_date,
            'status' => $presenter->status === 'presenting' ? 'observing' : $presenter->status,
        ]);
    }

    private function deletePresenterGroup(Participant $presenter): void
    {
        Participant::whereIn('id', $this->groupMemberIds($presenter))->delete();
    }

    private function groupMemberIds(Participant $presenter): array
    {
        if ($presenter->group_order === null) {
            return [$presenter->id];
        }

        return $this->panel->participants()
            ->where('group_order', $presenter->group_order)
            ->pluck('id')
            ->all();
    }

    // ─── Import Excel ─────────────────────────────────────────────────

    public function previewImport(): void
    {
        $this->validate(['importFile' => 'required|file|mimes:xlsx,xls|max:4096']);

        $rows = $this->parseExcel($this->importFile->getRealPath());

        $this->importPreview = collect($rows)
            ->sortBy(fn ($row, $index) => $row['order'] ?? $index + 1)
            ->values()
            ->all();
        $this->showPreview = true;
    }

    public function confirmImport(): void
    {
        $baseQueueOrder = ($this->panel->participants()->max('queue_order') ?? 0) + 1;
        $baseGroupOrder = ($this->panel->participants()->max('group_order') ?? 0) + 1;

        foreach ($this->importPreview as $i => $row) {
            $presenterName = trim($row['presenter'] ?? '');
            if (empty($presenterName)) {
                continue;
            }

            $groupOrder = $baseGroupOrder + $i;
            $queueOffset = $baseQueueOrder + ($i * 3);
            $examDate = ! empty($row['exam_date']) ? $row['exam_date'] : null;

            // Presenter
            $this->panel->participants()->create([
                'name' => $presenterName,
                'role' => 'presenter',
                'group_order' => $groupOrder,
                'queue_order' => $queueOffset,
                'exam_date' => $examDate,
                'status' => 'waiting',
            ]);

            // Observer 1
            $obs1 = trim($row['observer1'] ?? '');
            if (! empty($obs1)) {
                $this->panel->participants()->create([
                    'name' => $obs1,
                    'role' => 'observer',
                    'group_order' => $groupOrder,
                    'queue_order' => $queueOffset + 1,
                    'exam_date' => $examDate,
                    'status' => 'waiting',
                ]);
            }

            // Observer 2
            $obs2 = trim($row['observer2'] ?? '');
            if (! empty($obs2)) {
                $this->panel->participants()->create([
                    'name' => $obs2,
                    'role' => 'observer',
                    'group_order' => $groupOrder,
                    'queue_order' => $queueOffset + 2,
                    'exam_date' => $examDate,
                    'status' => 'waiting',
                ]);
            }
        }

        $this->importPreview = [];
        $this->showPreview = false;
        $this->importFile = null;
        $this->dispatch('swal-toast', title: 'Import peserta berhasil.', icon: 'success');
    }

    public function cancelImport(): void
    {
        $this->importPreview = [];
        $this->showPreview = false;
        $this->importFile = null;
    }

    public function downloadTemplate()
    {
        return Excel::download(
            new class implements FromArray, WithHeadings
            {
                public function headings(): array
                {
                    return ['Nama Presenter', 'Urutan', 'Observer 1', 'Observer 2', 'Tanggal Ujian'];
                }

                public function array(): array
                {
                    return [
                        ['Ahmad Fauzi',   1, 'Budi Santoso',  'Citra Dewi',    '2025-09-15'],
                        ['Dodi Pratama',  2, 'Eka Putri',     'Fitri Handayani', '2025-09-15'],
                        ['Gilang Ramadan', 3, 'Hana Wijaya',   'Irfan Maulana', '2025-09-22'],
                    ];
                }
            },
            'template_peserta.xlsx'
        );
    }

    private function parseExcel(string $path): array
    {
        $reader = Excel::toArray(new class implements ToArray
        {
            public function array(array $array): array
            {
                return $array;
            }
        }, $path);

        $rows = $reader[0] ?? [];
        if (empty($rows)) {
            return [];
        }
        $header = array_map('strtolower', array_map('trim', array_map('strval', array_shift($rows))));

        // Cari index kolom (fallback ke posisi ordinal jika header tidak cocok)
        $presenterCol = array_search('nama presenter', $header)
            ?? array_search('presenter', $header)
            ?? array_search('nama lengkap', $header)
            ?? 0;
        $orderCol = array_search('urutan', $header)
            ?? array_search('urutan presentasi', $header)
            ?? 1;
        $obs1Col = array_search('observer 1', $header)
            ?? array_search('observer1', $header)
            ?? 2;
        $obs2Col = array_search('observer 2', $header)
            ?? array_search('observer2', $header)
            ?? 3;
        $dateCol = array_search('tanggal ujian', $header)
            ?? array_search('tanggal', $header)
            ?? array_search('exam_date', $header)
            ?? 4;

        $result = [];
        foreach ($rows as $row) {
            $presenter = trim((string) ($row[$presenterCol] ?? ''));
            if (empty($presenter)) {
                continue;
            }

            $rawDate = trim((string) ($row[$dateCol] ?? ''));
            $examDate = null;
            if ($rawDate) {
                try {
                    if (is_numeric($rawDate)) {
                        // Excel menyimpan tanggal sebagai serial number integer
                        $dt = Date::excelToDateTimeObject((float) $rawDate);
                        $examDate = Carbon::instance($dt)->format('Y-m-d');
                    } else {
                        $examDate = Carbon::parse($rawDate)->format('Y-m-d');
                    }
                } catch (\Throwable) {
                    $examDate = null;
                }
            }

            $result[] = [
                'presenter' => $presenter,
                'order' => is_numeric($row[$orderCol] ?? '') ? (int) $row[$orderCol] : null,
                'observer1' => trim((string) ($row[$obs1Col] ?? '')),
                'observer2' => trim((string) ($row[$obs2Col] ?? '')),
                'exam_date' => $examDate,
            ];
        }

        return $result;
    }

    public function render()
    {
        return view('livewire.admin.participants', [
            'participants' => $this->panel->participants()
                ->where('role', 'presenter')
                ->orderBy('queue_order')
                ->get(),
        ])->layout('layouts.app', ['title' => 'Peserta — '.$this->panel->name]);
    }
}
