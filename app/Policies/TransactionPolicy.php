<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Auth\Access\Response;

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

    public function create(User $user): Response
    {
        return !$user->is_flagged ? Response::allow() : Response::deny('Your account has been flagged. Please contact support.');
    }

    public function transfer(User $user, int $senderId): Response
    {
        if ($user->id !== $senderId) {
            return Response::deny('You are not the sender of this transaction');
        }

        if ($user->is_flagged) {
            return Response::deny('Your account has been flagged. Please contact support.');
        }

        return Response::allow();
    }
}

