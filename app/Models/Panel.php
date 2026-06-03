<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Panel extends Model
{
    protected $fillable = ['name', 'grade', 'major', 'status', 'operator_pin', 'location'];

    protected $hidden = ['operator_pin'];

    // Peserta dengan status aktif di atas (presenting, observing, waiting, skipped)
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function queueLogs(): HasMany
    {
        return $this->hasMany(QueueLog::class);
    }

    public function observersFor(Participant $presenter): Collection
    {
        if ($presenter->group_order === null) {
            return collect();
        }

        return $this->participants()
            ->where('group_order', $presenter->group_order)
            ->where('role', 'observer')
            ->orderBy('queue_order')
            ->get();
    }

    public function queuePresenters(array $statuses = ['waiting', 'skipped']): Collection
    {
        return $this->participants()
            ->where('role', 'presenter')
            ->whereIn('status', $statuses)
            ->orderBy('queue_order')
            ->get();
    }

    public function activePresenterCount(): int
    {
        return $this->participants()->where('role', 'presenter')->count();
    }

    public function donePresenterCount(): int
    {
        return $this->participants()
            ->where('role', 'presenter')
            ->where('status', 'done')
            ->count();
    }

    public function waitingPresenterCount(): int
    {
        return $this->participants()
            ->where('role', 'presenter')
            ->whereIn('status', ['waiting', 'skipped'])
            ->count();
    }

    /**
     * Ambil slot antrian saat ini: presenter + observer1 + observer2
     * Berdasarkan queue_order asc dari yang masih waiting/skipped.
     */
    public function currentSlot(): array
    {
        $active = $this->participants()
            ->whereIn('status', ['presenting', 'observing'])
            ->orderBy('queue_order')
            ->get();

        $presenter = $active->firstWhere('status', 'presenting');
        $observers = $active->where('status', 'observing')->values();

        return [
            'presenter' => $presenter,
            'observer1' => $observers->get(0),
            'observer2' => $observers->get(1),
        ];
    }

    /**
     * Giliran berikutnya: presenter-presenter berikutnya dari waiting/skipped
     */
    public function nextSlot(): Collection
    {
        return $this->participants()
            ->whereIn('status', ['waiting', 'skipped'])
            ->where('role', 'presenter')
            ->orderBy('queue_order')
            ->take(3)
            ->get();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function nextQueueOrder(): int
    {
        return ($this->participants()->max('queue_order') ?? 0) + 1;
    }

    public function nextGroupOrder(): int
    {
        return ($this->participants()->max('group_order') ?? 0) + 1;
    }

    /**
     * Mulai / geser giliran berikutnya.
     * Jika peserta memiliki group_order (import berpasangan),
     * ambil presenter + observer dari group yang sama.
     * Jika tidak, ambil 3 peserta berikutnya secara urut (mode legacy).
     */
    public function advanceQueue(): ?QueueLog
    {
        return DB::transaction(function () {
            // Cari presenter berikutnya
            $presenter = $this->participants()
                ->whereIn('status', ['waiting', 'skipped'])
                ->where('role', 'presenter')
                ->orderBy('queue_order')
                ->lockForUpdate()
                ->first();

            if (! $presenter) {
                return null;
            }

            // Cari observer dari group yang sama (jika ada group_order)
            if ($presenter->group_order !== null) {
                $observers = $this->participants()
                    ->where('group_order', $presenter->group_order)
                    ->where('role', 'observer')
                    ->orderBy('queue_order')
                    ->get();
                $obs1 = $observers->get(0);
                $obs2 = $observers->get(1);
            } else {
                // Mode legacy: ambil 2 peserta berikutnya di queue
                $next = $this->participants()
                    ->whereIn('status', ['waiting', 'skipped'])
                    ->where('id', '!=', $presenter->id)
                    ->orderBy('queue_order')
                    ->take(2)
                    ->lockForUpdate()
                    ->get();
                $obs1 = $next->get(0);
                $obs2 = $next->get(1);
            }

            $presenter->update(['status' => 'presenting', 'presented_at' => now()]);
            $obs1?->update(['status' => 'observing']);
            $obs2?->update(['status' => 'observing']);

            return QueueLog::create([
                'panel_id'     => $this->id,
                'presenter_id' => $presenter->id,
                'observer1_id' => $obs1?->id,
                'observer2_id' => $obs2?->id,
                'action'       => 'started',
                'started_at'   => now(),
            ]);
        });
    }

    /**
     * Selesaikan presenter saat ini.
     * Jika paired group: semua anggota group ditandai done.
     * Jika legacy: observer dikembalikan ke waiting.
     */
    public function completePresenter(): ?QueueLog
    {
        $slot = $this->currentSlot();
        $presenter = $slot['presenter'];

        if (! $presenter) {
            return null;
        }

        $presenter->update(['status' => 'done', 'done_at' => now()]);

        $log = $this->queueLogs()
            ->where('presenter_id', $presenter->id)
            ->where('action', 'started')
            ->latest()
            ->first();

        $log?->update(['action' => 'done', 'ended_at' => now()]);

        if ($presenter->group_order !== null) {
            // Row observer adalah bagian dari kelompok panggilan ini, bukan unit antrean terpisah.
            $this->participants()
                ->where('group_order', $presenter->group_order)
                ->where('role', 'observer')
                ->update(['status' => 'done', 'done_at' => now()]);
        } else {
            // Legacy: observer kembali ke waiting
            $this->participants()
                ->where('status', 'observing')
                ->update(['status' => 'waiting']);
        }

        return $log;
    }

    public function recallSkippedPresenter(int $presenterId): ?QueueLog
    {
        return DB::transaction(function () use ($presenterId) {
            if ($this->currentSlot()['presenter']) {
                return null;
            }

            $presenter = $this->participants()
                ->where('role', 'presenter')
                ->where('status', 'skipped')
                ->find($presenterId);

            if (! $presenter) {
                return null;
            }

            $minOrder = ($this->participants()->min('queue_order') ?? 1) - 3;
            $this->movePresenterGroup($presenter, $minOrder);

            return $this->advanceQueue();
        });
    }

    /**
     * Lewati presenter saat ini → taruh di akhir antrian.
     * Jika paired group: seluruh group dilewati bersama.
     */
    public function skipPresenter(): ?QueueLog
    {
        $slot = $this->currentSlot();
        $presenter = $slot['presenter'];

        if (! $presenter) {
            return null;
        }

        $maxOrder = $this->participants()->max('queue_order');
        $startedAt = $presenter->presented_at;

        if ($presenter->group_order !== null) {
            // Paired: skip seluruh group ke akhir antrian
            $groupMembers = $this->participants()
                ->where('group_order', $presenter->group_order)
                ->orderBy('queue_order')
                ->get();

            foreach ($groupMembers as $i => $member) {
                $member->update([
                    'status' => 'skipped',
                    'queue_order' => $maxOrder + $i + 1,
                    'presented_at' => null,
                    'done_at' => null,
                ]);
            }
        } else {
            $presenter->update([
                'status' => 'skipped',
                'queue_order' => $maxOrder + 1,
                'presented_at' => null,
                'done_at' => null,
            ]);
            $this->participants()
                ->where('status', 'observing')
                ->update(['status' => 'waiting']);
        }

        $log = QueueLog::create([
            'panel_id' => $this->id,
            'presenter_id' => $presenter->id,
            'observer1_id' => $slot['observer1']?->id,
            'observer2_id' => $slot['observer2']?->id,
            'action' => 'skipped',
            'started_at' => $startedAt,
            'ended_at' => now(),
        ]);

        return $log;
    }

    public function reorderPresenterGroups(array $presenterIds): void
    {
        $presenters = $this->participants()
            ->where('role', 'presenter')
            ->whereIn('status', ['waiting', 'skipped'])
            ->whereIn('id', $presenterIds)
            ->get()
            ->keyBy('id');

        $order = $presenters->min('queue_order') ?? 1;
        foreach ($presenterIds as $presenterId) {
            $presenter = $presenters->get((int) $presenterId);
            if (! $presenter) {
                continue;
            }

            $this->movePresenterGroup($presenter, $order);
            $order += max(1, $this->groupMembers($presenter)->count());
        }
    }

    public function normalizePresenterGroupOrder(): void
    {
        $order = 1;

        $this->participants()
            ->where('role', 'presenter')
            ->orderBy('queue_order')
            ->get()
            ->each(function (Participant $presenter) use (&$order) {
                $this->movePresenterGroup($presenter, $order);
                $order += max(1, $this->groupMembers($presenter)->count());
            });
    }

    public function resetPresenterGroupOrder(): void
    {
        $order = 1;

        $this->participants()
            ->where('role', 'presenter')
            ->orderByRaw('group_order is null')
            ->orderBy('group_order')
            ->orderBy('id')
            ->get()
            ->each(function (Participant $presenter) use (&$order) {
                $this->movePresenterGroup($presenter, $order);
                $order += max(1, $this->groupMembers($presenter)->count());
            });
    }

    private function groupMembers(Participant $presenter): Collection
    {
        if ($presenter->group_order === null) {
            return collect([$presenter]);
        }

        return $this->participants()
            ->where('group_order', $presenter->group_order)
            ->orderByRaw("case when role = 'presenter' then 0 else 1 end")
            ->orderBy('queue_order')
            ->get();
    }

    private function movePresenterGroup(Participant $presenter, int $startOrder): void
    {
        foreach ($this->groupMembers($presenter)->values() as $offset => $member) {
            $member->update(['queue_order' => $startOrder + $offset]);
        }
    }

    public function verifyPin(string $pin): bool
    {
        return $this->operator_pin && $this->operator_pin === $pin;
    }
}
