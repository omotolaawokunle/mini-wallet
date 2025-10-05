# Architecture Documentation

## System Overview

Mini Wallet is a full-stack digital wallet application that enables secure money transfers between users with real-time notifications and transaction history tracking.

### Technology Stack

**Backend:**
- Laravel 12 (PHP 8.2+)
- Laravel Sanctum (Session-based authentication)
- Laravel Horizon (Queue management)
- Redis (Queue driver & caching)
- SQLite/MySQL/PostgreSQL (Database)

**Frontend:**
- Vue 3 (Composition API with TypeScript)
- Pinia (State management)
- Vue Router (Routing)
- Tailwind CSS v4 (Styling)
- Axios (HTTP client)
- Laravel Echo (WebSocket client)

**Infrastructure:**
- Pusher (WebSocket server)
- Vite (Build tool)

---

## System Architecture

### High-Level Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         Client Layer                             │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Vue 3 SPA (TypeScript)                                   │  │
│  │  - Components (UI)                                        │  │
│  │  - Composables (Logic)                                    │  │
│  │  - Stores (State - Pinia)                                │  │
│  │  - Router (Navigation)                                    │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ HTTP / WebSocket
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Application Layer                           │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Laravel 12 (Backend API)                                 │  │
│  │                                                           │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │  │
│  │  │Controllers  │  │ Requests    │  │ Resources   │    │  │
│  │  │(HTTP Layer) │  │(Validation) │  │(Transform)  │    │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘    │  │
│  │                                                           │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │  │
│  │  │  Services   │  │   Models    │  │  Policies   │    │  │
│  │  │ (Business)  │  │  (Eloquent) │  │   (Auth)    │    │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘    │  │
│  │                                                           │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │  │
│  │  │    Jobs     │  │   Events    │  │ Exceptions  │    │  │
│  │  │  (Queues)   │  │(Broadcasting)│ │  (Custom)   │    │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘    │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       Data Layer                                 │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐         │
│  │  Database    │  │    Redis     │  │   Pusher     │         │
│  │ (SQLite/     │  │  (Queue &    │  │ (WebSocket)  │         │
│  │  MySQL/      │  │   Cache)     │  │              │         │
│  │  PostgreSQL) │  │              │  │              │         │
│  └──────────────┘  └──────────────┘  └──────────────┘         │
└─────────────────────────────────────────────────────────────────┘
```

---

## Application Flow

### Transfer Transaction Flow

```
┌──────┐                                   ┌──────────┐
│ User │                                   │ Frontend │
└──┬───┘                                   └────┬─────┘
   │                                            │
   │ 1. Initiate Transfer                      │
   │─────────────────────────────────────────►│
   │                                            │
   │                                            │ 2. POST /api/transactions
   │                                            │────────────────────┐
   │                                            │                    │
   │                                            │                    ▼
   │                                       ┌────┴───────────────────────┐
   │                                       │  TransactionController      │
   │                                       │  - Validate request         │
   │                                       │  - Authorize user           │
   │                                       │  - Call service             │
   │                                       └────┬───────────────────────┘
   │                                            │
   │                                            │ 3. Queue transfer
   │                                            ▼
   │                                       ┌────────────────────────────┐
   │                                       │  TransactionService        │
   │                                       │  - Queue ProcessTransfer   │
   │                                       └────┬───────────────────────┘
   │                                            │
   │ 4. Response: "Transaction processing"     │
   │◄──────────────────────────────────────────┤
   │                                            │
   │                                            │ 5. Job dispatched to queue
   │                                            ▼
   │                                       ┌────────────────────────────┐
   │                                       │  Redis Queue               │
   │                                       └────┬───────────────────────┘
   │                                            │
   │                                            │ 6. Job processed
   │                                            ▼
   │                                       ┌────────────────────────────┐
   │                                       │  ProcessTransfer Job       │
   │                                       │  - Lock user records       │
   │                                       │  - Validate balance        │
   │                                       │  - Update balances         │
   │                                       │  - Create transaction      │
   │                                       │  - Broadcast events        │
   │                                       └────┬───────────────────────┘
   │                                            │
   │                                            │ 7. Database transaction
   │                                            ▼
   │                                       ┌────────────────────────────┐
   │                                       │  Database (with locks)     │
   │                                       │  - UPDATE users (sender)   │
   │                                       │  - UPDATE users (receiver) │
   │                                       │  - INSERT transaction      │
   │                                       └────┬───────────────────────┘
   │                                            │
   │                                            │ 8. Broadcast events
   │                                            ▼
   │                                       ┌────────────────────────────┐
   │                                       │  Pusher (WebSocket)        │
   │                                       │  - TransactionCreated      │
   │                                       │    (to sender & receiver)  │
   │                                       └────┬───────────────────────┘
   │                                            │
   │ 9. Real-time notification                 │
   │◄──────────────────────────────────────────┤
   │ "Transfer completed"                      │
   │ "Balance updated"                         │
   │                                            │
```

---

## Design Patterns

### 1. Service Layer Pattern

Business logic is encapsulated in service classes, keeping controllers thin.

**Example: TransactionService**

```php
class TransactionService
{
    public function transfer(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            // Lock users
            $sender = User::lockForUpdate()->find($data['sender_id']);
            $receiver = User::lockForUpdate()->find($data['receiver_id']);
            
            // Validate
            if ($sender->balance < $data['amount'] + $data['commission_fee']) {
                throw new InsufficientBalanceException();
            }
            
            // Update balances
            $sender->decrement('balance', $data['amount'] + $data['commission_fee']);
            $receiver->increment('balance', $data['amount']);
            
            // Create transaction
            $transaction = Transaction::create($data);
            
            // Broadcast events
            event(new TransactionCreated($transaction, $sender));
            event(new TransactionCreated($transaction, $receiver));
            
            return $transaction;
        });
    }
}
```

**Benefits:**
- Reusable business logic
- Easier testing
- Cleaner controllers
- Single responsibility principle

---

### 2. Repository Pattern (Implicit via Eloquent)

Laravel's Eloquent ORM acts as a repository, abstracting database operations.

**Example:**

```php
// Model with scopes
class Transaction extends Model
{
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
                ->orWhere('receiver_id', $userId);
        });
    }
}

// Usage
$transactions = Transaction::forUser($userId)->latest()->paginate(20);
```

---

### 3. Job Queue Pattern

Time-consuming operations are processed asynchronously via queues.

**Example: ProcessTransfer Job**

```php
class ProcessTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function handle(TransactionService $service): void
    {
        try {
            $service->transfer([
                'sender_id' => $this->senderId,
                'receiver_id' => $this->receiverId,
                'amount' => $this->amount,
                'commission_fee' => $this->commissionFee,
            ]);
        } catch (\Exception $e) {
            event(new TransferFailed($e->getMessage(), [...]));
            throw $e;
        }
    }
}
```

**Benefits:**
- Non-blocking operations
- Better user experience
- Retry capability
- Distributed processing

---

### 4. Event Broadcasting Pattern

Real-time updates using events and WebSockets.

**Backend:**

```php
class TransactionCreated implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id),
        ];
    }
}
```

**Frontend:**

```javascript
Echo.private(`user.${userId}`)
  .listen('TransactionCreated', (event) => {
    // Update UI in real-time
  });
```

---

### 5. Policy Pattern

Authorization logic is centralized in policy classes.

**Example: TransactionPolicy**

```php
class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }
    
    public function transfer(User $user, int $senderId): bool
    {
        return $user->id === $senderId;
    }
}
```

**Usage in Controller:**

```php
$this->authorize('transfer', [Transaction::class, $request->sender_id]);
```

---

### 6. Resource Pattern

API responses are transformed using resource classes.

**Example: TransactionResource**

```php
class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'amount' => number_format($this->amount, 2),
            'commission_fee' => number_format($this->commission_fee, 2),
            'created_at' => $this->created_at,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'receiver' => new UserResource($this->whenLoaded('receiver')),
        ];
    }
}
```

---

## Database Design

### Entity Relationship Diagram

```
┌─────────────────────────┐
│        users            │
├─────────────────────────┤
│ id (PK)                 │
│ name                    │
│ email (unique)          │
│ password                │
│ balance (decimal)       │
│ email_verified_at       │
│ remember_token          │
│ created_at              │
│ updated_at              │
│ deleted_at              │
└───────┬─────────────────┘
        │
        │ 1
        │
        │ *
┌───────┴─────────────────┐
│    transactions         │
├─────────────────────────┤
│ id (PK)                 │
│ sender_id (FK)          │◄─────┐
│ receiver_id (FK)        │◄─────┤
│ amount (decimal)        │      │
│ commission_fee (decimal)│      │
│ created_at              │      │
│ updated_at              │      │
└─────────────────────────┘      │
                                 │
                                 │
                         References users.id
```

### Tables

#### users

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint | PK, auto_increment | Primary key |
| name | varchar(255) | NOT NULL | User's full name |
| email | varchar(255) | UNIQUE, NOT NULL | User's email |
| password | varchar(255) | NOT NULL | Hashed password |
| balance | decimal(12,2) | DEFAULT 0.00 | User's wallet balance |
| email_verified_at | timestamp | NULL | Email verification time |
| remember_token | varchar(100) | NULL | Remember token |
| created_at | timestamp | NULL | Creation timestamp |
| updated_at | timestamp | NULL | Last update timestamp |
| deleted_at | timestamp | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE KEY (email)

#### transactions

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | bigint | PK, auto_increment | Primary key |
| sender_id | bigint | FK, NOT NULL | Sender user ID |
| receiver_id | bigint | FK, NOT NULL | Receiver user ID |
| amount | decimal(12,2) | NOT NULL | Transfer amount |
| commission_fee | decimal(12,2) | DEFAULT 0.00 | Transaction fee |
| created_at | timestamp | NULL | Creation timestamp |
| updated_at | timestamp | NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- FOREIGN KEY (sender_id) REFERENCES users(id)
- FOREIGN KEY (receiver_id) REFERENCES users(id)
- INDEX (sender_id)
- INDEX (receiver_id)
- INDEX (created_at)

---

## Concurrency Control

### Problem

Multiple simultaneous transfers from the same account could cause:
- Negative balances
- Lost updates
- Race conditions

### Solution: Pessimistic Locking

**Implementation:**

```php
DB::transaction(function () use ($data) {
    // Lock rows for update until transaction completes
    $sender = User::lockForUpdate()->find($data['sender_id']);
    $receiver = User::lockForUpdate()->find($data['receiver_id']);
    
    // Only locked transaction can proceed
    if ($sender->balance < $data['amount'] + $data['commission_fee']) {
        throw new InsufficientBalanceException();
    }
    
    $sender->decrement('balance', $data['amount'] + $data['commission_fee']);
    $receiver->increment('balance', $data['amount']);
    
    // Transaction commits, locks released
});
```

**How It Works:**

1. `lockForUpdate()` issues `SELECT ... FOR UPDATE` SQL query
2. Database places exclusive lock on selected rows
3. Other transactions wait until lock is released
4. Ensures sequential processing of concurrent transfers
5. Locks released on transaction commit/rollback

**Trade-offs:**

✅ **Pros:**
- Guaranteed data consistency
- Simple to implement
- No lost updates

❌ **Cons:**
- Reduced throughput (sequential processing)
- Potential deadlocks (mitigated by queue)
- Lock contention under high load

### Alternative Considered: Optimistic Locking

We chose pessimistic locking over optimistic locking because:
- Financial transactions require strict consistency
- Transfer frequency is moderate (rate-limited)
- Queue system already serializes transfers per user
- Simpler error handling

---

## Security

### Authentication

**Laravel Sanctum (Session-based)**

- Cookie-based authentication
- CSRF protection via double-submit cookie
- Session stored in database
- XSS protection via httpOnly cookies

**Flow:**

1. Client requests `/sanctum/csrf-cookie`
2. Server sets XSRF-TOKEN cookie
3. Client sends token in X-XSRF-TOKEN header
4. Server validates and creates session

### Authorization

**Policy-based Authorization**

```php
// Users can only transfer from their own account
$this->authorize('transfer', [Transaction::class, $request->sender_id]);

// Users can only view their own transactions
$this->authorize('viewAny', Transaction::class);
```

### Data Validation

**Form Request Validation**

```php
class TransferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sender_id' => ['required', 'integer', 'exists:users,id'],
            'receiver_id' => ['required', 'integer', 'exists:users,id', 'different:sender_id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'regex:/^\d+(\.\d{1,2})?$/'],
            'commission_fee' => ['required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
        ];
    }
}
```

### Rate Limiting

**Throttle Middleware**

```php
Route::post('/transactions', [TransactionController::class, 'store'])
    ->middleware('throttle:3,1'); // 3 requests per minute
```

### SQL Injection Prevention

- Eloquent ORM uses parameterized queries
- Never raw SQL without bindings

### XSS Prevention

- Vue.js automatic template escaping
- Content Security Policy headers (production)

### CSRF Protection

- Laravel Sanctum double-submit cookie
- X-XSRF-TOKEN header validation

---

## Performance Optimizations

### Database Optimizations

1. **Eager Loading**
   ```php
   Transaction::with('sender', 'receiver')->get();
   ```

2. **Indexes**
   - Primary keys
   - Foreign keys
   - Frequently queried columns (created_at)

3. **Pagination**
   ```php
   Transaction::paginate(20);
   ```

### Caching

**Redis Caching Strategy**

- Cache frequently accessed data
- Cache invalidation on updates
- Session storage in Redis

### Queue System

**Asynchronous Processing**

- Transfers processed in background
- Non-blocking user experience
- Retry failed jobs automatically

**Horizon Monitoring**

- Real-time queue monitoring
- Failed job management
- Performance metrics

### Frontend Optimizations

1. **Code Splitting** (Vite)
2. **Tree Shaking** (Vite + Tailwind)
3. **Lazy Loading** (Vue Router)
4. **Debouncing** (User input)

---

## Testing Strategy

### Test Pyramid

```
        ┌─────────────┐
        │   E2E Tests │ (Few)
        └─────────────┘
       ┌──────────────────┐
       │  Feature Tests   │ (Some)
       └──────────────────┘
    ┌──────────────────────────┐
    │      Unit Tests          │ (Many)
    └──────────────────────────┘
```

### Test Types

**Unit Tests**
- Service layer logic
- Model methods
- Utility functions

**Feature Tests**
- API endpoints
- Authentication flow
- Authorization rules

**Concurrency Tests**
- Race condition handling
- Pessimistic locking
- Parallel transfers

**Performance Tests**
- Bulk operations
- Query optimization
- Response times

### Test Examples

```php
// Unit Test
test('transfer deducts balance from sender', function () {
    $sender = User::factory()->create(['balance' => 1000]);
    $receiver = User::factory()->create(['balance' => 500]);
    
    $service = new TransactionService();
    $service->transfer([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 100,
        'commission_fee' => 1,
    ]);
    
    expect($sender->fresh()->balance)->toBe(899.00);
});

// Feature Test
test('authenticated user can create transfer', function () {
    $user = User::factory()->create(['balance' => 1000]);
    
    actingAs($user)
        ->post('/api/transactions', [
            'sender_id' => $user->id,
            'receiver_id' => User::factory()->create()->id,
            'amount' => 100,
            'commission_fee' => 1,
        ])
        ->assertOk()
        ->assertJson(['message' => 'Transaction processing']);
});
```

---

## Deployment Considerations

### Environment Setup

**Production Requirements:**

- PHP 8.2+ with required extensions
- Web server (Nginx/Apache)
- Database server (MySQL/PostgreSQL)
- Redis server
- Queue worker (Supervisor/Systemd)
- SSL certificate (HTTPS)

### Queue Workers

**Supervisor Configuration:**

```ini
[program:mini-wallet-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
```

### Caching

```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Monitoring

- **Horizon**: Queue monitoring
- **Laravel Telescope**: Debugging (dev only)
- **Application logs**: storage/logs/laravel.log
- **Web server logs**: Nginx/Apache logs

---

## Scalability Considerations

### Horizontal Scaling

**Application Servers:**
- Load balancer (Nginx/HAProxy)
- Multiple Laravel instances
- Shared session storage (Redis)

**Queue Workers:**
- Multiple worker processes
- Distributed across servers
- Horizon for monitoring

### Database Scaling

**Read Replicas:**
- Separate read/write connections
- Read queries to replicas
- Write queries to primary

**Connection Pooling:**
- PgBouncer (PostgreSQL)
- ProxySQL (MySQL)

### Caching Strategy

**Multi-level Caching:**
1. Application cache (Redis)
2. Query caching
3. CDN for static assets

---

## References

- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Laravel Horizon](https://laravel.com/docs/horizon)
- [Pinia Documentation](https://pinia.vuejs.org)

