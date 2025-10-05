# ADR 001: Use Session-Based Authentication with Laravel Sanctum

## Status

Accepted

## Date

2025-10-05

## Context

We need to implement authentication for the Mini Wallet application. The system requires:

- Secure user authentication
- Protection against CSRF attacks
- Support for SPA (Single Page Application) frontend
- Simple integration with Laravel and Vue.js
- No need for mobile app support initially

We considered the following options:

### Option 1: Token-Based Authentication (Bearer Tokens)

**Pros:**
- Stateless
- Works well for mobile apps
- Easy to scale horizontally
- No server-side session storage

**Cons:**
- More complex token refresh mechanism
- Need to store tokens securely on client
- XSS vulnerabilities if stored in localStorage
- No automatic CSRF protection
- Token revocation requires blacklist

### Option 2: Session-Based Authentication with Laravel Sanctum

**Pros:**
- Built-in CSRF protection
- HttpOnly cookies (XSS protection)
- Automatic session management
- Works seamlessly with Laravel
- Easy to implement
- Simpler logout mechanism

**Cons:**
- Requires cookie support
- Server-side session storage
- Same-domain or CORS setup needed

### Option 3: OAuth 2.0 / Passport

**Pros:**
- Industry standard
- Third-party authentication support
- Fine-grained token scopes

**Cons:**
- Overcomplicated for our use case
- More setup and maintenance
- Heavier package

## Decision

We chose **Session-Based Authentication with Laravel Sanctum** (Option 2).

## Rationale

1. **Security First**: HttpOnly cookies provide better XSS protection than localStorage tokens
2. **CSRF Protection**: Built-in double-submit cookie pattern
3. **Simplicity**: Less code to maintain, Laravel handles most complexity
4. **SPA Support**: Sanctum is specifically designed for SPAs with same-domain APIs
5. **No Mobile Requirement**: We don't need token-based auth for mobile apps yet
6. **Developer Experience**: Faster development, well-documented, community support

## Implementation Details

### Backend Configuration

```php
// config/sanctum.php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 
    'localhost:5173,127.0.0.1:5173'
)),
```

### Frontend Setup

```javascript
// Axios configuration
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

// Authentication flow
await axios.get('/sanctum/csrf-cookie');
await axios.post('/login', credentials);
```

### API Routes

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    // ...
});
```

## Consequences

### Positive

- ✅ Enhanced security with HttpOnly cookies
- ✅ Automatic CSRF protection
- ✅ Simplified authentication flow
- ✅ Better developer experience
- ✅ Seamless Laravel integration
- ✅ Easy to test and debug

### Negative

- ❌ Requires CORS configuration for different domains
- ❌ Session storage overhead (mitigated by Redis)
- ❌ Cookie-based, requires browser support
- ❌ Need to migrate if mobile app required later

### Neutral

- 🔸 Frontend and backend must be on same domain or configured with CORS
- 🔸 Sessions stored in database (or Redis for performance)

## Alternatives Considered

If we need mobile app support in the future, we can:

1. Add token-based authentication alongside session-based
2. Use Sanctum's token abilities for mobile
3. Maintain both authentication methods

## References

- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [SPA Authentication Guide](https://laravel.com/docs/sanctum#spa-authentication)
- [OWASP Session Management](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)

