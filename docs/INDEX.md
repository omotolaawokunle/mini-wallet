# Mini Wallet Documentation Index

Welcome to the Mini Wallet documentation! This index will help you find the information you need.

## 📚 Documentation Structure

```
docs/
├── INDEX.md (this file)
├── API_DOCUMENTATION.md
├── ARCHITECTURE.md
└── adr/
    ├── README.md
    ├── 001-use-session-based-authentication.md
    ├── 002-use-pessimistic-locking-for-transfers.md
    ├── 003-use-queue-for-async-processing.md
    └── 004-use-tailwind-v4-for-styling.md
```

## 🚀 Getting Started

### New to the Project?

1. **[README.md](../README.md)** - Start here for project overview and setup
2. **[Installation Guide](../README.md#installation)** - Step-by-step setup instructions
3. **[Running the Application](../README.md#running-the-application)** - How to start development

### Want to Use the API?

1. **[API Documentation](./API_DOCUMENTATION.md)** - Complete API reference
2. **[Authentication Flow](./API_DOCUMENTATION.md#authentication)** - How to authenticate
3. **[API Examples](./API_DOCUMENTATION.md#example-api-calls)** - Code samples

### Want to Understand the System?

1. **[Architecture Documentation](./ARCHITECTURE.md)** - System design and patterns
2. **[Architecture Decision Records](./adr/)** - Why we made key decisions
3. **[Database Design](./ARCHITECTURE.md#database-design)** - Entity relationships

## 📖 Documentation by Topic

### Installation & Setup

| Document | Section | Description |
|----------|---------|-------------|
| [README.md](../README.md#installation) | Installation | Full installation guide |
| [README.md](../README.md#configuration) | Configuration | Environment setup |
| [README.md](../README.md#running-the-application) | Running | Starting services |

### API Reference

| Document | Section | Description |
|----------|---------|-------------|
| [API_DOCUMENTATION.md](./API_DOCUMENTATION.md#authentication) | Authentication | Login, register, logout |
| [API_DOCUMENTATION.md](./API_DOCUMENTATION.md#transactions) | Transactions | List, create transfers |
| [API_DOCUMENTATION.md](./API_DOCUMENTATION.md#websocket-events) | WebSocket | Real-time events |
| [API_DOCUMENTATION.md](./API_DOCUMENTATION.md#error-handling) | Errors | Error codes and handling |

### Architecture & Design

| Document | Section | Description |
|----------|---------|-------------|
| [ARCHITECTURE.md](./ARCHITECTURE.md#system-architecture) | Overview | High-level architecture |
| [ARCHITECTURE.md](./ARCHITECTURE.md#design-patterns) | Patterns | Design patterns used |
| [ARCHITECTURE.md](./ARCHITECTURE.md#database-design) | Database | ER diagrams and schemas |
| [ARCHITECTURE.md](./ARCHITECTURE.md#concurrency-control) | Concurrency | Race condition prevention |
| [ARCHITECTURE.md](./ARCHITECTURE.md#security) | Security | Security measures |

### Architecture Decisions

| Document | Topic | Status |
|----------|-------|--------|
| [ADR-001](./adr/001-use-session-based-authentication.md) | Session Authentication | Accepted |
| [ADR-002](./adr/002-use-pessimistic-locking-for-transfers.md) | Pessimistic Locking | Accepted |
| [ADR-003](./adr/003-use-queue-for-async-processing.md) | Queue System | Accepted |
| [ADR-004](./adr/004-use-tailwind-v4-for-styling.md) | Tailwind CSS v4 | Accepted |
| [ADR-005](./adr/005-use-scheduled-balance-verification.md) | Balance Verification | Accepted |


## 🎯 Quick Reference

### Common Tasks

| Task | Documentation |
|------|---------------|
| Install and run | [README → Installation](../README.md#installation) |
| Make API calls | [API Docs → Examples](./API_DOCUMENTATION.md#example-api-calls) |
| Understand transfers | [Architecture → Flow](./ARCHITECTURE.md#application-flow) | 
| Debug issues | [README → Debugging](../README.md#debugging) |

### Technology Stack

| Technology | Version | Documentation Link |
|------------|---------|-------------------|
| Laravel | 12.x | [Laravel Docs](https://laravel.com/docs) |
| Vue.js | 3.5.x | [Vue Docs](https://vuejs.org) |
| Tailwind CSS | 4.x | [Tailwind Docs](https://tailwindcss.com) |
| Pinia | 3.x | [Pinia Docs](https://pinia.vuejs.org) |
| Laravel Sanctum | 4.x | [Sanctum Docs](https://laravel.com/docs/sanctum) |
| Laravel Horizon | 5.x | [Horizon Docs](https://laravel.com/docs/horizon) |

### Key Concepts

| Concept | Explained In | Key Points |
|---------|--------------|------------|
| Authentication | [ADR-001](./adr/001-use-session-based-authentication.md) | Session-based, Sanctum, CSRF |
| Concurrency | [ADR-002](./adr/002-use-pessimistic-locking-for-transfers.md) | Pessimistic locking, transactions |
| Async Processing | [ADR-003](./adr/003-use-queue-for-async-processing.md) | Queue jobs, Horizon |
| Styling | [ADR-004](./adr/004-use-tailwind-v4-for-styling.md) | Tailwind utilities, responsive |

## 🔍 Find What You Need

### I want to...

#### ...understand how authentication works
→ [ADR-001: Session Authentication](./adr/001-use-session-based-authentication.md)  
→ [API Docs: Authentication](./API_DOCUMENTATION.md#authentication)

#### ...make a money transfer
→ [API Docs: Create Transfer](./API_DOCUMENTATION.md#create-transfer)  
→ [Architecture: Transfer Flow](./ARCHITECTURE.md#application-flow)

#### ...prevent race conditions
→ [ADR-002: Pessimistic Locking](./adr/002-use-pessimistic-locking-for-transfers.md)  
→ [Architecture: Concurrency Control](./ARCHITECTURE.md#concurrency-control)

#### ...use WebSocket notifications
→ [API Docs: WebSocket Events](./API_DOCUMENTATION.md#websocket-events)  
→ [Architecture: Event Broadcasting](./ARCHITECTURE.md#design-patterns)

#### ...deploy to production
→ [Architecture: Deployment](./ARCHITECTURE.md#deployment-considerations)  
→ [README: Production Build](../README.md#production-build)


#### ...write tests 
→ [README: Testing](../README.md#testing)

#### ...style components
→ [ADR-004: Tailwind CSS](./adr/004-use-tailwind-v4-for-styling.md)  

## 📋 Checklists

### New Developer Checklist

- [ ] Read [README.md](../README.md)
- [ ] Setup development environment
- [ ] Run application locally
- [ ] Make a test transfer via UI
- [ ] Make a test API call
- [ ] Read [Architecture Overview](./ARCHITECTURE.md)
- [ ] Review [ADRs](./adr/README.md)
- [ ] Run tests locally

### API Integration Checklist

- [ ] Read [API Documentation](./API_DOCUMENTATION.md)
- [ ] Understand [authentication flow](./API_DOCUMENTATION.md#authentication)
- [ ] Get CSRF token
- [ ] Implement login
- [ ] Make authenticated requests
- [ ] Handle errors properly
- [ ] Setup WebSocket listening
- [ ] Test rate limiting

