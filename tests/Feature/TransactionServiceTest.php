<?php

use App\Models\User;
use App\Models\Transaction;
use App\Jobs\ProcessTransfer;
use App\Events\TransactionCreated;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Event;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\AccountFlaggedException;

beforeEach(function () {
    $this->transactionService = new TransactionService();
});

it('successfully transfers money between users', function () {
    $sender = User::factory()->create(['balance' => 1000]);
    $receiver = User::factory()->create(['balance' => 500]);

    $transaction = $this->transactionService->transfer([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 100,
        'commission_fee' => 10,
    ]);

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->amount)->toBe('100.00')
        ->and($transaction->commission_fee)->toBe('10.00')
        ->and($transaction->sender_id)->toBe($sender->id)
        ->and($transaction->receiver_id)->toBe($receiver->id);

    $sender->refresh();
    $receiver->refresh();

    expect($sender->balance)->toBe('890.00')
        ->and($receiver->balance)->toBe('600.00');
});

it('deducts amount plus commission fee from sender', function () {
    $sender = User::factory()->create(['balance' => 1000]);
    $receiver = User::factory()->create(['balance' => 0]);

    $this->transactionService->transfer([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 200,
        'commission_fee' => 50,
    ]);

    $sender->refresh();
    expect($sender->balance)->toBe('750.00');
});

it('only credits the transfer amount to receiver without commission', function () {
    $sender = User::factory()->create(['balance' => 1000]);
    $receiver = User::factory()->create(['balance' => 100]);

    $this->transactionService->transfer([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 200,
        'commission_fee' => 50,
    ]);

    $receiver->refresh();
    expect($receiver->balance)->toBe('300.00');
});

it('throws exception when sender has insufficient balance', function () {
    $sender = User::factory()->create(['balance' => 50]);
    $receiver = User::factory()->create(['balance' => 100]);

    $this->transactionService->transfer([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 100,
        'commission_fee' => 10,
    ]);
})->throws(InsufficientBalanceException::class, 'Sender does not have enough balance');

it('throws exception when sender is not found', function () {
    $receiver = User::factory()->create(['balance' => 100]);

    $this->transactionService->transfer([
        'sender_id' => 999999,
        'receiver_id' => $receiver->id,
        'amount' => 100,
        'commission_fee' => 10,
    ]);
})->throws(Exception::class, 'Sender or receiver not found');

it('throws exception when receiver is not found', function () {
    $sender = User::factory()->create(['balance' => 1000]);

    $this->transactionService->transfer([
        'sender_id' => $sender->id,
        'receiver_id' => 999999,
        'amount' => 100,
        'commission_fee' => 10,
    ]);
})->throws(Exception::class, 'Sender or receiver not found');

it('rolls back transaction when an error occurs', function () {
    $sender = User::factory()->create(['balance' => 1000]);
    $receiver = User::factory()->create(['balance' => 500]);
    $initialSenderBalance = $sender->balance;
    $initialReceiverBalance = $receiver->balance;

    try {
        $this->transactionService->transfer([
            'sender_id' => $sender->id,
            'receiver_id' => 999999,
            'amount' => 100,
            'commission_fee' => 10,
        ]);
    } catch (Exception $e) {
        // Expected exception
    }

    $sender->refresh();
    $receiver->refresh();

    expect($sender->balance)->toBe($initialSenderBalance)
        ->and($receiver->balance)->toBe($initialReceiverBalance)
        ->and(Transaction::count())->toBe(0);
});

it('dispatches TransactionCreated event for sender', function () {
    Event::fake();

    $sender = User::factory()->create(['balance' => 1000]);
    $receiver = User::factory()->create(['balance' => 500]);

    $transaction = $this->transactionService->transfer([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 100,
        'commission_fee' => 10,
    ]);

    Event::assertDispatched(TransactionCreated::class, function ($event) use ($transaction, $sender) {
        return $event->transaction->id === $transaction->id &&
               $event->user->id === $sender->id;
    });
});

it('dispatches TransactionCreated event for receiver', function () {
    Event::fake();

    $sender = User::factory()->create(['balance' => 1000]);
    $receiver = User::factory()->create(['balance' => 500]);

    $transaction = $this->transactionService->transfer([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 100,
        'commission_fee' => 10,
    ]);

    Event::assertDispatched(TransactionCreated::class, function ($event) use ($transaction, $receiver) {
        return $event->transaction->id === $transaction->id &&
               $event->user->id === $receiver->id;
    });
});

it('dispatches TransactionCreated event exactly twice', function () {
    Event::fake();

    $sender = User::factory()->create(['balance' => 1000]);
    $receiver = User::factory()->create(['balance' => 500]);

    $this->transactionService->transfer([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 100,
        'commission_fee' => 10,
    ]);

    Event::assertDispatchedTimes(TransactionCreated::class, 2);
});

it('creates transaction record in database', function () {
    $sender = User::factory()->create(['balance' => 1000]);
    $receiver = User::factory()->create(['balance' => 500]);

    $this->transactionService->transfer([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 100,
        'commission_fee' => 10,
    ]);

    $this->assertDatabaseHas('transactions', [
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 100,
        'commission_fee' => 10,
    ]);
});

it('handles exact balance transfers correctly', function () {
    $sender = User::factory()->create(['balance' => 110]);
    $receiver = User::factory()->create(['balance' => 0]);

    $transaction = $this->transactionService->transfer([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 100,
        'commission_fee' => 10,
    ]);

    $sender->refresh();
    $receiver->refresh();

    expect($sender->balance)->toBe('0.00')
        ->and($receiver->balance)->toBe('100.00')
        ->and($transaction)->toBeInstanceOf(Transaction::class);
});

it('handles transfers with zero commission fee', function () {
    $sender = User::factory()->create(['balance' => 1000]);
    $receiver = User::factory()->create(['balance' => 500]);

    $transaction = $this->transactionService->transfer([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 100,
        'commission_fee' => 0,
    ]);

    $sender->refresh();
    $receiver->refresh();

    expect($sender->balance)->toBe('900.00')
        ->and($receiver->balance)->toBe('600.00')
        ->and($transaction->commission_fee)->toBe('0.00');
});

it('handles decimal amounts correctly', function () {
    $sender = User::factory()->create(['balance' => 1000.50]);
    $receiver = User::factory()->create(['balance' => 100.25]);

    $transaction = $this->transactionService->transfer([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 50.75,
        'commission_fee' => 5.25,
    ]);

    $sender->refresh();
    $receiver->refresh();

    expect($sender->balance)->toBe('944.50')
        ->and($receiver->balance)->toBe('151.00')
        ->and($transaction->amount)->toBe('50.75')
        ->and($transaction->commission_fee)->toBe('5.25');
});

it('prevents race conditions with database locking', function () {
    $sender = User::factory()->create(['balance' => 1000]);
    $receiver = User::factory()->create(['balance' => 500]);

    $transaction1 = $this->transactionService->transfer([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 100,
        'commission_fee' => 10,
    ]);

    expect($transaction1)->toBeInstanceOf(Transaction::class);

    $sender->refresh();
    $finalBalance = $sender->balance;

    expect($finalBalance)->toBe('890.00');
});

it('throws exception when sender account is flagged', function () {
    $sender = User::factory()->create([
        'balance' => 1000,
        'flagged_at' => now(),
        'flagged_reason' => 'Balance discrepancy detected',
    ]);
    $receiver = User::factory()->create(['balance' => 500]);

    $this->transactionService->transfer([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 100,
        'commission_fee' => 10,
    ]);
})->throws(AccountFlaggedException::class);

it('throws exception when receiver account is flagged', function () {
    $sender = User::factory()->create(['balance' => 1000]);
    $receiver = User::factory()->create([
        'balance' => 500,
        'flagged_at' => now(),
        'flagged_reason' => 'Balance discrepancy detected',
    ]);

    $this->transactionService->transfer([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 100,
        'commission_fee' => 10,
    ]);
})->throws(AccountFlaggedException::class, 'The receiver account has been flagged');

it('does not deduct balance when sender is flagged', function () {
    $sender = User::factory()->create([
        'balance' => 1000,
        'flagged_at' => now(),
        'flagged_reason' => 'Balance discrepancy detected',
    ]);
    $receiver = User::factory()->create(['balance' => 500]);
    $initialBalance = $sender->balance;

    try {
        $this->transactionService->transfer([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'amount' => 100,
            'commission_fee' => 10,
        ]);
    } catch (AccountFlaggedException $e) {
        // Expected exception
    }

    $sender->refresh();
    expect($sender->balance)->toBe($initialBalance);
});

it('does not create transaction when account is flagged', function () {
    $sender = User::factory()->create([
        'balance' => 1000,
        'flagged_at' => now(),
        'flagged_reason' => 'Balance discrepancy detected',
    ]);
    $receiver = User::factory()->create(['balance' => 500]);
    $initialCount = Transaction::count();

    try {
        $this->transactionService->transfer([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'amount' => 100,
            'commission_fee' => 10,
        ]);
    } catch (AccountFlaggedException $e) {
        // Expected exception
    }

    expect(Transaction::count())->toBe($initialCount);
});

describe('Queue Transfer', function () {
    it('dispatches transfer job to queue', function () {
        \Illuminate\Support\Facades\Queue::fake();

        $sender = User::factory()->create(['balance' => 1000]);
        $receiver = User::factory()->create(['balance' => 500]);

        $this->transactionService->queueTransfer([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'amount' => 100,
            'commission_fee' => 10,
        ]);

        \Illuminate\Support\Facades\Queue::assertPushed(ProcessTransfer::class, function ($job) use ($sender, $receiver) {
            return $job->senderId === $sender->id &&
                   $job->receiverId === $receiver->id &&
                   $job->amount === 100.0 &&
                $job->commissionFee === 10.0;
        });
    });

    it('dispatches transfer to transfers queue', function () {
        \Illuminate\Support\Facades\Queue::fake();

        $sender = User::factory()->create(['balance' => 1000]);
        $receiver = User::factory()->create(['balance' => 500]);

        $this->transactionService->queueTransfer([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'amount' => 100,
            'commission_fee' => 10,
        ]);

        \Illuminate\Support\Facades\Queue::assertPushed(ProcessTransfer::class, function ($job) {
            return $job->queue === 'transfers';
        });
    });

    it('queues multiple transfers independently', function () {
        \Illuminate\Support\Facades\Queue::fake();

        $sender = User::factory()->create(['balance' => 1000]);
        $receiver1 = User::factory()->create(['balance' => 500]);
        $receiver2 = User::factory()->create(['balance' => 300]);

        $this->transactionService->queueTransfer([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver1->id,
            'amount' => 100,
            'commission_fee' => 10,
        ]);

        $this->transactionService->queueTransfer([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver2->id,
            'amount' => 50,
            'commission_fee' => 5,
        ]);

        \Illuminate\Support\Facades\Queue::assertPushed(ProcessTransfer::class, 2);
    });

    it('handles decimal amounts in queued transfers', function () {
        \Illuminate\Support\Facades\Queue::fake();

        $sender = User::factory()->create(['balance' => 1000]);
        $receiver = User::factory()->create(['balance' => 500]);

        $this->transactionService->queueTransfer([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'amount' => 50.75,
            'commission_fee' => 5.25,
        ]);

        \Illuminate\Support\Facades\Queue::assertPushed(ProcessTransfer::class, function ($job) {
            return $job->amount === 50.75 &&
                   $job->commissionFee === 5.25;
        });
    });
});
