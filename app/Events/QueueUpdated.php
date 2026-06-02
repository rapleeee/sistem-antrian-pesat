<?php

namespace App\Events;

use App\Models\Panel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $panelId;
    public array $slot;
    public ?string $ttsMessage;

    public function __construct(Panel $panel, ?string $ttsMessage = null)
    {
        $this->panelId = $panel->id;
        $this->ttsMessage = $ttsMessage;

        $slot = $panel->currentSlot();

        $this->slot = [
            'presenter'  => $slot['presenter'] ? [
                'id'   => $slot['presenter']->id,
                'name' => $slot['presenter']->name,
            ] : null,
            'observer1' => $slot['observer1'] ? [
                'id'   => $slot['observer1']->id,
                'name' => $slot['observer1']->name,
            ] : null,
            'observer2' => $slot['observer2'] ? [
                'id'   => $slot['observer2']->id,
                'name' => $slot['observer2']->name,
            ] : null,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('panel.' . $this->panelId),
            new Channel('display.all'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'queue.updated';
    }
}
