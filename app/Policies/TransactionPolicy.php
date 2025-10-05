<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Transaction;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $transaction->sender_id === $user->id ||
               $transaction->receiver_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function transfer(User $user, int $senderId): bool
    {
        return $user->id === $senderId;
    }
}

