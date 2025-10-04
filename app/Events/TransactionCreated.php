<?php

namespace App\Events;

use App\Models\User;
use App\Models\Transaction;
use App\Http\Resources\UserResource;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class TransactionCreated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public User $user
    ) {
        $this->transaction->load('sender', 'receiver');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('transactions.' . $this->user->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'transaction' => [
                'id' => $this->transaction->id,
                'receiver_id' => $this->transaction->receiver_id,
                'sender_id' => $this->transaction->sender_id,
                'sender' => new UserResource($this->transaction->sender),
                'receiver' => new UserResource($this->transaction->receiver),
                'amount' => $this->transaction->amount,
                'commission_fee' => $this->transaction->commission_fee,
                'type' => $this->user->id === $this->transaction->sender_id ? 'Debit' : 'Credit',
                'created_at' => $this->transaction->created_at->toISOString(),
            ],
            'user' => $this->user->fresh()->only('id', 'name', 'email', 'balance'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'transaction.created';
    }
}

