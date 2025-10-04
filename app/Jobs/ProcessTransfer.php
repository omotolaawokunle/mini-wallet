<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Transaction;
use App\Events\TransferFailed;
use App\Events\TransactionCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\TransferException;

class ProcessTransfer implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 60;
    public $maxExceptions = 3;
    public $backoff = 1000;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $senderId, public int $receiverId, public float $amount, public float $commissionFee)
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $sender = User::lockForUpdate()->find($this->senderId);
            $receiver = User::lockForUpdate()->find($this->receiverId);
            if (!$sender || !$receiver) {
                throw new TransferException('Sender or receiver not found');
            }
            if($sender->id === $receiver->id) {
                throw new TransferException('Sender and receiver cannot be the same');
            }
            if ($sender->balance < $this->amount + $this->commissionFee) {
                throw new InsufficientBalanceException('Sender does not have enough balance');
            }
            $sender->decrement('balance', $this->amount + $this->commissionFee);
            $receiver->increment('balance', $this->amount);
            $transaction = Transaction::create([
                'sender_id' => $this->senderId,
                'receiver_id' => $this->receiverId,
                'amount' => $this->amount,
                'commission_fee' => $this->commissionFee,
            ]);
            event(new TransactionCreated($transaction, $sender));
            event(new TransactionCreated($transaction, $receiver));
        });
    }

    public function failed(\Throwable $throwable)
    {
        Log::error('ProcessTransfer failed: ' . $throwable->getMessage());

        $message = $throwable instanceof TransferException || $throwable instanceof InsufficientBalanceException ? $throwable->getMessage() : 'Server error';
        event(new TransferFailed($this->senderId, $this->receiverId, $this->amount, $this->commissionFee, $message));

    }
}
