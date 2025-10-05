# API Documentation

## Base URL

```
Development: http://localhost:8000
Production: Your production URL
```

## Authentication

This API uses **Laravel Sanctum** for session-based authentication. All authenticated endpoints require:

1. CSRF token in headers (obtained from `/sanctum/csrf-cookie`)
2. Session cookie (automatically set after login)

### Authentication Flow

1. **Get CSRF Token**: `GET /sanctum/csrf-cookie`
2. **Login/Register**: `POST /login` or `POST /register`
3. **Make Authenticated Requests**: Include cookies and CSRF token

### Headers Required

```http
Accept: application/json
Content-Type: application/json
X-XSRF-TOKEN: <csrf-token>
Cookie: <session-cookie>
```

## Response Format

All API responses follow a consistent format:

### Success Response

```json
{
  "status": true,
  "message": "Success message",
  "data": {
    // Response data
  }
}
```

### Error Response

```json
{
  "status": false,
  "message": "Error message",
  "errors": {
    "field": ["Error detail"]
  }
}
```

### Pagination Response

```json
{
  "status": true,
  "message": "Success message",
  "data": [
    // Array of items
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 20,
    "to": 20,
    "total": 100
  },
  "links": {
    "first": "http://localhost:8000/api/transactions?page=1",
    "last": "http://localhost:8000/api/transactions?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/transactions?page=2"
  }
}
```

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Internal Server Error |

---

## Endpoints

### Authentication

#### Register User

Create a new user account.

**Endpoint:** `POST /register`

**Authentication:** Not required

**Request Body:**

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Validation Rules:**

- `name`: Required, string, max 255 characters
- `email`: Required, valid email, unique in users table
- `password`: Required, string, min 8 characters, confirmed
- `password_confirmation`: Required, must match password

**Success Response (200):**

```json
{
  "status": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "balance": "0.00",
      "is_flagged": false,
      "flagged_at": null,
      "flagged_reason": null,
      "created_at": "2025-10-05T10:30:00.000000Z"
    }
  }
}
```

**Error Response (422):**

```json
{
  "status": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

---

#### Login User

Authenticate a user and create a session.

**Endpoint:** `POST /login`

**Authentication:** Not required

**Request Body:**

```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Validation Rules:**

- `email`: Required, valid email
- `password`: Required, string

**Success Response (200):**

```json
{
  "status": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "balance": "1000.00",
      "created_at": "2025-10-05T10:30:00.000000Z"
    }
  }
}
```

**Error Response (401):**

```json
{
  "status": false,
  "message": "Invalid credentials"
}
```

---

#### Logout User

End the current user session.

**Endpoint:** `POST /logout`

**Authentication:** Required

**Request Body:** None

**Success Response (200):**

```json
{
  "status": true,
  "message": "Logout successful"
}
```

---

#### Get Current User

Retrieve authenticated user information.

**Endpoint:** `GET /user`

**Authentication:** Required

**Success Response (200):**

```json
{
  "status": true,
  "message": "User retrieved",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "balance": "1000.00",
      "created_at": "2025-10-05T10:30:00.000000Z"
    }
  }
}
```

**Error Response (401):**

```json
{
  "status": false,
  "message": "Unauthenticated"
}
```

---

### Transactions

#### List Transactions

Retrieve paginated transaction history for the authenticated user.

**Endpoint:** `GET /api/transactions`

**Authentication:** Required

**Query Parameters:**

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| page | integer | Page number | 1 |
| per_page | integer | Items per page | 20 |

**Example Request:**

```
GET /api/transactions?page=1
```

**Success Response (200):**

```json
{
  "status": true,
  "message": "Transactions retrieved successfully",
  "data": [
    {
      "id": 1,
      "sender_id": 1,
      "receiver_id": 2,
      "amount": "100.00",
      "commission_fee": "1.00",
      "created_at": "2025-10-05T10:30:00.000000Z",
      "sender": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "balance": "900.00"
      },
      "receiver": {
        "id": 2,
        "name": "Jane Smith",
        "email": "jane@example.com",
        "balance": "1100.00"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 20,
    "to": 20,
    "total": 100
  },
  "links": {
    "first": "http://localhost:8000/api/transactions?page=1",
    "last": "http://localhost:8000/api/transactions?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/transactions?page=2"
  }
}
```

**Authorization:**

Users can only view transactions where they are either the sender or receiver.

---

#### Create Transfer

Create a new money transfer between users.

**Endpoint:** `POST /api/transactions`

**Authentication:** Required

**Rate Limit:** 3 requests per minute

**Request Body:**

```json
{
  "sender_id": 1,
  "receiver_id": 2,
  "amount": 100.50,
  "commission_fee": 1.00
}
```

**Validation Rules:**

- `sender_id`: Required, integer, must exist in users table, must be authenticated user
- `receiver_id`: Required, integer, must exist in users table, must be different from sender
- `amount`: Required, numeric, must be greater than 0, max 2 decimal places
- `commission_fee`: Required, numeric, must be 0 or greater, max 2 decimal places

**Success Response (200):**

```json
{
  "status": true,
  "message": "Transaction processing"
}
```

**Note:** The transaction is processed asynchronously via queue. The user will receive real-time updates via WebSocket when the transaction completes.

**Error Responses:**

**Validation Error (422):**

```json
{
  "status": false,
  "message": "The given data was invalid.",
  "errors": {
    "amount": ["The amount must be greater than 0."],
    "receiver_id": ["The receiver must be different from the sender."]
  }
}
```

**Insufficient Balance (422):**

```json
{
  "status": false,
  "message": "Sender does not have enough balance"
}
```

**Unauthorized (403):**

```json
{
  "status": false,
  "message": "You are not authorized to perform this transfer."
}
```

**Rate Limit Exceeded (429):**

```json
{
  "status": false,
  "message": "Too Many Attempts."
}
```

**Authorization:**

Users can only create transfers where they are the sender.

---

#### Validate Receiver

Validate if a receiver exists and can receive transfers.

**Endpoint:** `POST /api/validate-receiver`

**Authentication:** Required

**Request Body:**

```json
{
  "receiver_id": 2
}
```

**Success Response (200):**

```json
{
  "status": true,
  "message": "Receiver found",
  "data": {
    "id": 2,
    "name": "Jane Smith",
    "email": "jane@example.com",
    "balance": "1000.00",
    "created_at": "2025-10-05T10:30:00.000000Z"
  }
}
```

**Error Response (422):**

```json
{
  "status": false,
  "message": "Receiver not found",
  "errors": {
    "receiver_id": "Receiver not found"
  }
}
```

---

## WebSocket Events

The application uses **Laravel Echo** with **Pusher** for real-time notifications.

### Configuration

**Frontend (Vue.js):**

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  wsHost: import.meta.env.VITE_PUSHER_HOST,
  wsPort: import.meta.env.VITE_PUSHER_PORT,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
  forceTLS: false,
  encrypted: false,
  enabledTransports: ['ws', 'wss'],
});
```

### Events

#### Transaction Created

**Event:** `TransactionCreated`

**Channel:** `private-user.{userId}`

**Payload:**

```json
{
  "transaction": {
    "id": 1,
    "sender_id": 1,
    "receiver_id": 2,
    "amount": "100.00",
    "commission_fee": "1.00",
    "created_at": "2025-10-05T10:30:00.000000Z"
  },
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "balance": "900.00"
  },
  "type": "sent" // or "received"
}
```

**Listen for Event:**

```javascript
Echo.private(`user.${userId}`)
  .listen('TransactionCreated', (event) => {
    console.log('Transaction:', event.transaction);
    console.log('Updated balance:', event.user.balance);
    console.log('Type:', event.type); // "sent" or "received"
  });
```

---

#### Transfer Failed

**Event:** `TransferFailed`

**Channel:** `private-user.{userId}`

**Payload:**

```json
{
  "message": "Sender does not have enough balance",
  "data": {
    "sender_id": 1,
    "receiver_id": 2,
    "amount": "100.00",
    "commission_fee": "1.00"
  }
}
```

**Listen for Event:**

```javascript
Echo.private(`user.${userId}`)
  .listen('TransferFailed', (event) => {
    console.error('Transfer failed:', event.message);
    console.log('Failed data:', event.data);
  });
```

---

## Example API Calls

### Using JavaScript (Axios)

#### Setup Axios

```javascript
import axios from 'axios';

axios.defaults.baseURL = 'http://localhost:8000';
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['Content-Type'] = 'application/json';
```

#### Register

```javascript
// Get CSRF token first
await axios.get('/sanctum/csrf-cookie');

// Register
const response = await axios.post('/register', {
  name: 'John Doe',
  email: 'john@example.com',
  password: 'password123',
  password_confirmation: 'password123'
});

console.log(response.data);
```

#### Login

```javascript
// Get CSRF token first
await axios.get('/sanctum/csrf-cookie');

// Login
const response = await axios.post('/login', {
  email: 'john@example.com',
  password: 'password123'
});

console.log(response.data);
```

#### Get Transactions

```javascript
const response = await axios.get('/api/transactions', {
  params: { page: 1 }
});

console.log(response.data);
```

#### Create Transfer

```javascript
const response = await axios.post('/api/transactions', {
  sender_id: 1,
  receiver_id: 2,
  amount: 100.50,
  commission_fee: 1.00
});

console.log(response.data);
```

---

### Using cURL

#### Register

```bash
# Get CSRF token
curl -X GET http://localhost:8000/sanctum/csrf-cookie \
  -c cookies.txt

# Register
curl -X POST http://localhost:8000/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -c cookies.txt \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

#### Login

```bash
# Get CSRF token
curl -X GET http://localhost:8000/sanctum/csrf-cookie \
  -c cookies.txt

# Login
curl -X POST http://localhost:8000/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -c cookies.txt \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

#### Get Transactions

```bash
curl -X GET http://localhost:8000/api/transactions \
  -H "Accept: application/json" \
  -b cookies.txt
```

#### Create Transfer

```bash
curl -X POST http://localhost:8000/api/transactions \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "sender_id": 1,
    "receiver_id": 2,
    "amount": 100.50,
    "commission_fee": 1.00
  }'
```

---

## Error Handling

### Common Errors

#### 401 Unauthorized

Occurs when:
- User is not authenticated
- Session has expired
- CSRF token is invalid

**Solution:** Login again and get a new CSRF token.

#### 403 Forbidden

Occurs when:
- User is authenticated but not authorized to perform the action
- Trying to transfer from another user's account
- User account has been flagged due to balance discrepancy

**Solution:** Ensure you're only transferring from your own account. If your account is flagged, contact support to resolve the balance discrepancy.

#### 422 Validation Error

Occurs when:
- Request data is invalid
- Required fields are missing
- Data types are incorrect

**Solution:** Check validation rules and fix request data.

#### 429 Rate Limit Exceeded

Occurs when:
- Too many transfer requests (3 per minute)

**Solution:** Wait before making another request.

#### 500 Internal Server Error

Occurs when:
- Server error
- Database connection issues

**Solution:** Check server logs and contact support.

---

## Rate Limiting

### Transfer Endpoint

**Limit:** 3 requests per minute per user

**Headers:**

```http
X-RateLimit-Limit: 3
X-RateLimit-Remaining: 2
```

**When Exceeded:**

```json
{
  "status": false,
  "message": "Too Many Attempts."
}
```

---

## User Account Flags

### Flagged Accounts

Users may be flagged if a balance discrepancy is detected during the automated verification process (runs every 12 hours).

**User Fields:**

```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "balance": "1000.00",
  "is_flagged": true,
  "flagged_at": "2025-10-05T14:30:00.000000Z",
  "flagged_reason": "Balance mismatch detected. Expected: $950.00, Actual: $1000.00, Discrepancy: $50.00"
}
```

**Behavior:**
- Flagged users cannot make or receive transfers
- Transfer attempts will return a 403 error with the flagged reason
- Users remain flagged until an administrator resolves the discrepancy
- The system automatically recalculates balances every 12 hours and will unflag accounts if the balance is corrected

**Error Response (403):**

```json
{
  "status": false,
  "message": "Your account has been flagged due to balance discrepancy. Please contact support."
}
```

---

## Best Practices

1. **Always get CSRF token** before making authenticated requests
2. **Handle errors gracefully** with proper error messages
3. **Implement retry logic** for rate-limited requests
4. **Use WebSockets** for real-time updates
5. **Validate data** on the client side before submitting
6. **Store sensitive data securely** (never in localStorage)
7. **Implement proper logout** to clear sessions
8. **Use HTTPS** in production
9. **Check user flagged status** before allowing transfers in UI

---

## Testing

### Postman Collection

Import the following environment variables:

```json
{
  "base_url": "http://localhost:8000",
  "csrf_token": "{{csrf_token}}",
  "user_id": "1"
}
```

### Test Credentials

If you've run the database seeder:

```
Email: Any of the 50 generated users
Password: password (default for all seeded users)
```

To find seeded users:

```bash
php artisan tinker
>>> User::all(['id', 'name', 'email', 'balance']);
```

---

## Support

For issues or questions:
- Open an issue on GitHub
- Check Laravel documentation: https://laravel.com/docs
- Check Vue.js documentation: https://vuejs.org

