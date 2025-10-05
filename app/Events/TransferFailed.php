<?php

namespace App\Events;

use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TransferFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public int $senderId, public int $receiverId, public float $amount, public float $commissionFee, public string $message)
    {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->senderId),
        ];
    }

    public function broadcastWith(): array
    {
        $receiver = User::find($this->receiverId);
        return [
            'sender_id' => $this->senderId,
            'receiver_id' => $this->receiverId,
            'receiver' => new UserResource($receiver),
            'amount' => $this->amount,
            'commission_fee' => $this->commissionFee,
            'message' => $this->message,
        ];
    }

    public function broadcastAs(): string
    {
        return 'transfer.failed';
    }
}
