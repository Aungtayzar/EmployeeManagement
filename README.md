# Employee Management API

A GraphQL-based backend API for managing employee records, built with Laravel 12.

## Project Overview

This system provides:
- JWT-based authentication (login, token refresh, logout)
- Role-based access control (Admin and Employee roles)
- Full employee CRUD with soft deletes (recoverable removal)
- Bulk employee generation (10,000 records) via queued job
- Queued Excel imports with row-level validation and persisted results
- Queued Excel exports with task-status polling and download links
- Paginated employee listing with search by name or email, join date range, and salary range filters

## Tech Stack

| Component | Package |
|-----------|---------|
| Framework | Laravel 12 |
| API Layer | GraphQL via [nuwave/lighthouse](https://lighthouse-php.com) |
| Auth | JWT via [tymon/jwt-auth](https://github.com/tymondesigns/jwt-auth) |
| Excel | [Maatwebsite/Laravel-Excel](https://laravel-excel.com) |
| Test Data | Faker (bundled with Laravel) |

## Prerequisites

- PHP 8.2+
- MySQL 5.7+ (or MariaDB 10.3+)
- Composer
- PHP extensions: `ext-zip`, `ext-pdo_mysql`, `ext-fileinfo`

## Environment Setup

1. **Clone and install dependencies**
   ```bash
   git clone <repository-url>
   cd employee-management-api
   composer install
   ```

2. **Configure environment**
   ```bash
   cp .env.example .env
   ```
   
   Update `.env` with your database credentials:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=employee_management
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. **Generate app key and JWT secret**
   ```bash
   php artisan key:generate
   php artisan jwt:secret
   ```

4. **Run migrations and seed**
   ```bash
   php artisan migrate --seed
   ```
   This creates the database structure and seeds a default admin user.

5. **Generate sample Excel import template (optional)**
   ```bash
   php artisan sample:excel
   ```
   The sample file will be saved to `storage/app/sample-import-template.xlsx`.

## Default Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | password |

## How to Run

Start the development server:
```bash
php artisan serve
```

The GraphQL endpoint is available at: `http://localhost:8000/graphql`

To process queued jobs (employee generation, imports, and exports):
```bash
php artisan queue:work
```

## How to Run Tests

```bash
php artisan test
```

This runs the authentication and employee-management feature tests, including queued export task creation and task-status retrieval.

## Postman Collection

The ready-to-import collection is available at [`postman/employee-management-api.postman_collection.json`](postman/employee-management-api.postman_collection.json).

Import it into Postman, set the `base` collection variable if your application is not running at `http://employee-management-api.test`, then run **1. login**. The collection stores the JWT automatically for authenticated requests.

For imports, select an `.xlsx` file in **13. importEmployees (multipart)**. Both import and export requests save their returned task ID to `transferTaskId`; use **14. employeeTransferTask (poll status)** until `status` is `completed` or `failed`. A completed export includes its download `url`.

## GraphQL API Usage

All API access is through a single endpoint: `POST /graphql`

### Authentication Headers

For authenticated requests, include the JWT token:
```
Authorization: Bearer <your-token>
```

### Authentication Mutations

**Login**
```graphql
mutation {
    login(email: "admin@example.com", password: "password") {
        token
    }
}
```

**Refresh Token**
```graphql
mutation {
    refreshToken {
        token
    }
}
```

**Logout**
```graphql
mutation {
    logout {
        message
    }
}
```

### Employee Queries

**View own profile** (Admin and Employee)
```graphql
query {
    me {
        id
        email
        system_role
        employee {
            first_name
            last_name
            phone
            address
            salary
            job_role
            join_date
        }
    }
}
```

**View all employees** (Admin only)
```graphql
query {
    employees(
        search: "john",
        join_date_from: "2024-01-01",
        join_date_to: "2024-12-31",
        salary_min: 3000,
        salary_max: 8000,
        page: 1
    ) {
        data {
            id
            first_name
            last_name
            email
            salary
            system_role
            job_role
            join_date
        }
        paginatorInfo {
            count
            currentPage
            lastPage
            total
        }
    }
}
```

**View single employee** (Admin only)
```graphql
query {
    employee(id: 1) {
        id
        first_name
        last_name
        email
        phone
        address
        salary
        system_role
        job_role
    }
}
```

### Employee Mutations (Admin only)

**Create employee**
```graphql
mutation {
    createEmployee(input: {
        first_name: "John"
        last_name: "Doe"
        email: "john@example.com"
        phone: "555-0100"
        address: "123 Main St"
        salary: 75000
        system_role: "employee"
        job_role: "Software Engineer"
        password: "password123"
    }) {
        id
        first_name
        last_name
        email
    }
}
```

**Update employee**
```graphql
mutation {
    updateEmployee(id: 2, input: {
        first_name: "Jane"
        salary: 85000
    }) {
        id
        first_name
        salary
    }
}
```

**Delete employee** (soft delete)
```graphql
mutation {
    deleteEmployee(id: 2) {
        message
    }
}
```

**Restore deleted employee**
```graphql
mutation {
    restoreEmployee(id: 2) {
        message
    }
}
```

### Bulk Operations (Admin only)

**Generate test data** (dispatches queued job)
```graphql
mutation {
    generateEmployees(count: 10000) {
        job_id
    }
}
```

**Import employees from Excel**
```graphql
mutation($file: Upload!) {
    importEmployees(file: $file) {
        id
        status
        success_count
        errors {
            row
            message
        }
        error_message
    }
}
```
> This queues the import and immediately returns a task with `status: pending`. Upload an Excel file with columns: `first_name`, `last_name`, `email`, `phone`, `address`, `salary`, `system_role`, `job_role`. Existing employees matched by email will be updated; new emails will create new records.

**Export employees to Excel**
```graphql
query {
    exportEmployees {
        id
        status
        url
    }
}
```
> This queues the export. `url` is `null` until the task completes.

**Check import or export task status**
```graphql
query($id: ID!) {
    employeeTransferTask(id: $id) {
        id
        type
        status
        success_count
        errors {
            row
            message
        }
        error_message
        url
    }
}
```

### Access Control

| Operation | Admin | Employee |
|-----------|-------|----------|
| `me` | Full profile | Full profile |
| `employees` (list) | All employees | Denied |
| `employee(id)` | Any employee | Denied |
| Create/Update/Delete/Restore | Full access | Denied |
| Generate/Import/Export | Full access | Denied |

## Excel Import Format

The Excel file must have a header row with the following columns:

| Column | Required | Description |
|--------|----------|-------------|
| first_name | Yes | First name |
| last_name | Yes | Last name |
| email | Yes | Unique email address |
| phone | No | Phone number |
| address | No | Physical address |
| salary | No | Numeric salary amount |
| system_role | No | `admin` or `employee` (default: employee) |
| job_role | No | Job title/position |

A sample file can be generated using `php artisan sample:excel`.

## Architecture Notes

- **User and employee records**: Authentication and roles are stored on `users`; employee profile information is stored on `employees`.
- **Soft deletes**: Deleted employees are hidden from queries but remain in the database and can be restored. Deleted email addresses cannot be reused by new employees.
- **Authorization**: Access control is handled within each resolver class by checking the authenticated user's `system_role`. Admin-only operations throw an "unauthorized" GraphQL error for non-admin users.
- **Bulk generation**: The `generateEmployees` mutation dispatches a queued job that processes records in chunks of 500 to avoid memory issues.
- **Excel transfers**: Imports and exports run in queued jobs. Each request creates an `employee_transfer_tasks` record; poll `employeeTransferTask` for status, validation errors, and the completed export URL.
