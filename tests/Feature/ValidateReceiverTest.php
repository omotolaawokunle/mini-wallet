<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['balance' => 1000]);
    $this->actingAs($this->user);
});

describe('Validate Receiver', function () {
    it('returns success when receiver is found and not flagged', function () {
        $receiver = User::factory()->create(['balance' => 500]);

        $response = $this->postJson('/api/validate-receiver', [
            'receiver_id' => $receiver->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Receiver found',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'balance',
                ],
            ]);

        expect($response->json('data.id'))->toBe($receiver->id);
    });

    it('returns error when receiver does not exist', function () {
        $response = $this->postJson('/api/validate-receiver', [
            'receiver_id' => 999999,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Receiver not found',
                'errors' => [
                    'receiver_id' => 'Receiver not found',
                ],
            ]);
    });

    it('returns error when receiver is flagged', function () {
        $receiver = User::factory()->create([
            'balance' => 500,
            'flagged_at' => now(),
            'flagged_reason' => 'Balance discrepancy detected',
        ]);

        $response = $this->postJson('/api/validate-receiver', [
            'receiver_id' => $receiver->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Receiver is flagged',
                'errors' => [
                    'receiver_id' => 'Receiver cannot receive funds. Please contact support.',
                ],
            ]);
    });

    it('allows validation of non-flagged receiver with flagged_at null', function () {
        $receiver = User::factory()->create([
            'balance' => 500,
            'flagged_at' => null,
            'flagged_reason' => null,
        ]);

        $response = $this->postJson('/api/validate-receiver', [
            'receiver_id' => $receiver->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Receiver found',
            ]);
    });

    it('requires authentication', function () {
        auth()->guard('web')->logout();

        $receiver = User::factory()->create(['balance' => 500]);

        $response = $this->postJson('/api/validate-receiver', [
            'receiver_id' => $receiver->id,
        ]);

        $response->assertUnauthorized();
    });

    it('returns user data with correct structure', function () {
        $receiver = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'balance' => 750.50,
        ]);

        $response = $this->postJson('/api/validate-receiver', [
            'receiver_id' => $receiver->id,
        ]);

        $response->assertOk();

        $data = $response->json('data');

        expect($data['id'])->toBe($receiver->id)
            ->and($data['name'])->toBe('John Doe')
            ->and($data['email'])->toBe('john@example.com')
            ->and($data['balance'])->toBe('750.50');
    });

    it('validates different receivers independently', function () {
        $receiver1 = User::factory()->create(['balance' => 500]);
        $receiver2 = User::factory()->create([
            'balance' => 300,
            'flagged_at' => now(),
            'flagged_reason' => 'Test',
        ]);

        $response1 = $this->postJson('/api/validate-receiver', [
            'receiver_id' => $receiver1->id,
        ]);

        $response2 = $this->postJson('/api/validate-receiver', [
            'receiver_id' => $receiver2->id,
        ]);

        $response1->assertOk();
        $response2->assertStatus(422)
            ->assertJsonPath('errors.receiver_id', 'Receiver cannot receive funds. Please contact support.');
    });

    it('can validate the authenticated user as receiver', function () {
        $response = $this->postJson('/api/validate-receiver', [
            'receiver_id' => $this->user->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Receiver found',
            ]);

        expect($response->json('data.id'))->toBe($this->user->id);
    });

    it('returns error if authenticated user is flagged and tries to validate self', function () {
        $this->user->update([
            'flagged_at' => now(),
            'flagged_reason' => 'Balance discrepancy',
        ]);

        $response = $this->postJson('/api/validate-receiver', [
            'receiver_id' => $this->user->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Receiver is flagged',
            ]);
    });

    it('handles soft deleted users', function () {
        $receiver = User::factory()->create(['balance' => 500]);
        $receiver->delete(); // Soft delete

        $response = $this->postJson('/api/validate-receiver', [
            'receiver_id' => $receiver->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Receiver not found',
            ]);
    });

    it('returns consistent error format for all error cases', function () {
        $receiver = User::factory()->create([
            'flagged_at' => now(),
            'flagged_reason' => 'Test',
        ]);

        $response1 = $this->postJson('/api/validate-receiver', [
            'receiver_id' => 999999,
        ]);

        $response2 = $this->postJson('/api/validate-receiver', [
            'receiver_id' => $receiver->id,
        ]);

        expect($response1->json())->toHaveKeys(['success', 'message', 'errors'])
            ->and($response2->json())->toHaveKeys(['success', 'message', 'errors'])
            ->and($response1->json('success'))->toBeFalse()
            ->and($response2->json('success'))->toBeFalse();
    });
});

