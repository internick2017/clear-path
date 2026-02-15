# Clear Path

A comprehensive personal finance management application built with Laravel 12, Inertia.js, and Vue 3.

## Overview

Clear Path helps users take control of their financial life by providing tools to track transactions, manage budgets, set savings goals, and plan debt payoff strategies.

## Features

### Transaction Management
- Track income and expenses with categorization
- Link transactions to debts for automatic payment tracking
- Multi-currency support with automatic conversion
- Category suggestions based on transaction history

### Budget Management
- Create budgets by category with spending limits
- Real-time tracking of budget consumption
- Notifications when approaching or exceeding limits
- Monthly budget performance analysis

### Savings Goals
- Set financial goals with target amounts and deadlines
- Track progress with deposits
- Visual progress indicators
- Goal completion notifications

### Debt Management
- Track multiple debts with balances, interest rates, and minimum payments
- Payment history and progress tracking
- Smart payoff strategies:
  - **Avalanche Method**: Pay off highest interest rate debts first
  - **Snowball Method**: Pay off smallest balances first
- Debt payoff plan visualization
- Payment reminders and notifications

### Multi-Currency Support
- Support for multiple currencies (USD, EUR, GBP, etc.)
- User-selectable display currency
- Automatic currency conversion for transactions
- Real-time exchange rate updates

### Dashboard
- Financial overview at a glance
- Budget status summaries
- Goal progress tracking
- Recent transactions
- Debt overview with payoff projections

### Notifications
- Budget exceeded alerts
- Debt payment reminders
- Goal achievement notifications
- Scheduled reminders

### Audit Logging
- Track all financial data changes
- Maintain accountability and transparency
- Historical record of modifications

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Vue 3 with Composition API
- **Bridge**: Inertia.js
- **Styling**: Tailwind CSS
- **Charts**: Chart.js
- **Icons**: Heroicons
- **Authentication**: Laravel Breeze

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm/yarn
- MySQL, PostgreSQL, or SQLite

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd clear-path
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   # or
   yarn install
   ```

4. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**

   Update `.env` with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=clear_path
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed currency rates (optional)**
   ```bash
   php artisan db:seed --class=CurrencyRatesSeeder
   ```

8. **Build assets**
   ```bash
   npm run build
   # or for development
   npm run dev
   ```

9. **Start the server**
   ```bash
   php artisan serve
   ```

## Development

Run all services concurrently for development:

```bash
composer dev
```

This starts:
- Laravel development server
- Queue worker
- Laravel Pail (log viewer)
- Vite dev server

### Running Tests

```bash
composer test
```

## Project Structure

```
app/
├── Console/Commands/      # Artisan commands for scheduled tasks
├── Helpers/               # Currency and utility helpers
├── Http/
│   ├── Controllers/       # Application controllers
│   ├── Middleware/        # Security headers and middleware
│   └── Requests/          # Form request validation
├── Mail/                  # Email templates
├── Models/                # Eloquent models
├── Notifications/         # Notification classes
└── Services/              # Business logic services

resources/js/
├── Components/            # Reusable Vue components
├── Layouts/               # Page layouts
├── Pages/                 # Inertia page components
└── composables/           # Vue composables

database/
├── factories/             # Model factories
├── migrations/            # Database migrations
└── seeders/               # Database seeders
```

## Key Services

| Service | Description |
|---------|-------------|
| `BudgetService` | Budget calculations and tracking |
| `DebtPayoffService` | Debt payoff strategies and projections |
| `CurrencyConversionService` | Multi-currency conversion |
| `NotificationService` | Notification management |
| `AuditService` | Activity logging |
| `CategorySuggestionService` | Smart category suggestions |
| `ReminderService` | Scheduled reminders |

## Scheduled Commands

| Command | Schedule | Description |
|---------|----------|-------------|
| `budget:check-exceeded` | Daily | Send budget exceeded notifications |
| `debts:payment-reminders` | Daily | Send debt payment reminders |
| `reminders:process` | Hourly | Process scheduled reminders |
| `notifications:send-scheduled` | Hourly | Send scheduled notifications |

## Security Features

- CSRF protection
- Rate limiting on sensitive operations
- Security headers middleware
- Input validation and sanitization
- Audit logging for accountability

## License

This project is proprietary software. All rights reserved.
