<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use App\Exceptions\InsufficientBalanceException;

class TransactionService
{
    public function transfer(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $sender = User::lockForUpdate()->find($data['sender_id']);
            $receiver = User::lockForUpdate()->find($data['receiver_id']);
            if (!$sender || !$receiver) {
                throw new \Exception('Sender or receiver not found');
            }
            if ($sender->balance < $data['amount'] + $data['commission_fee']) {
                throw new InsufficientBalanceException('Sender does not have enough balance');
            }
            $sender->decrement('balance', $data['amount'] + $data['commission_fee']);
            $receiver->increment('balance', $data['amount']);

            return Transaction::create([
                'sender_id' => $data['sender_id'],
                'receiver_id' => $data['receiver_id'],
                'amount' => $data['amount'],
                'commission_fee' => $data['commission_fee'],
            ]);
        });
    }
}
