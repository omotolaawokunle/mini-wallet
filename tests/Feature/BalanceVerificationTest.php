<?php

use App\Models\User;
use App\Models\Transaction;
use App\Jobs\VerifyUserBalance;
use App\Jobs\SendBalanceDiscrepancyReport;
use App\Mail\BalanceDiscrepancyMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;

describe('Balance Verification', function () {
    it('correctly calculates expected balance from transactions', function () {
        $user = User::factory()->create(['balance' => 0]);
        $sender = User::factory()->create(['balance' => 1000]);
        $receiver = User::factory()->create(['balance' => 500]);

        // User receives 500
        Transaction::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'amount' => 500,
            'commission_fee' => 0,
        ]);

        // User sends 200 with 10 fee
        Transaction::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'amount' => 200,
            'commission_fee' => 10,
        ]);

        // Update user balance correctly: 500 - 200 - 10 = 290
        $user->update(['balance' => 290]);

        $job = new VerifyUserBalance($user->id);
        $job->handle();

        $user->refresh();

        expect($user->is_flagged)->toBeFalse()
            ->and($user->flagged_at)->toBeNull()
            ->and($user->flagged_reason)->toBeNull();
    });

    it('flags user when balance is higher than expected', function () {
        $user = User::factory()->create(['balance' => 1000]);
        $receiver = User::factory()->create(['balance' => 500]);

        // User sends 500 with 25 fee
        Transaction::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'amount' => 500,
            'commission_fee' => 25,
        ]);

        // Balance should be 1000 - 500 - 25 = 475, but it's 1000
        $job = new VerifyUserBalance($user->id);
        $job->handle();

        $user->refresh();

        expect($user->is_flagged)->toBeTrue()
            ->and($user->flagged_at)->not()->toBeNull()
            ->and($user->flagged_reason)->toContain('Balance mismatch detected');
    });

    it('flags user when balance is lower than expected', function () {
        $user = User::factory()->create(['balance' => 100]);
        $sender = User::factory()->create(['balance' => 1000]);

        // User receives 500
        Transaction::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'amount' => 500,
            'commission_fee' => 0,
        ]);

        // Balance should be 100 + 500 = 600, but it's 100
        $job = new VerifyUserBalance($user->id);
        $job->handle();

        $user->refresh();

        expect($user->is_flagged)->toBeTrue()
            ->and($user->flagged_at)->not()->toBeNull()
            ->and($user->flagged_reason)->toContain('Balance mismatch detected');
    });

    it('unflags user when balance is corrected', function () {
        $user = User::factory()->create([
            'balance' => 1000,
            'flagged_at' => now(),
            'flagged_reason' => 'Balance mismatch detected',
        ]);

        $receiver = User::factory()->create(['balance' => 500]);

        // User sends 500 with 25 fee
        Transaction::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'amount' => 500,
            'commission_fee' => 25,
        ]);

        // Correct the balance
        $user->update(['balance' => -525]);

        $job = new VerifyUserBalance($user->id);
        $job->handle();

        $user->refresh();

        expect($user->is_flagged)->toBeFalse()
            ->and($user->flagged_at)->toBeNull()
            ->and($user->flagged_reason)->toBeNull();
    });

    it('handles users with no transactions', function () {
        $user = User::factory()->create(['balance' => 0]);

        $job = new VerifyUserBalance($user->id);
        $job->handle();

        $user->refresh();

        expect($user->is_flagged)->toBeFalse();
    });

    it('handles multiple incoming and outgoing transactions', function () {
        $user = User::factory()->create(['balance' => 0]);
        $user1 = User::factory()->create(['balance' => 1000]);
        $user2 = User::factory()->create(['balance' => 1000]);
        $user3 = User::factory()->create(['balance' => 1000]);

        // User receives 300 from user1
        Transaction::factory()->create([
            'sender_id' => $user1->id,
            'receiver_id' => $user->id,
            'amount' => 300,
            'commission_fee' => 0,
        ]);

        // User receives 200 from user2
        Transaction::factory()->create([
            'sender_id' => $user2->id,
            'receiver_id' => $user->id,
            'amount' => 200,
            'commission_fee' => 0,
        ]);

        // User sends 150 with 5 fee to user3
        Transaction::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $user3->id,
            'amount' => 150,
            'commission_fee' => 5,
        ]);

        // Expected: 0 + 300 + 200 - 150 - 5 = 345
        $user->update(['balance' => 345]);

        $job = new VerifyUserBalance($user->id);
        $job->handle();

        $user->refresh();

        expect($user->is_flagged)->toBeFalse();
    });
});

describe('Balance Discrepancy Report', function () {
    it('sends email when flagged users exist', function () {
        Mail::fake();

        User::factory()->create([
            'balance' => 1000,
            'flagged_at' => now(),
            'flagged_reason' => 'Test discrepancy',
        ]);

        $job = new SendBalanceDiscrepancyReport();
        $job->handle();

        Mail::assertSent(BalanceDiscrepancyMail::class, function ($mail) {
            return $mail->flaggedUsers->count() === 1;
        });
    });

    it('does not send email when no flagged users exist', function () {
        Mail::fake();

        User::factory()->create(['balance' => 1000]);

        $job = new SendBalanceDiscrepancyReport();
        $job->handle();

        Mail::assertNothingSent();
    });

    it('includes all flagged users in email', function () {
        Mail::fake();

        User::factory()->count(3)->create([
            'flagged_at' => now(),
            'flagged_reason' => 'Test discrepancy',
        ]);

        User::factory()->count(2)->create(['balance' => 1000]);

        $job = new SendBalanceDiscrepancyReport();
        $job->handle();

        Mail::assertSent(BalanceDiscrepancyMail::class, function ($mail) {
            return $mail->flaggedUsers->count() === 3;
        });
    });

    it('sends email to configured admin address', function () {
        Mail::fake();
        config(['mail.admin_email' => 'admin@example.com']);

        User::factory()->create([
            'flagged_at' => now(),
            'flagged_reason' => 'Test discrepancy',
        ]);

        $job = new SendBalanceDiscrepancyReport();
        $job->handle();

        Mail::assertSent(BalanceDiscrepancyMail::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });
    });
});

describe('Balance Verification Command', function () {
    it('successfully dispatches verification jobs for all users', function () {
        \Illuminate\Support\Facades\Bus::fake();

        User::factory()->count(5)->create();

        Artisan::call('wallet:verify-balances');

        \Illuminate\Support\Facades\Bus::assertChained([
            VerifyUserBalance::class,
            VerifyUserBalance::class,
            VerifyUserBalance::class,
            VerifyUserBalance::class,
            VerifyUserBalance::class,
            SendBalanceDiscrepancyReport::class,
        ]);
    });

    it('includes report job at the end of chain', function () {
        \Illuminate\Support\Facades\Bus::fake();

        User::factory()->count(2)->create();

        Artisan::call('wallet:verify-balances');

        \Illuminate\Support\Facades\Bus::assertChained([
            VerifyUserBalance::class,
            VerifyUserBalance::class,
            SendBalanceDiscrepancyReport::class,
        ]);
    });

    it('handles zero users gracefully', function () {
        $exitCode = Artisan::call('wallet:verify-balances');

        expect($exitCode)->toBe(0);
    });

    it('excludes soft deleted users', function () {
        \Illuminate\Support\Facades\Bus::fake();

        User::factory()->count(3)->create();
        User::factory()->create(['deleted_at' => now()]);

        Artisan::call('wallet:verify-balances');

        // Should only process 3 active users + 1 report job = 4 jobs
        \Illuminate\Support\Facades\Bus::assertChained([
            VerifyUserBalance::class,
            VerifyUserBalance::class,
            VerifyUserBalance::class,
            SendBalanceDiscrepancyReport::class,
        ]);
    });
});

