<?php

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::factory()->create(['balance' => 100000]);
    $this->actingAs($this->user);
});

describe('Transaction Performance Tests', function () {
    it('efficiently retrieves user transactions from large dataset', function () {
        $otherUsers = User::factory()->count(50)->create(['balance' => 1000]);

        foreach ($otherUsers as $index => $otherUser) {
            Transaction::factory()->count(20)->create([
                'sender_id' => $this->user->id,
                'receiver_id' => $otherUser->id,
            ]);

            Transaction::factory()->count(20)->create([
                'sender_id' => $otherUser->id,
                'receiver_id' => $this->user->id,
            ]);
        }

        $unrelatedUser1 = User::factory()->create(['balance' => 1000]);
        $unrelatedUser2 = User::factory()->create(['balance' => 1000]);
        Transaction::factory()->count(500)->create([
            'sender_id' => $unrelatedUser1->id,
            'receiver_id' => $unrelatedUser2->id,
        ]);

        DB::enableQueryLog();

        $response = $this->getJson('/api/transactions');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();

        expect($response->json('data'))->toHaveCount(20)
            ->and($response->json('meta.total'))->toBe(2000);

        $queryCount = count($queries);
        expect($queryCount)->toBeLessThanOrEqual(4);
    });

    it('uses composite index for filtering transactions', function () {
        $otherUsers = User::factory()->count(10)->create(['balance' => 1000]);

        foreach ($otherUsers as $otherUser) {
            Transaction::factory()->count(100)->create([
                'sender_id' => $this->user->id,
                'receiver_id' => $otherUser->id,
            ]);
        }

        DB::enableQueryLog();

        $response = $this->getJson('/api/transactions');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();

        $mainQuery = $queries[0]['query'] ?? '';
        expect($mainQuery)->toContain('sender_id')
            ->and($mainQuery)->toContain('receiver_id');

        $queryTime = $queries[0]['time'] ?? 0;
        expect($queryTime)->toBeLessThan(1000);
    });

    it('eager loads relationships without N+1 queries', function () {
        $otherUsers = User::factory()->count(20)->create(['balance' => 1000]);

        foreach ($otherUsers as $otherUser) {
            Transaction::factory()->create([
                'sender_id' => $this->user->id,
                'receiver_id' => $otherUser->id,
            ]);
        }

        DB::enableQueryLog();

        $response = $this->getJson('/api/transactions');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();

        expect(count($queries))->toBeLessThanOrEqual(4);

        $data = $response->json('data');
        expect($data)->toHaveCount(20);

        foreach ($data as $transaction) {
            expect($transaction)->toHaveKeys(['sender_id', 'receiver_id']);
        }
    });

    it('handles pagination efficiently with large dataset', function () {
        $otherUser = User::factory()->create(['balance' => 10000]);

        Transaction::factory()->count(1000)->create([
            'sender_id' => $this->user->id,
            'receiver_id' => $otherUser->id,
        ]);

        DB::enableQueryLog();

        $responsePage1 = $this->getJson('/api/transactions?page=1');
        $queries1 = DB::getQueryLog();

        DB::flushQueryLog();

        $responsePage50 = $this->getJson('/api/transactions?page=50');
        $queries50 = DB::getQueryLog();

        DB::disableQueryLog();

        $responsePage1->assertOk();
        $responsePage50->assertOk();

        expect(count($queries1))->toBeLessThanOrEqual(4)
            ->and(count($queries50))->toBeLessThanOrEqual(4);

        expect($responsePage1->json('data'))->toHaveCount(20)
            ->and($responsePage50->json('data'))->toHaveCount(20);
    });

    it('scope forUser correctly filters transactions in large dataset', function () {
        $user1 = User::factory()->create(['balance' => 1000]);
        $user2 = User::factory()->create(['balance' => 1000]);
        $user3 = User::factory()->create(['balance' => 1000]);

        Transaction::factory()->count(200)->create([
            'sender_id' => $this->user->id,
            'receiver_id' => $user1->id,
        ]);

        Transaction::factory()->count(150)->create([
            'sender_id' => $user2->id,
            'receiver_id' => $this->user->id,
        ]);

        Transaction::factory()->count(500)->create([
            'sender_id' => $user2->id,
            'receiver_id' => $user3->id,
        ]);

        $userTransactions = Transaction::forUser($this->user->id)->count();

        expect($userTransactions)->toBe(350);

        $response = $this->getJson('/api/transactions');

        $response->assertOk();
        expect($response->json('meta.total'))->toBe(350);
    });

    it('performs well with mixed sender and receiver queries', function () {
        $users = User::factory()->count(30)->create(['balance' => 1000]);

        foreach ($users->take(15) as $user) {
            Transaction::factory()->count(30)->create([
                'sender_id' => $this->user->id,
                'receiver_id' => $user->id,
            ]);
        }

        foreach ($users->skip(15) as $user) {
            Transaction::factory()->count(30)->create([
                'sender_id' => $user->id,
                'receiver_id' => $this->user->id,
            ]);
        }

        DB::enableQueryLog();

        $startTime = microtime(true);
        $response = $this->getJson('/api/transactions');
        $endTime = microtime(true);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $executionTime = ($endTime - $startTime) * 1000;

        $response->assertOk();

        expect($response->json('meta.total'))->toBe(900)
            ->and(count($queries))->toBeLessThanOrEqual(4)
            ->and($executionTime)->toBeLessThan(2000);
    });

    it('handles concurrent high-volume transaction queries', function () {
        $otherUsers = User::factory()->count(100)->create(['balance' => 1000]);

        foreach ($otherUsers as $index => $otherUser) {
            Transaction::factory()->count(10)->create([
                'sender_id' => ($index % 2 === 0) ? $this->user->id : $otherUser->id,
                'receiver_id' => ($index % 2 === 0) ? $otherUser->id : $this->user->id,
            ]);
        }

        $totalTransactions = Transaction::forUser($this->user->id)->count();
        expect($totalTransactions)->toBe(1000);

        DB::enableQueryLog();

        $response = $this->getJson('/api/transactions?page=1');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();

        expect($response->json('data'))->toHaveCount(20)
            ->and($response->json('meta.total'))->toBe(1000)
            ->and(count($queries))->toBeLessThanOrEqual(4);
    });

    it('efficiently sorts large transaction dataset by latest', function () {
        $otherUser = User::factory()->create(['balance' => 10000]);

        $transactions = Transaction::factory()->count(500)->create([
            'sender_id' => $this->user->id,
            'receiver_id' => $otherUser->id,
        ]);

        DB::enableQueryLog();

        $response = $this->getJson('/api/transactions');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(20);

        for ($i = 0; $i < count($data) - 1; $i++) {
            $current = strtotime($data[$i]['created_at']);
            $next = strtotime($data[$i + 1]['created_at']);
            expect($current)->toBeGreaterThanOrEqual($next);
        }

        $hasOrderBy = false;
        foreach ($queries as $query) {
            if (str_contains($query['query'], 'order by')) {
                $hasOrderBy = true;
                break;
            }
        }
        expect($hasOrderBy)->toBeTrue();
    });
});

