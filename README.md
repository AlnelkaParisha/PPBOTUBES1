# Streamly – Subscription Streaming Backend

Streamly is a RESTful API backend for a subscription-based streaming platform. This project was developed as a group assignment for the Object-Oriented Programming (OOP) course.

The application simulates the core backend functionality of a streaming platform, including user authentication, subscription plan management, subscription billing, invoice processing, payments, and content access based on active subscriptions.

## Project Information

| Information | Details |
|---|---|
| Project Name | Streamly |
| Project Type | RESTful API Backend |
| Framework | Laravel 13 |
| PHP | PHP 8 |
| Database | MySQL |
| Authentication | Laravel Sanctum |
| Dependency Manager | Composer |
| Testing Framework | PHPUnit |

## Main Features

### 1. Authentication and User Management
- User registration and login.
- Token-based authentication using Laravel Sanctum.
- User logout.
- Authenticated user profile.
- Admin role middleware.
- Authentication rate limiting.

### 2. Subscription Plan Management
- View available subscription plans.
- View subscription plan details.
- Create, update, and delete plans for administrators.
- Support for monthly, annual, and family billing cycles.

### 3. Subscription Management
- Create a subscription.
- View subscription details.
- Upgrade and downgrade subscription plans.
- Preview subscription plan changes.
- Calculate prorated charges.
- Cancel subscriptions.

### 4. Billing and Invoice Management
- Generate subscription invoices.
- View invoice details.
- Process invoice payments using a simulated payment gateway.
- Generate recurring invoices.
- Handle overdue invoices and expired subscriptions.

### 5. Content Management and Access
- View available streaming content.
- View content details.
- Check content access based on subscription eligibility.
- Create, update, and delete content for administrators.

## Object-Oriented Programming Implementation

The project applies Object-Oriented Programming concepts through the following implementations:

| OOP Concept | Implementation |
|---|---|
| Interface | `BillingStrategy`, `PaymentGateway`, and repository interfaces |
| Inheritance | `BaseBilling` as the base class for billing strategies |
| Polymorphism | Billing strategies selected through `BillingStrategyResolver` |
| Encapsulation | Access modifiers such as private and protected |
| Abstraction | Interfaces and abstract billing classes |
| Trait | `ApiResponse` and `HasInvoiceNumber` |
| Dependency Injection | Laravel service container and service providers |

### Billing Strategies

Streamly implements three billing strategies:

- `MonthlyBilling` – Monthly subscription billing.
- `AnnualBilling` – Annual subscription billing with discount support.
- `FamilyBilling` – Family subscription billing with member-based payment sharing.

The `BillingStrategyResolver` selects the appropriate billing strategy based on the subscription plan's billing cycle.

## Project Structure

```text
PPBOTUBES1/
├── app/
│   ├── Billing/
│   ├── Console/
│   │   └── Commands/
│   ├── Contracts/
│   ├── Enums/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Models/
│   ├── Payments/
│   ├── Policies/
│   ├── Providers/
│   ├── Repositories/
│   ├── Services/
│   └── Traits/
├── bootstrap/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── routes/
│   ├── api.php
│   ├── api/
│   ├── console.php
│   └── web.php
├── tests/
│   ├── Feature/
│   └── Unit/
├── composer.json
├── composer.lock
└── artisan
```

## Installation and Setup

### Prerequisites

Make sure the following tools are installed:

- PHP 8.3.36, 8.4.27, or 8.5.12 as the minimum patched versions specified in the course requirements.
- Composer.
- MySQL.
- Git.

### 1. Clone the Repository

```bash
git clone <YOUR_GITHUB_REPOSITORY_URL>
cd PPBOTUBES1
```

Run the following commands from the Laravel project directory containing `artisan` and `composer.json`.

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

Create the environment file:

```bash
cp .env.example .env
```

For Windows Command Prompt:

```cmd
copy .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### 4. Configure MySQL

Create a database named `streamly_db` using MySQL or phpMyAdmin.

Update the database configuration in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=streamly_db
DB_USERNAME=root
DB_PASSWORD=
```

Adjust the database username and password according to your local MySQL configuration.

### 5. Run Database Migrations and Seeders

```bash
php artisan migrate
php artisan db:seed
```

The database seeder creates sample administrator and user accounts, subscription plans, and streaming content.

**Sample development accounts:**

| Role | Email | Password |
|---|---|---|
| Admin | admin@example.com | password |
| User | user@example.com | password |

These accounts are intended for local development and testing only.

### 6. Start the Application

```bash
php artisan serve
```

The application will be available at:

`http://127.0.0.1:8000`

API base URL:

`http://127.0.0.1:8000/api`

## API Endpoints

All API endpoints use the `/api` prefix.

### Authentication

| Method | Endpoint | Description | Access |
|---|---|---|---|
| POST | `/register` | Register a new user | Public |
| POST | `/login` | User login | Public |
| POST | `/logout` | User logout | Authenticated |
| GET | `/me` | Get authenticated user profile | Authenticated |

### Subscription Plans

| Method | Endpoint | Description | Access |
|---|---|---|---|
| GET | `/plans` | Get all plans | Public |
| GET | `/plans/{plan}` | Get plan details | Public |
| POST | `/plans` | Create a plan | Admin |
| PUT | `/plans/{plan}` | Update a plan | Admin |
| DELETE | `/plans/{plan}` | Delete a plan | Admin |

### Subscriptions

| Method | Endpoint | Description | Access |
|---|---|---|---|
| GET | `/subscriptions` | Get user subscriptions | Authenticated |
| POST | `/subscriptions` | Create a subscription | Authenticated |
| GET | `/subscriptions/{subscription}` | Get subscription details | Authenticated |
| POST | `/subscriptions/{subscription}/change-plan` | Change subscription plan | Authenticated |
| POST | `/subscriptions/{subscription}/preview-change` | Preview plan changes | Authenticated |
| POST | `/subscriptions/{subscription}/cancel` | Cancel a subscription | Authenticated |

### Invoices

| Method | Endpoint | Description | Access |
|---|---|---|---|
| GET | `/invoices` | Get user invoices | Authenticated |
| GET | `/invoices/{invoice}` | Get invoice details | Authenticated |
| POST | `/invoices/{invoice}/pay` | Process invoice payment | Authenticated |

### Content

| Method | Endpoint | Description | Access |
|---|---|---|---|
| GET | `/contents` | Get available content | Authenticated |
| GET | `/contents/{content}` | Get content details | Authenticated |
| GET | `/contents/{content}/watch` | Check content access | Authenticated |
| POST | `/contents` | Create content | Admin |
| PUT | `/contents/{content}` | Update content | Admin |
| DELETE | `/contents/{content}` | Delete content | Admin |

Protected endpoints require a valid Laravel Sanctum bearer token. Administrator endpoints require administrator authorization.

## Scheduled Billing Commands

The application provides commands for recurring invoice generation and subscription expiration.

Generate recurring invoices:

```bash
php artisan billing:generate-invoices
```

Handle overdue invoices and expired subscriptions:

```bash
php artisan billing:expire-unpaid
```

The commands are scheduled in `routes/console.php`:

- Recurring invoice generation: Daily at 01:00.
- Unpaid subscription expiration: Daily at 02:00.

For automatic execution, configure Laravel's scheduler to run every minute:

```bash
php artisan schedule:run
```

## Automated Testing

The project includes feature tests and unit tests.

Run the test suite using:

```bash
php artisan test
```

The tests include scenarios for subscription flows, payment processing, billing calculations, prorated charges, and content access.

Test results must be verified in the development environment before being reported as successful.

## Security

The application implements the following security mechanisms:

- Laravel Sanctum for API authentication.
- Rate limiting for authentication endpoints.
- Admin middleware for administrator-only operations.
- Form Request validation.
- API Resources for structured responses.
- Environment variables for application configuration.

Security recommendations:

- Never commit the `.env` file.
- Set `APP_DEBUG=false` in production.
- Use secure database credentials.
- Run `composer audit` to check dependencies for known security vulnerabilities.

## Development Team

Streamly was developed collaboratively as a group project.

**Project:** Streamly – Subscription Streaming Backend

**Course:** Object-Oriented Programming

**Study Program:** Information Systems

## License

This project was developed for educational purposes as part of a university group assignment.
