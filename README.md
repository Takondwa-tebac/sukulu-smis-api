# Sukulu School Management Information System (SMIS) API

A comprehensive, enterprise-grade, multi-tenant School Management System API built with Laravel 12.

## Features

### Core Modules

- **Authentication & Authorization** - Role-based access control with Spatie Permission, Sanctum token authentication
- **Multi-Tenancy** - School-based tenant isolation with automatic scoping
- **Academic Structure** - Academic years, terms, classes, streams, subjects, departments
- **Student Management** - Student profiles, guardians, enrollments, promotions
- **Admissions** - Application workflow, document management, interview scheduling
- **Grading System** - Configurable grade scales, GPA calculation, promotion rules
- **Exams & Assessments** - Exam management, marks entry, moderation workflow
- **Fees & Billing** - Fee structures, invoices, payments, discounts
- **Report Cards** - Automated generation, approval workflow, publishing
- **Timetables** - Time periods, class timetables, teacher schedules
- **Attendance** - Student attendance tracking, reports, statistics
- **Notifications** - Email/SMS templates, bulk notifications, user preferences

## Tech Stack

- **Framework**: Laravel 12
- **Authentication**: Laravel Sanctum
- **Authorization**: Spatie Laravel Permission
- **Database**: MySQL/PostgreSQL/SQLite
- **Media**: Spatie Media Library
- **Testing**: Pest PHP

## Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+ / PostgreSQL 14+ / SQLite

## Installation

```bash
# Clone the repository
git clone https://github.com/your-org/sukulu-smis-api.git
cd sukulu-smis-api

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env file
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=sukulu_smis
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations and seeders
php artisan migrate --seed

# Start the development server
php artisan serve
```

## Default Credentials

After seeding, you can login with:
- **Email**: `admin@sukulu.com`
- **Password**: `password`

## API Documentation

All API endpoints are prefixed with `/api/v1/`.

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/login` | Login and get token |
| POST | `/auth/logout` | Logout current session |
| GET | `/auth/me` | Get authenticated user |
| POST | `/auth/change-password` | Change password |

### Academic Structure
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | `/academic-years` | List/Create academic years |
| GET/PUT/DELETE | `/academic-years/{id}` | View/Update/Delete academic year |
| GET/POST | `/terms` | List/Create terms |
| GET/POST | `/classes` | List/Create classes |
| GET/POST | `/subjects` | List/Create subjects |

### Students
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | `/students` | List/Create students |
| GET/PUT/DELETE | `/students/{id}` | View/Update/Delete student |
| POST | `/students/{id}/enroll` | Enroll student in class |
| POST | `/students/{id}/guardians` | Attach guardian |

### Admissions
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | `/admission-periods` | List/Create admission periods |
| GET/POST | `/admission-applications` | List/Create applications |
| POST | `/admission-applications/{id}/approve` | Approve application |
| POST | `/admission-applications/{id}/enroll` | Enroll approved applicant |

### Exams & Marks
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | `/exams` | List/Create exams |
| POST | `/exams/{id}/subjects` | Add subjects to exam |
| GET/POST | `/exam-subjects/{id}/marks` | List/Enter marks |
| POST | `/exam-subjects/{id}/marks/approve` | Approve marks |

### Fees & Payments
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | `/invoices` | List/Create invoices |
| POST | `/invoices/generate-from-structure` | Generate from fee structure |
| GET/POST | `/payments` | List/Create payments |
| POST | `/payments/{id}/allocate` | Allocate to invoice |

### Report Cards
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/report-cards` | List report cards |
| POST | `/report-cards/generate` | Generate report cards |
| POST | `/report-cards/bulk-approve` | Bulk approve |
| POST | `/report-cards/bulk-publish` | Bulk publish |

### Timetables
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | `/time-periods` | List/Create time periods |
| GET/POST | `/timetables` | List/Create timetables |
| POST | `/timetables/{id}/slots` | Add slots to timetable |
| GET | `/timetables/teacher-schedule` | Get teacher schedule |

### Attendance
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | `/attendance/sessions` | List/Create attendance sessions |
| POST | `/attendance/sessions/{id}/record` | Record attendance |
| GET | `/attendance/student/{id}/report` | Student attendance report |
| GET | `/attendance/class-report` | Class attendance report |

### Notifications
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | `/notifications/templates` | List/Create templates |
| POST | `/notifications/send` | Send notification |
| GET | `/notifications/my` | User's notifications |
| GET | `/notifications/unread-count` | Unread count |

### Grading Systems
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | `/grading-systems` | List/Create grading systems |
| GET | `/grading-systems/system-defaults` | Get system defaults |
| POST | `/grading-systems/calculate` | Calculate grade |

## Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/V1/     # API Controllers
│   ├── Requests/               # Form Request validation (organized by module)
│   │   ├── AcademicYear/
│   │   ├── Admission/
│   │   ├── Attendance/
│   │   ├── Exam/
│   │   ├── Fee/
│   │   ├── Guardian/
│   │   ├── Notification/
│   │   ├── Report/
│   │   ├── SchoolClass/
│   │   ├── Student/
│   │   ├── Subject/
│   │   ├── Term/
│   │   └── Timetable/
│   └── Resources/              # API Resources (organized by module)
│       ├── AcademicYear/
│       ├── Admission/
│       ├── Attendance/
│       ├── Exam/
│       ├── Fee/
│       ├── Notification/
│       ├── Report/
│       ├── SchoolClass/
│       ├── Student/
│       ├── Subject/
│       └── Timetable/
├── Models/                     # Eloquent Models
├── Services/                   # Business Logic Services
│   ├── Grading/
│   └── NotificationService.php
├── Traits/                     # Reusable Traits
│   ├── BaseModel.php
│   ├── BelongsToTenant.php
│   └── HasUuid.php
└── Scopes/                     # Global Scopes
    └── TenantScope.php

database/
├── migrations/                 # Database Migrations
└── seeders/                    # Database Seeders
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=GradingSystemTest

# Run with coverage
php artisan test --coverage
```

## Multi-Tenancy

The system uses a `school_id` column for tenant isolation. All tenant-scoped models use:
- `BelongsToTenant` trait for automatic school_id assignment
- `TenantScope` global scope for automatic query filtering

## UUID Primary Keys

All models use UUID primary keys via the `HasUuid` trait for better security and distributed system compatibility.

## Audit Trail

All models track:
- `created_by` - User who created the record
- `updated_by` - User who last updated the record
- `deleted_by` - User who soft-deleted the record

## License

This project is proprietary software. All rights reserved.
