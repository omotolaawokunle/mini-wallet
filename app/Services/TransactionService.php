<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Events\TransactionCreated;
use Illuminate\Support\Facades\DB;
use App\Exceptions\InsufficientBalanceException;

class TransactionService
{
    /**
     * Transfer money between two users
     * @param array $data {sender_id: int, receiver_id: int, amount: float, commission_fee: float}
     * @return Transaction
     * @throws \Exception
     * @throws InsufficientBalanceException
     */
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
            $transaction = Transaction::create([
                'sender_id' => $data['sender_id'],
                'receiver_id' => $data['receiver_id'],
                'amount' => $data['amount'],
                'commission_fee' => $data['commission_fee'],
            ]);
            event(new TransactionCreated($transaction, $sender));
            event(new TransactionCreated($transaction, $receiver));
            return $transaction;
        });
    }
}
