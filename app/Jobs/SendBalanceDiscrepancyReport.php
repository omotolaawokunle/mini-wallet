<?php

namespace App\Jobs;

use App\Models\User;
use App\Mail\BalanceDiscrepancyMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendBalanceDiscrepancyReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $flaggedUsers = User::whereNotNull('flagged_at')
            ->orderBy('flagged_at', 'desc')
            ->get();

        if ($flaggedUsers->isEmpty()) {
            Log::info('Balance verification completed: No discrepancies found');
            return;
        }

        $adminEmail = config('mail.admin_email', config('mail.from.address'));

        try {
            Mail::to($adminEmail)->send(new BalanceDiscrepancyMail($flaggedUsers));
            Log::info("Balance discrepancy report sent to {$adminEmail} with {$flaggedUsers->count()} flagged users");
        } catch (\Exception $e) {
            Log::error("Failed to send balance discrepancy report: {$e->getMessage()}");
            throw $e;
        }
    }
}

