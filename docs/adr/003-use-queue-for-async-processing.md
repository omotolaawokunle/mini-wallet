# ADR 003: Use Queue System for Asynchronous Transfer Processing

## Status

Accepted

## Date

2025-10-05

## Context

Money transfers involve multiple operations:
1. Validate sender and receiver
2. Check balance sufficiency
3. Lock user records
4. Update balances
5. Create transaction record
6. Broadcast real-time notifications
7. Log transaction

Processing these operations synchronously would:
- Block HTTP request for 50-200ms
- Tie up web server resources
- Create poor user experience
- Make system less scalable

We considered the following options:

### Option 1: Synchronous Processing

```php
public function store(TransferRequest $request): JsonResponse
{
    $transaction = $this->transactionService->transfer($request->validated());
    
    return $this->success('Transfer completed', [
        'transaction' => new TransactionResource($transaction)
    ]);
}
```

**Pros:**
- Simple implementation
- Immediate feedback
- No queue infrastructure needed

**Cons:**
- ❌ Slow response times (50-200ms)
- ❌ Blocks web server thread
- ❌ Poor scalability
- ❌ No retry mechanism
- ❌ Timeout issues on slow operations

**Verdict:** ❌ Not suitable for production

### Option 2: Background Jobs with Queue

```php
public function store(TransferRequest $request): JsonResponse
{
    ProcessTransfer::dispatch(...$request->validated());
    
    return $this->success('Transaction processing');
}
```

**Pros:**
- Fast response (<10ms)
- Non-blocking
- Retry capability
- Better scalability
- Resource optimization
- Can distribute load

**Cons:**
- Requires queue infrastructure (Redis)
- Requires worker processes
- More complex error handling
- Eventual consistency

**Verdict:** ✅ Best for production

### Option 3: Hybrid Approach

Process simple transfers synchronously, complex ones asynchronously.

**Pros:**
- Optimized for each case

**Cons:**
- Inconsistent behavior
- Complex logic
- Harder to maintain

**Verdict:** ❌ Overcomplicated

## Decision

We chose **Background Jobs with Queue** (Option 2) using Laravel Horizon.

## Rationale

1. **Performance**: <10ms API response time
2. **Scalability**: Can scale workers independently from web servers
3. **Reliability**: Built-in retry mechanism for failed transfers
4. **User Experience**: Instant feedback + real-time updates
5. **Resource Efficiency**: Don't tie up web server threads
6. **Monitoring**: Laravel Horizon provides queue dashboard
7. **Production Ready**: Industry standard approach

## Implementation

### Queue Configuration

```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'redis'),

'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => false,
    ],
],
```

### Job Class

```php
class ProcessTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $timeout = 60;
    public $backoff = [10, 30, 60]; // Retry delays in seconds
    
    public function __construct(
        public int $senderId,
        public int $receiverId,
        public float $amount,
        public float $commissionFee,
    ) {}
    
    public function handle(TransactionService $service): void
    {
        try {
            $service->transfer([
                'sender_id' => $this->senderId,
                'receiver_id' => $this->receiverId,
                'amount' => $this->amount,
                'commission_fee' => $this->commissionFee,
            ]);
        } catch (InsufficientBalanceException $e) {
            // Don't retry insufficient balance
            $this->fail($e);
            
            // Notify user of failure
            event(new TransferFailed($e->getMessage(), [
                'sender_id' => $this->senderId,
                'receiver_id' => $this->receiverId,
                'amount' => $this->amount,
            ]));
        } catch (\Exception $e) {
            // Log and rethrow for retry
            Log::error('Transfer failed', [
                'sender_id' => $this->senderId,
                'receiver_id' => $this->receiverId,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    public function failed(\Throwable $exception): void
    {
        // Handle permanent failure after all retries
        Log::error('Transfer permanently failed', [
            'sender_id' => $this->senderId,
            'receiver_id' => $this->receiverId,
            'error' => $exception->getMessage(),
        ]);
        
        event(new TransferFailed($exception->getMessage(), [
            'sender_id' => $this->senderId,
            'receiver_id' => $this->receiverId,
            'amount' => $this->amount,
        ]));
    }
}
```

### Controller

```php
public function store(TransferRequest $request): JsonResponse
{
    $this->authorize('transfer', [Transaction::class, $request->sender_id]);
    
    // Queue the transfer
    $this->transactionService->queueTransfer($request->validated());
    
    // Return immediately
    return $this->success('Transaction processing');
}
```

### Service Layer

```php
public function queueTransfer(array $data): void
{
    ProcessTransfer::dispatch(
        $data['sender_id'],
        $data['receiver_id'],
        $data['amount'],
        $data['commission_fee'],
    );
}
```

### Worker Command

```bash
# Start queue worker
php artisan queue:listen --tries=3

# Or with Horizon
php artisan horizon
```

## User Experience Flow

```
┌──────┐                 ┌──────────┐                ┌────────┐
│ User │                 │ Frontend │                │   API  │
└──┬───┘                 └────┬─────┘                └───┬────┘
   │                          │                          │
   │ 1. Click "Transfer"      │                          │
   │─────────────────────────►│                          │
   │                          │                          │
   │                          │ 2. POST /api/transactions│
   │                          │─────────────────────────►│
   │                          │                          │
   │                          │◄─────────────────────────│
   │◄─────────────────────────│ 3. "Transaction processing"
   │ Show loading indicator   │      (< 10ms)           │
   │                          │                          │
   │                          │         Queue            │
   │                          │           │              │
   │                          │           │ 4. Process   │
   │                          │           │ (50-200ms)   │
   │                          │           │              │
   │                          │◄──────────┤              │
   │◄─────────────────────────│ 5. WebSocket Event      │
   │ "Transfer completed"     │ (TransactionCreated)    │
   │ Balance updated          │                          │
```

### Benefits for User

1. **Instant Feedback**: "Transaction processing" appears immediately
2. **No Waiting**: User can continue using app
3. **Real-time Update**: Notified when transfer completes
4. **Error Notification**: Informed if transfer fails

## Laravel Horizon

### Why Horizon?

- **Dashboard**: Monitor queue health at `/horizon`
- **Metrics**: Throughput, runtime, failed jobs
- **Management**: Pause, retry, delete jobs
- **Auto-scaling**: Scale workers based on load
- **Tagging**: Organize jobs by tags
- **Notifications**: Slack/email on failures

### Configuration

```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'processes' => 10,
            'tries' => 3,
        ],
    ],
    
    'local' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'processes' => 3,
            'tries' => 3,
        ],
    ],
],
```

### Access Control

```php
// app/Providers/HorizonServiceProvider.php
protected function gate(): void
{
    Gate::define('viewHorizon', function ($user) {
        return in_array($user->email, [
            'admin@example.com',
        ]);
    });
}
```

## Error Handling

### Retry Strategy

1. **Transient Errors**: Retry with exponential backoff
   - Database connection errors
   - Network timeouts
   - Temporary service unavailability

2. **Permanent Errors**: Don't retry
   - Insufficient balance
   - User not found
   - Validation errors

### Failed Jobs

```php
// Retry failed job
php artisan queue:retry {job-id}

// Retry all failed jobs
php artisan queue:retry all

// Delete failed job
php artisan queue:forget {job-id}

// Flush all failed jobs
php artisan queue:flush
```

## Consequences

### Positive

- ✅ Fast API responses (<10ms)
- ✅ Better user experience
- ✅ Scalable architecture
- ✅ Automatic retries
- ✅ Resource optimization
- ✅ Load distribution
- ✅ Monitoring dashboard
- ✅ Failure handling

### Negative

- ❌ Requires Redis infrastructure
- ❌ Requires worker processes
- ❌ Eventual consistency (mitigated by real-time updates)
- ❌ More complex debugging
- ❌ Additional monitoring needed

### Neutral

- 🔸 Users notified via WebSocket when complete
- 🔸 Need to handle edge cases (worker crashes, etc.)
- 🔸 Queue depth monitoring important

## Performance Metrics

### Without Queue (Synchronous)

- **API Response**: 50-200ms
- **Max Throughput**: ~50 transfers/second
- **Resource Usage**: High (blocks web threads)

### With Queue (Asynchronous)

- **API Response**: <10ms
- **Processing Time**: 50-200ms (in background)
- **Max Throughput**: Limited by workers, not web servers
- **Resource Usage**: Optimized (separate worker pool)

## Production Deployment

### Supervisor Configuration

```ini
[program:mini-wallet-horizon]
process_name=%(program_name)s
command=php /path/to/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/horizon.log
stopwaitsecs=3600
```

### Monitoring

- **Horizon Dashboard**: `/horizon`
- **Queue Depth**: Alert if > 100 jobs
- **Failed Jobs**: Alert on failures
- **Worker Health**: Ensure workers running

## Alternative for High Scale

If queue becomes bottleneck, consider:

1. **Multiple Queue Connections**: Shard by user ID
2. **Priority Queues**: High-priority users
3. **Dedicated Queue Servers**: Separate from Redis cache
4. **RabbitMQ/SQS**: More robust message brokers

## References

- [Laravel Queues Documentation](https://laravel.com/docs/queues)
- [Laravel Horizon Documentation](https://laravel.com/docs/horizon)
- [Queue Best Practices](https://laravel-news.com/laravel-queue-work)
- [Asynchronous Processing Patterns](https://www.enterpriseintegrationpatterns.com/patterns/messaging/)

