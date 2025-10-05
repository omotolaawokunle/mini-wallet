<?php

namespace App\Events;

use App\Http\Resources\TransactionResource;
use App\Models\User;
use App\Models\Transaction;
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
            new PrivateChannel('App.Models.User.' . $this->user->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'transaction' => new TransactionResource($this->transaction),
            'user' => $this->user->fresh()->only('id', 'name', 'email', 'balance'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'transaction.created';
    }
}

