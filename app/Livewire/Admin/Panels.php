<?php

namespace App\Livewire\Admin;

use App\Models\Panel;
use Livewire\Component;

class Panels extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $grade = '10';

    public string $major = 'RPL';

    public string $status = 'inactive';

    public string $operator_pin = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'grade' => 'required|in:10,11,12',
            'major' => 'required|in:RPL,DKV,TKJ',
            'status' => 'required|in:inactive,active,closed',
            'operator_pin' => 'nullable|string|min:4|max:6',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'grade', 'major', 'status', 'operator_pin']);
        $this->grade = '10';
        $this->major = 'RPL';
        $this->status = 'inactive';
        $this->showModal = true;
    }

    public function openEdit(Panel $panel): void
    {
        $this->editingId = $panel->id;
        $this->name = $panel->name;
        $this->grade = $panel->grade;
        $this->major = $panel->major;
        $this->status = $panel->status;
        $this->operator_pin = $panel->operator_pin ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if (empty($data['operator_pin'])) {
            unset($data['operator_pin']);
        }

        if ($this->editingId) {
            Panel::findOrFail($this->editingId)->update($data);
        } else {
            Panel::create($data);
        }

        $isEdit = (bool) $this->editingId;
        $this->showModal = false;
        $this->reset(['editingId', 'name', 'grade', 'major', 'status', 'operator_pin']);
        $this->dispatch('swal-toast', title: $isEdit ? 'Panel berhasil diperbarui.' : 'Panel berhasil ditambahkan.', icon: 'success');
    }

    public function toggleStatus(Panel $panel): void
    {
        $next = match ($panel->status) {
            'inactive' => 'active',
            'active' => 'closed',
            'closed' => 'inactive',
            default => 'inactive',
        };

        $panel->update(['status' => $next]);
    }

    public function delete(Panel $panel): void
    {
        $panel->delete();
        $this->dispatch('swal-toast', title: 'Panel berhasil dihapus.', icon: 'success');
    }

    public function render()
    {
        return view('livewire.admin.panels', [
            'panels' => Panel::withCount([
                'participants' => fn ($q) => $q->where('role', 'presenter'),
            ])->orderBy('grade')->orderBy('major')->get(),
        ])->layout('layouts.app', ['title' => 'Kelola Panel']);
    }
}
