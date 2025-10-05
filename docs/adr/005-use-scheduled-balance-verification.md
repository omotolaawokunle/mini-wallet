# ADR-005: Use Scheduled Balance Verification for Data Integrity

**Status:** Accepted

**Date:** 2025-10-05

**Context:**

In a financial application like Mini Wallet, maintaining accurate balance data is critical. Due to various factors (bugs, concurrent operations, system failures, manual database operations), balance discrepancies can occur. We need a reliable mechanism to detect and flag such discrepancies to maintain data integrity and user trust.

## Decision

We will implement an automated scheduled balance verification system that:

1. Runs every 12 hours (1:00 AM and 1:00 PM)
2. Recalculates each user's balance from their transaction history
3. Compares calculated balance with stored balance
4. Flags users with discrepancies greater than $0.01
5. Prevents flagged users from making transactions
6. Sends email notifications to administrators with flagged user details
7. Automatically unflags accounts when balances are corrected

## Technical Implementation

### Components

1. **Console Command:** `wallet:verify-balances`
   - Scheduled via Laravel's task scheduler
   - Fetches all active users
   - Dispatches verification jobs to queue

2. **Job:** `VerifyUserBalance`
   - Processes individual user verification
   - Calculates expected balance from transactions
   - Flags/unflags users based on comparison
   - Logs discrepancies

3. **Job:** `SendBalanceDiscrepancyReport`
   - Runs after all verifications complete (via job chain)
   - Collects flagged users
   - Sends comprehensive email report to admin

4. **Exception:** `AccountFlaggedException`
   - Thrown when flagged users attempt transactions
   - Contains user-friendly error message

5. **Database Fields:**
   - `users.flagged_at`: Timestamp of when account was flagged
   - `users.flagged_reason`: Detailed reason for flagging
   - `users.is_flagged`: Accessor computed from flagged_at

### Balance Calculation

```php
Expected Balance = Sum(Incoming Transactions) - Sum(Outgoing Transactions + Fees)
```

### Job Chain Pattern

We use Laravel's `Bus::chain()` to ensure the admin notification job runs only after all user verifications are complete:

```php
Bus::chain([
    new VerifyUserBalance($user1->id),
    new VerifyUserBalance($user2->id),
    // ... all users
    new SendBalanceDiscrepancyReport(),
])->dispatch();
```

## Alternatives Considered

### 1. Real-time Balance Verification

**Approach:** Verify balance after every transaction

**Pros:**
- Immediate detection of discrepancies
- Faster response to issues

**Cons:**
- Performance overhead on every transaction
- Increased latency for users
- May slow down high-frequency operations
- More complex error handling

**Rejected because:** The performance cost outweighs the benefit. 12-hour intervals provide adequate detection while maintaining system performance.

### 2. Event Sourcing

**Approach:** Store only transactions and calculate balances dynamically

**Pros:**
- Single source of truth
- No balance discrepancies possible
- Full audit trail

**Cons:**
- Significant architectural change
- Increased query complexity
- Performance overhead on balance reads
- Requires complete system redesign

**Rejected because:** Too large a change for the current system architecture. Would require major refactoring.

### 3. Database Triggers

**Approach:** Use database triggers to maintain balance consistency

**Pros:**
- Enforced at database level
- Automatic consistency
- No application code needed

**Cons:**
- Database-specific logic
- Harder to test and debug
- Reduced portability
- Complex trigger management

**Rejected because:** Makes the system more tightly coupled to specific database features and harder to maintain.

### 4. Optimistic Locking with Versioning

**Approach:** Add version column and use optimistic locking

**Pros:**
- Good for high-concurrency scenarios
- No blocking

**Cons:**
- More complex error handling
- Retry logic needed
- Version conflicts in high-traffic
- Doesn't detect external balance modifications

**Rejected because:** We already use pessimistic locking for transactions. This doesn't solve the problem of detecting existing discrepancies.

## Consequences

### Positive

1. **Data Integrity:** Proactive detection of balance discrepancies
2. **User Protection:** Prevents transactions from flagged accounts
3. **Admin Visibility:** Clear notifications of issues
4. **Automated Resolution:** Auto-unflag when balances are corrected
5. **Non-intrusive:** Doesn't affect normal transaction flow
6. **Audit Trail:** Full logging of discrepancies
7. **Flexible Scheduling:** Can be run manually or scheduled

### Negative

1. **Detection Delay:** Up to 12 hours before discrepancy is detected
2. **Queue Dependency:** Requires queue workers to be running
3. **Email Dependency:** Requires mail configuration
4. **Resource Usage:** Bulk processing every 12 hours
5. **User Impact:** Flagged users cannot transact until resolved

### Mitigation Strategies

1. **Detection Delay:** 12-hour interval is acceptable for balance verification. Critical issues would be caught by transaction validation
2. **Queue Dependency:** Document queue worker requirements in deployment guide
3. **Email Dependency:** Log all discrepancies even if email fails
4. **Resource Usage:** Jobs are queued to avoid overwhelming the system
5. **User Impact:** Clear messaging to users about why account is flagged

## Implementation Details

### Schedule Configuration

```php
// routes/console.php
Schedule::command('wallet:verify-balances')
    ->twiceDaily(1, 13) // 1:00 AM and 1:00 PM
    ->description('Verify user balances for data integrity');
```

### Tolerance Threshold

We use a $0.01 tolerance to account for floating-point precision issues:

```php
if (abs($expectedBalance - $actualBalance) > 0.01) {
    // Flag user
}
```

### Frontend Integration

- Display alert banner for flagged users
- Disable transfer form
- Show flagged reason
- Provide support contact information

## Monitoring and Observability

1. **Logs:** All verifications and discrepancies logged
2. **Email Reports:** Admin receives detailed reports
3. **Metrics:** Track number of flagged accounts over time
4. **Manual Trigger:** Command can be run manually for testing

## Future Enhancements

1. Add dashboard for admins to view flagged accounts
2. Implement automated balance correction for small discrepancies
3. Add metrics and alerting for high discrepancy counts
4. Create API endpoints for admin to manually flag/unflag accounts
5. Add balance correction history tracking
6. Implement different verification intervals per environment

## References

- [ADR-002: Pessimistic Locking](./002-use-pessimistic-locking-for-transfers.md)
- [ADR-003: Queue for Async Processing](./003-use-queue-for-async-processing.md)
- [Laravel Task Scheduling](https://laravel.com/docs/scheduling)
- [Laravel Queue](https://laravel.com/docs/queues)
- [Laravel Mail](https://laravel.com/docs/mail)

## Authors

- Development Team

## Notes

This ADR should be reviewed if:
- Balance discrepancies become frequent
- Performance issues arise from verification process
- Business requirements change for detection intervals
- New payment methods or transaction types are added

