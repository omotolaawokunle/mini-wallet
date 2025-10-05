<?php

use App\Models\User;
use App\Models\Transaction;

beforeEach(function () {
    $this->user = User::factory()->create(['balance' => 1000]);
    $this->actingAs($this->user);
});

describe('Transaction Index', function () {
    it('returns paginated list of user transactions', function () {
        $otherUser = User::factory()->create(['balance' => 1000]);

        Transaction::factory()->count(15)->create([
            'sender_id' => $this->user->id,
            'receiver_id' => $otherUser->id,
        ]);

        Transaction::factory()->count(10)->create([
            'sender_id' => $otherUser->id,
            'receiver_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/transactions');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'sender_id',
                        'receiver_id',
                        'amount',
                        'commission_fee',
                        'created_at',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'total',
                    'per_page',
                ]
            ]);

        expect($response->json('meta.per_page'))->toBe(20);
    });

    it('returns only transactions involving authenticated user', function () {
        $user1 = User::factory()->create(['balance' => 1000]);
        $user2 = User::factory()->create(['balance' => 1000]);

        Transaction::factory()->create([
            'sender_id' => $this->user->id,
            'receiver_id' => $user1->id,
            'amount' => 100,
        ]);

        Transaction::factory()->create([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
            'amount' => 50,
        ]);

        $response = $this->getJson('/api/transactions');

        $response->assertOk();

        $transactions = $response->json('data');
        expect(count($transactions))->toBe(1);
    });

    it('includes sender and receiver information', function () {
        $receiver = User::factory()->create(['balance' => 1000]);

        Transaction::factory()->create([
            'sender_id' => $this->user->id,
            'receiver_id' => $receiver->id,
        ]);

        $response = $this->getJson('/api/transactions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'sender_id',
                        'receiver_id',
                    ]
                ]
            ]);
    });

    it('returns transactions in correct pagination', function () {
        $otherUser = User::factory()->create(['balance' => 1000]);

        Transaction::factory()->count(25)->create([
            'sender_id' => $this->user->id,
            'receiver_id' => $otherUser->id,
        ]);

        $response = $this->getJson('/api/transactions');

        $response->assertOk();

        expect(count($response->json('data')))->toBe(20)
            ->and($response->json('meta.total'))->toBe(25)
            ->and($response->json('meta.current_page'))->toBe(1);

        $page2Response = $this->getJson('/api/transactions?page=2');

        expect(count($page2Response->json('data')))->toBe(5)
            ->and($page2Response->json('meta.current_page'))->toBe(2);
    });

    it('requires authentication', function () {
        auth()->guard('web')->logout();

        $response = $this->getJson('/api/transactions');

        $response->assertUnauthorized();
    });

    it('returns empty data when user has no transactions', function () {
        $response = $this->getJson('/api/transactions');

        $response->assertOk();
        expect($response->json('data'))->toBe([]);
    });
});

describe('Transaction Store', function () {
    it('successfully queues a transfer', function () {
        \Illuminate\Support\Facades\Queue::fake();

        $receiver = User::factory()->create(['balance' => 500]);

        $response = $this->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 100,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Transaction processing',
            ]);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ProcessTransfer::class);
    });

    it('dispatches transfer job with correct parameters', function () {
        \Illuminate\Support\Facades\Queue::fake();

        $receiver = User::factory()->create(['balance' => 500]);

        $response = $this->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 100,
        ]);

        $response->assertOk();

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ProcessTransfer::class, function ($job) {
            return $job->senderId === $this->user->id &&
                $job->amount === 100.0;
        });
    });

    it('calculates commission fee correctly before queuing', function () {
        \Illuminate\Support\Facades\Queue::fake();

        $receiver = User::factory()->create(['balance' => 500]);

        $response = $this->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 100,
        ]);

        $response->assertOk();

        $commissionPercentage = config('constant.commission_percentage');
        $expectedFee = 100 * $commissionPercentage;

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ProcessTransfer::class, function ($job) use ($expectedFee) {
            return abs($job->commissionFee - $expectedFee) < 0.01;
        });
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/transactions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['receiver_id', 'amount']);
    });


    it('validates receiver exists', function () {
        $response = $this->postJson('/api/transactions', [
            'receiver_id' => 999999,
            'amount' => 100,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['receiver_id']);
    });

    it('validates amount is numeric', function () {
        $receiver = User::factory()->create(['balance' => 500]);

        $response = $this->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    });

    it('validates amount is greater than zero', function () {
        $receiver = User::factory()->create(['balance' => 500]);

        $response = $this->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    });

    it('dispatches job to transfers queue', function () {
        \Illuminate\Support\Facades\Queue::fake();

        $receiver = User::factory()->create(['balance' => 500]);

        $response = $this->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 100,
        ]);

        $response->assertOk();

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ProcessTransfer::class, function ($job) {
            return $job->queue === 'transfers';
        });
    });

    it('requires authentication', function () {
        auth()->guard('web')->logout();

        $receiver = User::factory()->create(['balance' => 500]);

        $response = $this->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 100,
        ]);

        $response->assertUnauthorized();
    });

    it('queues decimal amounts correctly', function () {
        \Illuminate\Support\Facades\Queue::fake();

        $receiver = User::factory()->create(['balance' => 500]);

        $response = $this->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 50.75,
        ]);

        $response->assertOk();

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ProcessTransfer::class, function ($job) {
            return $job->amount === 50.75;
        });
    });

    it('queues multiple transfers independently', function () {
        \Illuminate\Support\Facades\Queue::fake();

        $receiver1 = User::factory()->create(['balance' => 500]);
        $receiver2 = User::factory()->create(['balance' => 300]);

        $this->postJson('/api/transactions', [
            'receiver_id' => $receiver1->id,
            'amount' => 100,
        ]);

        $this->postJson('/api/transactions', [
            'receiver_id' => $receiver2->id,
            'amount' => 50,
        ]);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ProcessTransfer::class, 2);
    });

    it('does not immediately deduct balance when queuing', function () {
        \Illuminate\Support\Facades\Queue::fake();

        $receiver = User::factory()->create(['balance' => 500]);
        $initialBalance = $this->user->balance;

        $response = $this->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 100,
        ]);

        $response->assertOk();

        $this->user->refresh();

        expect($this->user->balance)->toBe($initialBalance);
    });

    it('does not allow transactions when user is flagged', function () {
        $sender = User::factory()->create(['balance' => 1000, 'flagged_at' => now(), 'flagged_reason' => 'Flagged for testing']);

        $receiver = User::factory()->create(['balance' => 500]);

        $response = $this->actingAs($sender)->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 100,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Your account has been flagged. Please contact support.',
            ]);
    });
});
