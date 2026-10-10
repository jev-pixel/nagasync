<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class EmergencyAlertChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public int $alertId, public int $userId, public string $status) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('dispatch'), new PrivateChannel('citizen.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'alert.changed';
    }

    // No personal data goes over the socket: clients reload through the authenticated app
    public function broadcastWith(): array
    {
        return ['id' => $this->alertId, 'status' => $this->status];
    }
}