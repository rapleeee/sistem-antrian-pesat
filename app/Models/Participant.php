<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    protected $fillable = [
        'panel_id',
        'name',
        'student_number',
        'exam_date',
        'role',
        'group_order',
        'queue_order',
        'status',
        'presented_at',
        'done_at',
    ];

    protected $casts = [
        'presented_at' => 'datetime',
        'done_at'      => 'datetime',
        'exam_date'    => 'date',
        'queue_order'  => 'integer',
        'group_order'  => 'integer',
    ];

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }

    public function getDurationMinutesAttribute(): ?int
    {
        if ($this->presented_at && $this->done_at) {
            return (int) $this->presented_at->diffInMinutes($this->done_at);
        }

        return null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'waiting'    => 'Menunggu',
            'presenting' => 'Presentasi',
            'observing'  => 'Observer',
            'done'       => 'Selesai',
            'skipped'    => 'Dilewati',
            default      => $this->status,
        };
    }

    public function statusIcon(): string
    {
        return match ($this->status) {
            'waiting'    => 'clock',
            'presenting' => 'microphone',
            'observing'  => 'eye',
            'done'       => 'check-circle',
            'skipped'    => 'forward',
            default      => '',
        };
    }
}
