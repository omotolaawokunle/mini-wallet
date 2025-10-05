# Mini Wallet - Digital Wallet Transfer System

A modern, real-time digital wallet application built with Laravel 12 and Vue 3. This system enables users to transfer money between accounts with real-time balance updates, transaction history, and comprehensive security features.

## 🚀 Features

- **User Authentication**: Secure registration and login with Laravel Sanctum
- **Real-time Transfers**: Instant money transfers between users with WebSocket notifications
- **Transaction History**: Paginated transaction list with sender/receiver details
- **Balance Management**: Real-time balance updates with race condition protection
- **Commission Fees**: Configurable transaction fees
- **Queue System**: Asynchronous transaction processing with Laravel Horizon
- **Rate Limiting**: Transfer throttling (3 transfers per minute)
- **Concurrency Control**: Database-level pessimistic locking to prevent race conditions
- **Responsive UI**: Modern Vue 3 SPA with Tailwind CSS v4

## 📋 Table of Contents

- [System Requirements](#system-requirements)
- [Technology Stack](#technology-stack)
- [Installation](#installation)
- [Configuration](#configuration)
- [Running the Application](#running-the-application)
- [Testing](#testing)
- [API Documentation](#api-documentation)
- [Architecture](#architecture)
- [Project Structure](#project-structure)

## 💻 System Requirements

- **PHP**: ^8.2
- **Composer**: Latest stable version
- **Node.js**: ^18.x or ^20.x
- **NPM**: ^9.x or ^10.x
- **Database**: SQLite (default), MySQL 8.0+, PostgreSQL 13+, or MariaDB 10.6+
- **Redis**: ^6.2 (for queues and broadcasting)

## 🛠 Technology Stack

### Backend
- **Laravel**: 12.x
- **Laravel Sanctum**: 4.x (Session-based authentication)
- **Laravel Horizon**: 5.x (Queue management and monitoring)
- **Redis**: Queue driver and broadcaster
- **Pusher**: WebSocket broadcasting (via Pusher PHP Server)

### Frontend
- **Vue**: 3.5.x (Composition API)
- **Vue Router**: 4.x
- **Pinia**: 3.x (State management)
- **Axios**: 1.x (HTTP client)
- **Laravel Echo**: 2.x (WebSocket client)
- **Tailwind CSS**: 4.x
- **Vite**: 7.x (Build tool)
- **TypeScript**: 5.x

### Testing
- **Pest PHP**: 4.x
- **Mockery**: 1.x

### Development Tools
- **Laravel Pint**: Code formatting
- **Laravel Sail**: Docker environment (optional)

## 📦 Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd mini-wallet
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node Dependencies

```bash
npm install
```

### 4. Environment Configuration

Create a `.env` file:

```bash
cp .env.example .env
```

If `.env.example` doesn't exist, create `.env` with the following:

```env
APP_NAME="Mini Wallet"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file

# Frontend URL for CORS and Sanctum
FRONTEND_URL=http://localhost:5173

# Database Configuration
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=mini_wallet
# DB_USERNAME=root
# DB_PASSWORD=

# Redis Configuration (Required for queues and broadcasting)
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue Configuration
QUEUE_CONNECTION=redis

# Broadcasting Configuration
BROADCAST_CONNECTION=pusher

# Pusher Configuration
PUSHER_APP_ID=mini-wallet
PUSHER_APP_KEY=mini-wallet-key
PUSHER_APP_SECRET=mini-wallet-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1

# Vite Configuration
VITE_APP_NAME="${APP_NAME}"
VITE_API_URL="${APP_URL}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173,localhost:3000,127.0.0.1:3000

# Cache Configuration
CACHE_STORE=redis
CACHE_PREFIX=
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Create Database

For SQLite (default):
```bash
touch database/database.sqlite
```

For MySQL/PostgreSQL, create the database manually:
```bash
# MySQL
mysql -u root -p -e "CREATE DATABASE mini_wallet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# PostgreSQL
createdb mini_wallet
```

### 7. Run Migrations

```bash
php artisan migrate
```

### 8. Seed Database (Optional)

```bash
php artisan db:seed
```

This will create:
- 50 test users with random balances (500-10000)
- 100 sample transactions

## ⚙️ Configuration

### Redis Setup

**macOS (using Homebrew):**
```bash
brew install redis
brew services start redis
```

**Ubuntu/Debian:**
```bash
sudo apt-get install redis-server
sudo systemctl start redis-server
```

**Windows:**
Download and install from [Redis Windows](https://github.com/microsoftarchive/redis/releases)

### Pusher (WebSocket) Setup

This project uses Pusher PHP Server for local WebSocket development. No external Pusher account needed!

The configuration is already set in `.env`:
```env
BROADCAST_CONNECTION=pusher
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
```

### Horizon Configuration

Horizon is configured to use Redis. Dashboard is available at `/horizon` after starting the queue worker.

Access control is configured in `app/Providers/HorizonServiceProvider.php`.

## 🚀 Running the Application

### Development Mode (Recommended)

Run all services concurrently:

```bash
composer dev
```

This single command starts:
- Laravel development server (http://localhost:8000)
- Queue worker (Laravel Horizon)
- Log viewer (Laravel Pail)
- Vite dev server (http://localhost:5173)

### Manual Mode (Individual Services)

If you prefer to run services separately:

**Terminal 1 - Laravel Server:**
```bash
php artisan serve
```

**Terminal 2 - Queue Worker:**
```bash
php artisan queue:listen --tries=1
```

**Terminal 3 - Frontend:**
```bash
npm run dev
```

**Terminal 4 - Horizon (Optional, for queue monitoring):**
```bash
php artisan horizon
```

### Production Build

```bash
# Build frontend assets
npm run build

# Configure your web server (Nginx/Apache) to serve the public directory
# Ensure queue workers are running via supervisor or systemd
# Use Laravel Horizon for queue monitoring
```

## 🧪 Testing

The project uses Pest PHP for testing.

### Run All Tests

```bash
composer test
# or
php artisan test
```

### Run Specific Test Files

```bash
php artisan test --filter=TransactionServiceTest
php artisan test --filter=TransactionControllerTest
php artisan test --filter=ConcurrencyTest
php artisan test --filter=TransactionPerformanceTest
```

### Test Coverage

```bash
php artisan test --coverage
```

### Available Test Suites

- **Unit Tests**: Service layer testing
- **Feature Tests**: Controller and API endpoint testing
- **Concurrency Tests**: Race condition and parallel transaction testing
- **Performance Tests**: Bulk operation and load testing

## 📚 API Documentation

See [API_DOCUMENTATION.md](./docs/API_DOCUMENTATION.md) for detailed API reference.

### Quick Reference

#### Authentication Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/register` | Register new user | No |
| POST | `/login` | Login user | No |
| POST | `/logout` | Logout user | Yes |
| GET | `/user` | Get authenticated user | Yes |

#### Transaction Endpoints

| Method | Endpoint | Description | Auth Required | Rate Limit |
|--------|----------|-------------|---------------|------------|
| GET | `/api/transactions` | List user transactions | Yes | - |
| POST | `/api/transactions` | Create transfer | Yes | 3/minute |
| POST | `/api/validate-receiver` | Validate receiver | Yes | - |

## 🏗 Architecture

See [ARCHITECTURE.md](./docs/ARCHITECTURE.md) for detailed architecture documentation.

### High-Level Architecture

```
┌─────────────┐      HTTP/WS      ┌──────────────┐
│   Vue 3     │ ◄─────────────────► │   Laravel    │
│   Frontend  │                     │   Backend    │
└─────────────┘                     └──────────────┘
                                           │
                                           │
                    ┌──────────────────────┼──────────────────────┐
                    │                      │                      │
              ┌─────▼─────┐         ┌─────▼─────┐         ┌─────▼─────┐
              │  Database │         │   Redis   │         │  Pusher   │
              │  (SQLite) │         │  (Queue)  │         │   (WS)    │
              └───────────┘         └───────────┘         └───────────┘
```

### Key Design Patterns

- **Service Layer Pattern**: Business logic in `TransactionService`
- **Repository Pattern**: Data access abstraction
- **Job Queue Pattern**: Asynchronous transaction processing
- **Event Broadcasting**: Real-time notifications
- **Policy Pattern**: Authorization logic
- **Resource Pattern**: API response transformation

### Concurrency Control

The system implements **pessimistic locking** to prevent race conditions:

```php
// Lock user records before balance updates
$sender = User::lockForUpdate()->find($senderId);
$receiver = User::lockForUpdate()->find($receiverId);
```

This ensures that concurrent transfers don't cause balance inconsistencies.

## 📁 Project Structure

```
mini-wallet/
├── app/
│   ├── Events/               # Event classes
│   │   ├── TransactionCreated.php
│   │   └── TransferFailed.php
│   ├── Exceptions/           # Custom exceptions
│   │   ├── InsufficientBalanceException.php
│   │   └── TransferException.php
│   ├── Http/
│   │   ├── Controllers/      # API controllers
│   │   │   ├── AuthController.php
│   │   │   ├── TransactionController.php
│   │   │   └── ValidateReceiver.php
│   │   ├── Requests/         # Form request validation
│   │   │   ├── LoginRequest.php
│   │   │   ├── RegisterRequest.php
│   │   │   └── TransferRequest.php
│   │   └── Resources/        # API resources
│   │       ├── TransactionResource.php
│   │       └── UserResource.php
│   ├── Jobs/                 # Queue jobs
│   │   └── ProcessTransfer.php
│   ├── Models/               # Eloquent models
│   │   ├── Transaction.php
│   │   └── User.php
│   ├── Policies/             # Authorization policies
│   │   └── TransactionPolicy.php
│   ├── Providers/            # Service providers
│   │   ├── AppServiceProvider.php
│   │   └── HorizonServiceProvider.php
│   ├── Services/             # Business logic services
│   │   ├── ResponseService.php
│   │   └── TransactionService.php
│   └── Traits/               # Reusable traits
│       └── ApiResponse.php
├── resources/
│   ├── css/
│   │   └── app.css           # Tailwind CSS
│   ├── js/
│   │   ├── components/       # Vue components
│   │   │   ├── Navbar.vue
│   │   │   ├── ToastContainer.vue
│   │   │   ├── ToastNotification.vue
│   │   │   ├── TransactionList.vue
│   │   │   ├── TransferForm.vue
│   │   │   └── UserBalance.vue
│   │   ├── composables/      # Vue composables
│   │   │   ├── useDebounce.ts
│   │   │   └── useToast.ts
│   │   ├── router/           # Vue Router
│   │   │   └── index.ts
│   │   ├── stores/           # Pinia stores
│   │   │   ├── auth.ts
│   │   │   └── transaction.ts
│   │   ├── types/            # TypeScript types
│   │   │   ├── auth.ts
│   │   │   ├── transaction.ts
│   │   │   └── user.ts
│   │   ├── utils/            # Utility functions
│   │   │   ├── api.ts
│   │   │   └── format.ts
│   │   ├── views/            # Page components
│   │   │   ├── Dashboard.vue
│   │   │   ├── Login.vue
│   │   │   └── Register.vue
│   │   ├── App.vue
│   │   ├── app.ts
│   │   └── bootstrap.ts
│   └── views/
│       └── welcome.blade.php # SPA entry point
├── routes/
│   ├── api.php               # API routes
│   ├── channels.php          # Broadcasting channels
│   ├── console.php           # Console commands
│   └── web.php               # Web routes
├── tests/
│   ├── Feature/              # Feature tests
│   │   ├── ConcurrencyTest.php
│   │   ├── TransactionControllerTest.php
│   │   ├── TransactionPerformanceTest.php
│   │   └── TransactionServiceTest.php
│   └── Unit/                 # Unit tests
├── database/
│   ├── factories/            # Model factories
│   ├── migrations/           # Database migrations
│   └── seeders/              # Database seeders
├── config/                   # Configuration files
├── docs/                     # Documentation
│   ├── API_DOCUMENTATION.md
│   ├── ARCHITECTURE.md
│   └── adr/                  # Architecture Decision Records
└── public/                   # Public assets
```

## 🔒 Security Features

- **Authentication**: Session-based with Laravel Sanctum
- **CSRF Protection**: Automatic CSRF token validation
- **XSS Protection**: Vue.js template escaping
- **SQL Injection**: Eloquent ORM parameterized queries
- **Rate Limiting**: Throttle middleware on sensitive endpoints
- **Password Hashing**: Bcrypt hashing
- **Authorization**: Policy-based access control
- **Database Transactions**: ACID compliance for transfers

## 🐛 Debugging

### Enable Debug Mode

```env
APP_DEBUG=true
```

### View Logs

```bash
# Real-time log streaming
php artisan pail

# Or view log file
tail -f storage/logs/laravel.log
```

### Queue Monitoring

Access Horizon dashboard at: `http://localhost:8000/horizon`

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 👥 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📧 Support

For issues and questions, please open an issue on the GitHub repository.

---

Built with ❤️ using Laravel 12 and Vue 3
