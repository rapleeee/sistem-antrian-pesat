<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueLog extends Model
{
    protected $fillable = [
        'panel_id',
        'presenter_id',
        'observer1_id',
        'observer2_id',
        'action',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }

    public function presenter(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'presenter_id');
    }

    public function observer1(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'observer1_id');
    }

    public function observer2(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'observer2_id');
    }

    public function getDurationMinutesAttribute(): ?int
    {
        if ($this->started_at && $this->ended_at) {
            return (int) $this->started_at->diffInMinutes($this->ended_at);
        }

        return null;
    }
}
