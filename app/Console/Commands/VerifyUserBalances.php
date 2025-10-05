<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Jobs\VerifyUserBalance;
use App\Jobs\SendBalanceDiscrepancyReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class VerifyUserBalances extends Command
{
    protected $signature = 'wallet:verify-balances';

    protected $description = 'Verify all user balances and flag accounts with discrepancies';

    public function handle(): int
    {
        $this->info('Starting balance verification for all users...');

        $users = User::whereNull('deleted_at');
        $totalUsers = $users->count();

        if ($totalUsers === 0) {
            $this->warn('No users found to verify.');
            return Command::SUCCESS;
        }

        $this->info("Found {$totalUsers} users to verify.");

        $jobs = [];
        $users->chunk(100, function ($users) use (&$jobs) {
            foreach ($users as $user) {
                $jobs[] = new VerifyUserBalance($user->id);
            }
        });

        Bus::chain([
            ...$jobs,
            new SendBalanceDiscrepancyReport(),
        ])->dispatch();

        $this->info("Balance verification jobs dispatched for {$totalUsers} users.");
        $this->info('Admin will receive an email report if any discrepancies are found.');

        return Command::SUCCESS;
    }
}
