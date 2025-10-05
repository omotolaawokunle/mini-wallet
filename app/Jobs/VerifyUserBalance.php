<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VerifyUserBalance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $userId
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            Log::warning("VerifyUserBalance: User {$this->userId} not found");
            return;
        }

        $expectedBalance = $this->calculateExpectedBalance($user);
        $actualBalance = (float) $user->balance;

        if (abs($expectedBalance - $actualBalance) > 0.01) {
            $discrepancy = $actualBalance - $expectedBalance;
            $reason = sprintf(
                'Balance mismatch detected. Expected: $%.2f, Actual: $%.2f, Discrepancy: $%.2f',
                $expectedBalance,
                $actualBalance,
                $discrepancy
            );

            $user->update([
                'flagged_at' => now(),
                'flagged_reason' => $reason,
            ]);

            Log::warning("User {$user->id} flagged: {$reason}");
        } else {
            if ($user->flagged_at !== null) {
                $user->update([
                    'flagged_at' => null,
                    'flagged_reason' => null,
                ]);

                Log::info("User {$user->id} unflagged: balance verified successfully");
            }
        }
    }

    private function calculateExpectedBalance(User $user): float
    {
        $outgoingTotal = Transaction::where('sender_id', $user->id)
            ->sum(DB::raw('amount + commission_fee'));

        $incomingTotal = Transaction::where('receiver_id', $user->id)
            ->sum('amount');

        return (float) ($incomingTotal - $outgoingTotal);
    }
}

