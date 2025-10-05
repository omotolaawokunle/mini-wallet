# Architecture Decision Records

This directory contains Architecture Decision Records (ADRs) for the Mini Wallet project.

## What is an ADR?

An Architecture Decision Record (ADR) is a document that captures an important architectural decision made along with its context and consequences.

## Format

Each ADR follows this structure:

- **Status**: Proposed | Accepted | Deprecated | Superseded
- **Date**: When the decision was made
- **Context**: What is the issue we're addressing?
- **Decision**: What is the change we're proposing and/or doing?
- **Rationale**: Why did we choose this approach?
- **Consequences**: What are the positive and negative consequences?
- **Alternatives**: What other options were considered?

## Index

| ADR | Title | Status | Date |
|-----|-------|--------|------|
| [001](./001-use-session-based-authentication.md) | Use Session-Based Authentication with Laravel Sanctum | Accepted | 2025-10-05 |
| [002](./002-use-pessimistic-locking-for-transfers.md) | Use Pessimistic Locking for Transfer Concurrency Control | Accepted | 2025-10-05 |
| [003](./003-use-queue-for-async-processing.md) | Use Queue System for Asynchronous Transfer Processing | Accepted | 2025-10-05 |
| [004](./004-use-tailwind-v4-for-styling.md) | Use Tailwind CSS v4 for Frontend Styling | Accepted | 2025-10-05 |
| [005](./005-use-scheduled-balance-verification.md) | Use Scheduled Balance Verification for Data Integrity | Accepted | 2025-10-05 |

## Key Decisions Summary

### Authentication
- **Decision**: Session-based authentication with Laravel Sanctum
- **Rationale**: Better security (HttpOnly cookies), built-in CSRF protection, simpler for SPA

### Concurrency Control
- **Decision**: Pessimistic locking (SELECT FOR UPDATE)
- **Rationale**: Guaranteed consistency for financial transactions, simple implementation

### Async Processing
- **Decision**: Queue system with Laravel Horizon
- **Rationale**: Fast API responses, better scalability, automatic retries, monitoring

### Frontend Styling
- **Decision**: Tailwind CSS v4
- **Rationale**: Rapid development, small bundle size, CSS-first configuration, modern approach

### Balance Integrity
- **Decision**: Scheduled balance verification every 12 hours
- **Rationale**: Proactive discrepancy detection, automated flagging, non-intrusive to normal operations

## Process

### Creating a New ADR

1. Copy the template below
2. Create a new file: `NNN-short-title.md` (sequential number)
3. Fill in all sections
4. Submit for review
5. Update this README with the new ADR

### ADR Template

```markdown
# ADR NNN: [Title]

## Status

[Proposed | Accepted | Deprecated | Superseded by ADR-XXX]

## Date

YYYY-MM-DD

## Context

[Describe the issue motivating this decision and any context that influences or constrains the decision.]

## Decision

[State the decision that was made.]

## Rationale

[Explain why this decision was chosen over alternatives.]

## Consequences

### Positive

- [Benefit 1]
- [Benefit 2]

### Negative

- [Drawback 1]
- [Drawback 2]

### Neutral

- [Neutral point 1]

## Alternatives Considered

### Option 1: [Alternative Name]

**Pros:**
- [Pro 1]

**Cons:**
- [Con 1]

**Verdict:** [Why rejected]

## Implementation

[Code examples, configuration, or steps to implement]

## References

- [Link 1]
- [Link 2]
```

## References

- [ADR Documentation](https://adr.github.io/)
- [Documenting Architecture Decisions](https://cognitect.com/blog/2011/11/15/documenting-architecture-decisions)
- [ADR Tools](https://github.com/npryce/adr-tools)

