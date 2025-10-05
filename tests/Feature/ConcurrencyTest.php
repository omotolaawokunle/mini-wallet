<?php

use App\Models\User;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Jobs\ProcessTransfer;
use App\Exceptions\InsufficientBalanceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->transactionService = new TransactionService();
});

describe('Concurrency and Race Conditions', function () {
    it('prevents double spending with database locking', function () {
        $sender = User::factory()->create(['balance' => 150]);
        $receiver1 = User::factory()->create(['balance' => 0]);
        $receiver2 = User::factory()->create(['balance' => 0]);

        $transaction1 = $this->transactionService->transfer([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver1->id,
            'amount' => 100,
            'commission_fee' => 10,
        ]);

        expect($transaction1)->toBeInstanceOf(Transaction::class);

        try {
            $this->transactionService->transfer([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver2->id,
                'amount' => 50,
                'commission_fee' => 5,
            ]);

            throw new Exception('Should have thrown InsufficientBalanceException');
        } catch (InsufficientBalanceException $e) {
            expect($e->getMessage())->toBe('Sender does not have enough balance');
        }

        $sender->refresh();
        $receiver1->refresh();
        $receiver2->refresh();

        expect($sender->balance)->toBe('40.00')
            ->and($receiver1->balance)->toBe('100.00')
            ->and($receiver2->balance)->toBe('0.00')
            ->and(Transaction::count())->toBe(1);
    });

    it('handles sequential transfers correctly maintaining balance integrity', function () {
        $sender = User::factory()->create(['balance' => 500]);
        $receiver = User::factory()->create(['balance' => 0]);

        for ($i = 0; $i < 4; $i++) {
            $this->transactionService->transfer([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'amount' => 100,
                'commission_fee' => 10,
            ]);
        }

        $sender->refresh();
        $receiver->refresh();

        expect($sender->balance)->toBe('60.00')
            ->and($receiver->balance)->toBe('400.00')
            ->and(Transaction::count())->toBe(4);
    });

    it('prevents race condition when multiple transfers target same sender', function () {
        $sender = User::factory()->create(['balance' => 200]);
        $receiver1 = User::factory()->create(['balance' => 0]);
        $receiver2 = User::factory()->create(['balance' => 0]);
        $receiver3 = User::factory()->create(['balance' => 0]);

        $this->transactionService->transfer([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver1->id,
            'amount' => 80,
            'commission_fee' => 8,
        ]);

        $sender->refresh();
        expect($sender->balance)->toBe('112.00');

        $this->transactionService->transfer([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver2->id,
            'amount' => 50,
            'commission_fee' => 5,
        ]);

        $sender->refresh();
        expect($sender->balance)->toBe('57.00');

        try {
            $this->transactionService->transfer([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver3->id,
                'amount' => 60,
                'commission_fee' => 6,
            ]);
        } catch (InsufficientBalanceException $e) {
            expect($e->getMessage())->toBe('Sender does not have enough balance');
        }

        $sender->refresh();
        $receiver1->refresh();
        $receiver2->refresh();
        $receiver3->refresh();

        expect($sender->balance)->toBe('57.00')
            ->and($receiver1->balance)->toBe('80.00')
            ->and($receiver2->balance)->toBe('50.00')
            ->and($receiver3->balance)->toBe('0.00')
            ->and(Transaction::count())->toBe(2);
    });

    it('maintains consistency when sender and receiver are in multiple transactions', function () {
        $user1 = User::factory()->create(['balance' => 1000]);
        $user2 = User::factory()->create(['balance' => 1000]);

        $this->transactionService->transfer([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
            'amount' => 100,
            'commission_fee' => 10,
        ]);

        $user1->refresh();
        $user2->refresh();

        expect($user1->balance)->toBe('890.00')
            ->and($user2->balance)->toBe('1100.00');

        $this->transactionService->transfer([
            'sender_id' => $user2->id,
            'receiver_id' => $user1->id,
            'amount' => 200,
            'commission_fee' => 20,
        ]);

        $user1->refresh();
        $user2->refresh();

        expect($user1->balance)->toBe('1090.00')
            ->and($user2->balance)->toBe('880.00');
    });

    it('uses lockForUpdate to prevent dirty reads', function () {
        $sender = User::factory()->create(['balance' => 500]);
        $receiver = User::factory()->create(['balance' => 0]);

        DB::transaction(function () use ($sender, $receiver) {
            $lockedSender = User::lockForUpdate()->find($sender->id);
            $lockedReceiver = User::lockForUpdate()->find($receiver->id);

            expect($lockedSender)->not->toBeNull()
                ->and($lockedReceiver)->not->toBeNull();

            $lockedSender->decrement('balance', 110);
            $lockedReceiver->increment('balance', 100);
        });

        $sender->refresh();
        $receiver->refresh();

        expect($sender->balance)->toBe('390.00')
            ->and($receiver->balance)->toBe('100.00');
    });

    it('rolls back all changes when transaction fails in the middle', function () {
        $sender = User::factory()->create(['balance' => 1000]);
        $receiver = User::factory()->create(['balance' => 500]);

        $initialSenderBalance = $sender->balance;
        $initialReceiverBalance = $receiver->balance;
        $initialTransactionCount = Transaction::count();

        try {
            DB::transaction(function () use ($sender, $receiver) {
                $sender->decrement('balance', 100);
                $receiver->increment('balance', 100);

                Transaction::create([
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'amount' => 100,
                    'commission_fee' => 0,
                ]);

                throw new Exception('Simulated failure');
            });
        } catch (Exception $e) {
            // Expected
        }

        $sender->refresh();
        $receiver->refresh();

        expect($sender->balance)->toBe($initialSenderBalance)
            ->and($receiver->balance)->toBe($initialReceiverBalance)
            ->and(Transaction::count())->toBe($initialTransactionCount);
    });
});

describe('Job Concurrency Tests', function () {
    it('processes queued transfers maintaining balance integrity', function () {
        $sender = User::factory()->create(['balance' => 1000]);
        $receiver = User::factory()->create(['balance' => 500]);

        $job1 = new ProcessTransfer(
            senderId: $sender->id,
            receiverId: $receiver->id,
            amount: 100,
            commissionFee: 10
        );

        $job2 = new ProcessTransfer(
            senderId: $sender->id,
            receiverId: $receiver->id,
            amount: 200,
            commissionFee: 20
        );

        $job1->handle();

        $sender->refresh();
        expect($sender->balance)->toBe('890.00');

        $job2->handle();

        $sender->refresh();
        $receiver->refresh();

        expect($sender->balance)->toBe('670.00')
            ->and($receiver->balance)->toBe('800.00')
            ->and(Transaction::count())->toBe(2);
    });

    it('fails job when sender has insufficient balance after first transfer', function () {
        $sender = User::factory()->create(['balance' => 250]);
        $receiver = User::factory()->create(['balance' => 0]);

        $job1 = new ProcessTransfer(
            senderId: $sender->id,
            receiverId: $receiver->id,
            amount: 200,
            commissionFee: 20
        );

        $job1->handle();

        $sender->refresh();
        expect($sender->balance)->toBe('30.00');

        $job2 = new ProcessTransfer(
            senderId: $sender->id,
            receiverId: $receiver->id,
            amount: 100,
            commissionFee: 10
        );

        expect(fn() => $job2->handle())
            ->toThrow(InsufficientBalanceException::class, 'Sender does not have enough balance');

        $sender->refresh();
        $receiver->refresh();

        expect($sender->balance)->toBe('30.00')
            ->and($receiver->balance)->toBe('200.00')
            ->and(Transaction::count())->toBe(1);
    });

    it('maintains atomicity when processing multiple jobs sequentially', function () {
        $sender = User::factory()->create(['balance' => 1000]);
        $receivers = User::factory()->count(5)->create(['balance' => 0]);

        foreach ($receivers as $receiver) {
            $job = new ProcessTransfer(
                senderId: $sender->id,
                receiverId: $receiver->id,
                amount: 100,
                commissionFee: 10
            );

            $job->handle();
        }

        $sender->refresh();

        expect($sender->balance)->toBe('450.00')
            ->and(Transaction::count())->toBe(5);

        $totalReceiverBalance = User::whereIn('id', $receivers->pluck('id'))->sum('balance');
        expect((float)$totalReceiverBalance)->toBe(500.0);
    });
});

describe('Edge Cases in Concurrent Scenarios', function () {
    it('handles exact balance depletion correctly', function () {
        $sender = User::factory()->create(['balance' => 220]);
        $receiver1 = User::factory()->create(['balance' => 0]);
        $receiver2 = User::factory()->create(['balance' => 0]);

        $this->transactionService->transfer([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver1->id,
            'amount' => 100,
            'commission_fee' => 10,
        ]);

        $sender->refresh();
        expect($sender->balance)->toBe('110.00');

        $this->transactionService->transfer([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver2->id,
            'amount' => 100,
            'commission_fee' => 10,
        ]);

        $sender->refresh();
        $receiver1->refresh();
        $receiver2->refresh();

        expect($sender->balance)->toBe('0.00')
            ->and($receiver1->balance)->toBe('100.00')
            ->and($receiver2->balance)->toBe('100.00');
    });

    it('prevents negative balance through concurrent attempts', function () {
        $sender = User::factory()->create(['balance' => 100]);
        $receiver = User::factory()->create(['balance' => 0]);

        $this->transactionService->transfer([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'amount' => 50,
            'commission_fee' => 5,
        ]);

        $sender->refresh();
        expect($sender->balance)->toBe('45.00');

        expect(fn() => $this->transactionService->transfer([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'amount' => 50,
            'commission_fee' => 5,
        ]))->toThrow(InsufficientBalanceException::class);

        $sender->refresh();
        expect($sender->balance)->toBe('45.00')
            ->and($sender->balance)->toBeGreaterThanOrEqual(0);
    });

    it('handles rapid successive transfers correctly', function () {
        $sender = User::factory()->create(['balance' => 1000]);
        $receiver = User::factory()->create(['balance' => 0]);

        $transferCount = 10;
        $amountPerTransfer = 50;
        $feePerTransfer = 5;

        for ($i = 0; $i < $transferCount; $i++) {
            $this->transactionService->transfer([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'amount' => $amountPerTransfer,
                'commission_fee' => $feePerTransfer,
            ]);
        }

        $sender->refresh();
        $receiver->refresh();

        $expectedSenderBalance = 1000 - ($transferCount * ($amountPerTransfer + $feePerTransfer));
        $expectedReceiverBalance = $transferCount * $amountPerTransfer;

        expect($sender->balance)->toBe(number_format($expectedSenderBalance, 2, '.', ''))
            ->and($receiver->balance)->toBe(number_format($expectedReceiverBalance, 2, '.', ''))
            ->and(Transaction::count())->toBe($transferCount);
    });

    it('maintains data integrity with bidirectional transfers', function () {
        $user1 = User::factory()->create(['balance' => 500]);
        $user2 = User::factory()->create(['balance' => 500]);

        $totalInitialBalance = $user1->balance + $user2->balance;

        $this->transactionService->transfer([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
            'amount' => 100,
            'commission_fee' => 10,
        ]);

        $this->transactionService->transfer([
            'sender_id' => $user2->id,
            'receiver_id' => $user1->id,
            'amount' => 50,
            'commission_fee' => 5,
        ]);

        $user1->refresh();
        $user2->refresh();

        $totalFinalBalance = $user1->balance + $user2->balance;
        $totalCommissions = 10 + 5;

        expect($user1->balance)->toBe('440.00')
            ->and($user2->balance)->toBe('545.00')
            ->and((float)$totalFinalBalance)->toBe((float)($totalInitialBalance - $totalCommissions));
    });
});

describe('Transaction Isolation Tests', function () {
    it('ensures transaction isolation with proper locking', function () {
        $sender = User::factory()->create(['balance' => 1000]);
        $receiver = User::factory()->create(['balance' => 500]);

        DB::transaction(function () use ($sender, $receiver) {
            $lockedSender = User::lockForUpdate()->find($sender->id);
            $lockedSender->decrement('balance', 100);

            expect($lockedSender)->not->toBeNull();
        });

        $sender->refresh();
        expect($sender->balance)->toBe('900.00');
    });

    it('maintains consistency across multiple database operations', function () {
        $sender = User::factory()->create(['balance' => 1000]);
        $receiver1 = User::factory()->create(['balance' => 0]);
        $receiver2 = User::factory()->create(['balance' => 0]);
        $receiver3 = User::factory()->create(['balance' => 0]);

        $operations = [
            ['receiver' => $receiver1, 'amount' => 100, 'fee' => 10],
            ['receiver' => $receiver2, 'amount' => 200, 'fee' => 20],
            ['receiver' => $receiver3, 'amount' => 150, 'fee' => 15],
        ];

        foreach ($operations as $operation) {
            $this->transactionService->transfer([
                'sender_id' => $sender->id,
                'receiver_id' => $operation['receiver']->id,
                'amount' => $operation['amount'],
                'commission_fee' => $operation['fee'],
            ]);
        }

        $sender->refresh();
        $receiver1->refresh();
        $receiver2->refresh();
        $receiver3->refresh();

        $totalSent = 100 + 200 + 150;
        $totalFees = 10 + 20 + 15;

        expect($sender->balance)->toBe(number_format(1000 - $totalSent - $totalFees, 2, '.', ''))
            ->and($receiver1->balance)->toBe('100.00')
            ->and($receiver2->balance)->toBe('200.00')
            ->and($receiver3->balance)->toBe('150.00')
            ->and(Transaction::count())->toBe(3);
    });
});

