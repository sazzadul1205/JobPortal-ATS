# Job Portal ATS System Documentation

| | |
|---|---|
| **Project** | Dwip-Unnayan-Sangstha-Website |
| **Scope** | Job Portal / Applicant Tracking System (ATS) + Role-Based Access Control (RBAC) |
| **Stack** | Laravel, Inertia.js, React |
| **Status** | Finalized, pre-CMS integration |
| **Branch** | `master` (commit `645eefb`, "Completed job listing") |

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Database Schema](#2-database-schema)
3. [Models](#3-models)
4. [Controllers](#4-controllers)
5. [Services](#5-services)
6. [Jobs](#6-jobs)
7. [Role System](#7-role-system)
8. [RBAC](#8-rbac-role-based-access-control)
9. [ATS Score Flow](#9-ats-score-flow)
10. [Soft Deletes](#10-soft-deletes)
11. [Key Design Decisions](#11-key-design-decisions)
12. [Summary of Files](#12-summary-of-files)

---

## 1. System Overview

The Job Portal ATS is an end-to-end applicant tracking platform built on Laravel, Inertia.js, and React. It lets employers post job listings, lets job seekers apply with resumes, and uses an automated ATS engine to score each application against the job's requirements using keyword matching.

Access is governed by three roles: **admin**, **employer**, and **job_seeker**.

---

## 2. Database Schema

### 2.1 Tables

| Table | Description |
|-------|-------------|
| `users` | System users with role-based access (`admin`, `employer`, `job_seeker`) |
| `job_categories` | Job categories with slugs and active/inactive status |
| `locations` | Job locations (cities/regions) with active status |
| `applicant_profiles` | Job seeker profile details (one-to-one with `users`) |
| `job_listings` | Employer-posted job listings, with JSON fields for skills, benefits, etc. |
| `applications` | Job applications, with ATS scoring data stored as JSON |
| `password_reset_tokens` | Password reset tokens |
| `sessions` | Laravel session storage |

### 2.2 `users`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigIncrements | PK |
| `name` | string | |
| `email` | string | Unique |
| `email_verified_at` | timestamp | Nullable |
| `password` | string | Hashed |
| `role` | enum | `admin`, `employer`, `job_seeker` (default: `job_seeker`) |
| `remember_token` | string | Nullable |
| `created_at`, `updated_at` | timestamps | |

**Indexes:** `role`

### 2.3 `job_categories`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigIncrements | PK |
| `name` | string | |
| `slug` | string | Unique, auto-generated from `name` |
| `is_active` | boolean | Default `true` |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp | Nullable (soft delete) |

**Indexes:** `is_active`, `slug`

**Seed:** 22 categories, including IT, Engineering, Healthcare, Finance, Sales, HR, Admin, Education, Customer Service, Manufacturing, Construction, Logistics, Retail, Hospitality, Media, Legal, Real Estate, Agriculture, Telecom, and Automotive.

### 2.4 `locations`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigIncrements | PK |
| `name` | string | |
| `address` | text | Nullable |
| `is_active` | boolean | Default `true` |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp | Nullable (soft delete) |

**Indexes:** `is_active`

**Seed:** 9 Bangladesh divisions/districts: All Over Bangladesh, Dhaka, Sylhet, Chittagong, Rajshahi, Khulna, Barisal, Rangpur, Mymensingh.

### 2.5 `applicant_profiles`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigIncrements | PK |
| `user_id` | foreignId | FK → `users.id` (cascade) |
| `first_name` | string | |
| `last_name` | string | |
| `birth_date` | date | Nullable |
| `email` | string | Indexed |
| `phone` | string | Nullable |
| `photo_path` | string | Nullable |
| `cv_path` | string | Nullable |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp | Nullable (soft delete) |

**Indexes:** `user_id`, `email`

### 2.6 `job_listings`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigIncrements | PK |
| `title` | string | |
| `slug` | string | Unique, auto-generated |
| `description` | longText | Rich text |
| `requirements` | longText | Rich text |
| `location_id` | foreignId | FK → `locations.id` (cascade) |
| `salary` | string | Nullable |
| `job_type` | string | |
| `category_id` | foreignId | FK → `job_categories.id` (cascade) |
| `experience_level` | string | |
| `education_requirement` | string | Nullable |
| `benefits` | json | Nullable |
| `skills` | json | Nullable |
| `responsibilities` | json | Nullable |
| `keywords` | json | Nullable |
| `application_deadline` | date | |
| `schedule_start_date` | date | Nullable |
| `is_active` | boolean | Default `true` |
| `user_id` | foreignId | FK → `users.id` (cascade); the employer who posted the job |
| `show_linkedin` | boolean | Default `false` |
| `show_facebook` | boolean | Default `false` |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp | Nullable (soft delete) |

**Indexes:** `[is_active, application_deadline]`, `category_id`, `location_id`, `job_type`, `slug`, `schedule_start_date`

**Model behaviors:**
- Generates `slug` from `title` on create.
- Regenerates `slug` when `title` changes.
- Scopes: `active()`, `byType()`, `byCategory()`, `byLocation()`.

### 2.7 `applications`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigIncrements | PK |
| `job_listing_id` | foreignId | FK → `job_listings.id` (cascade) |
| `user_id` | foreignId | FK → `users.id` (cascade); the applicant |
| `applicant_profile_id` | foreignId | FK → `applicant_profiles.id` (set null) |
| `name` | string | |
| `email` | string | |
| `phone` | string | Nullable |
| `resume_path` | string | Stored in `public/resumes` |
| `expected_salary` | decimal(12,2) | Nullable |
| `ats_score` | json | Nullable; ATS scoring results |
| `matched_keywords` | json | Nullable |
| `missing_keywords` | json | Nullable |
| `status` | enum | `pending`, `reviewed`, `shortlisted`, `rejected`, `hired` (default: `pending`) |
| `employer_notes` | text | Nullable |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp | Nullable (soft delete) |

**Indexes:** `status`, `[job_listing_id, status]`, `applicant_profile_id`  
**Unique constraint:** `[job_listing_id, user_id]` (prevents duplicate applications)

**Model behaviors:**
- Status checks: `isPending()`, `isReviewed()`, `isShortlisted()`, `isRejected()`, `isHired()`.
- `getAtsScorePercentage()` returns the total score as an integer.
- `calculateATSScore()` invokes `ATSService` to compute the score.
- Scopes: `withStatus()`, `pending()`, `reviewed()`, `shortlisted()`, `forJob()`.

---

## 3. Models

### 3.1 User (`app/Models/User.php`)

- **Fillable:** `name`, `email`, `password`, `role`
- **Hidden:** `password`, `remember_token`
- **Casts:** `email_verified_at` → datetime, `password` → hashed

**Role helper methods**

| Method | Returns | Description |
|--------|---------|-------------|
| `isAdmin()` | bool | `role === 'admin'` |
| `isEmployer()` | bool | `role === 'employer'` |
| `isJobSeeker()` | bool | `role === 'job_seeker'` |
| `hasRole(string $role)` | bool | Exact role match |
| `hasAnyRole(array $roles)` | bool | Matches any role in the array |

**Relationships**
- `jobListings()`: `hasMany(JobListing)`, the listings created by this employer
- `applications()`: `hasMany(Application)`, the applications submitted by this job seeker

### 3.2 JobListing (`app/Models/JobListing.php`)

- **Uses:** `HasFactory`, `SoftDeletes`
- **Fillable:** `title`, `slug`, `description`, `requirements`, `location_id`, `salary`, `job_type`, `category_id`, `experience_level`, `education_requirement`, `benefits`, `skills`, `responsibilities`, `keywords`, `application_deadline`, `schedule_start_date`, `is_active`, `user_id`, `show_linkedin`, `show_facebook`
- **Casts:** `benefits`, `skills`, `responsibilities`, `keywords` → array; `application_deadline`, `schedule_start_date` → date; `is_active`, `show_linkedin`, `show_facebook` → boolean

**Relationships**
- `location()`: `belongsTo(Location)`
- `category()`: `belongsTo(JobCategory, 'category_id')`
- `user()`: `belongsTo(User)`, the employer
- `applications()`: `hasMany(Application)`

**Scopes**
- `active()`: `is_active = true` AND `application_deadline >= now()`
- `byType($type)`: filters by `job_type`
- `byCategory($categoryId)`: filters by `category_id`
- `byLocation($locationId)`: filters by `location_id`

### 3.3 JobCategory (`app/Models/JobCategory.php`)

- **Uses:** `HasFactory`, `SoftDeletes`
- **Fillable:** `name`, `slug`, `is_active`
- **Casts:** `is_active` → boolean
- **Relationships:** `jobListings()`: `hasMany(JobListing, 'category_id')`
- **Scopes:** `active()`, `whereSlug($slug)`

### 3.4 Location (`app/Models/Location.php`)

- **Uses:** `HasFactory`, `SoftDeletes`
- **Fillable:** `name`, `address`, `is_active`
- **Casts:** `is_active` → boolean
- **Relationships:** `jobListings()`: `hasMany(JobListing)`
- **Scopes:** `active()`
- **Accessors:** `getFormattedAddressAttribute()` returns the address, falling back to the name

### 3.5 Application (`app/Models/Application.php`)

- **Uses:** `HasFactory`
- **Fillable:** `job_listing_id`, `user_id`, `name`, `email`, `phone`, `resume_path`, `ats_score`, `matched_keywords`, `missing_keywords`, `status`, `employer_notes`
- **Casts:** `ats_score`, `matched_keywords`, `missing_keywords` → array; datetimes

**Relationships**
- `jobListing()`: `belongsTo(JobListing)`
- `applicant()`: `belongsTo(User, 'user_id')`

**Status helpers:** `isPending()`, `isReviewed()`, `isShortlisted()`, `isRejected()`, `isHired()`

**ATS methods**
- `getAtsScorePercentage(): ?int` returns `$this->ats_score['total']`.
- `calculateATSScore(): void` calls `ATSService::calculateScore()` and updates `ats_score`, `matched_keywords`, and `missing_keywords`.

**Scopes:** `withStatus($status)`, `pending()`, `reviewed()`, `shortlisted()`, `forJob($jobListingId)`

---

## 4. Controllers

### 4.1 JobListingController (`app/Http/Controllers/JobListingController.php`)

Full CRUD for job listings, plus status management. All routes require `auth` + `verified`.

| Method | Route name | Description |
|--------|-----------|-------------|
| `index()` | `backend.listing.index` | List all job listings (with category, location, user) |
| `create()` | `backend.listing.create` | Show the create form (categories, locations) |
| `store()` | `backend.listing.store` | Create a listing; sets `user_id = Auth::id()`, generates the slug, determines the initial active status |
| `show()` | `backend.listing.show` | View a single listing |
| `edit()` | `backend.listing.edit` | Show the edit form |
| `update()` | `backend.listing.update` | Update a listing; regenerates the slug if the title changed |
| `destroy()` | `backend.listing.destroy` | Soft delete (blocked if applications exist) |
| `toggleActive()` | `backend.listing.toggle-active` | Toggle `is_active` |
| `applications()` | `backend.listing.applications` | List applications for a listing |
| `updateJobStatuses()` | internal | Scheduled task: auto-activate/deactivate listings based on dates |
| `determineInitialStatus()` | internal | Determine `is_active` on creation from dates |
| `determineStatusFromDates()` | internal | Determine `is_active` on update from dates |

### 4.2 ApplicationController (`app/Http/Controllers/ApplicationController.php`)

Handles applications, ATS scoring, status updates, resume management, and analytics. The constructor injects `ATSService`. All routes require `auth` + `verified`.

| Method | Route name | RBAC | Description |
|--------|-----------|------|-------------|
| `index()` | `application.index` | Job seeker: own applications. Employer: applications for own jobs. Admin: all. | List applications with filters (`status`, `job_listing_id`, `min_score`) |
| `create()` | `applications.create` | Job seeker only | Show the application form for a job |
| `store()` | `applications.store` | Job seeker only | Submit an application; uploads the resume (pdf/doc/docx, max 5 MB) and dispatches `CalculateAtsScore` |
| `show()` | `application.show` | Owner (seeker) or job owner (employer) | View application details |
| `updateStatus()` | `application.update-status` | Admin or job owner (employer) | Update status and employer notes |
| `bulkUpdateStatus()` | `application.bulk-status` | Admin or employer | Bulk-update multiple applications |
| `mergeResumes()` | `application.merge-resumes` | Admin or employer | Merge selected PDF resumes into one PDF (Fpdi) |
| `downloadResume()` | `application.download-resume` | Owner or job owner | Download the resume file |
| `destroy()` | `application.destroy` | Admin or own application | Soft delete the application and delete the resume file |
| `recalculateScore()` | `application.recalculate-score` | Admin or job owner | Recalculate the ATS score |
| `analyticsDashboard()` | `backend.analytics.dashboard` | All roles | ATS statistics dashboard |
| `jobATSAnalysis()` | `backend.analytics.job-analysis` | Admin or job owner | Per-job keyword analysis and top candidates |
| `getATSStats()` | API | Employer (own jobs) | JSON stats: total applications, average score, score distribution, top skills, status breakdown |

**ATS score distribution tiers**

| Tier | Score |
|------|-------|
| Excellent | ≥ 80 |
| Good | 60–79 |
| Fair | 40–59 |
| Poor | < 40 |

### 4.3 JobCategoryController (`app/Http/Controllers/JobCategoryController.php`)

CRUD for job categories with soft delete, restore, force delete, and toggle active. Deletion is blocked if the category has associated job listings.

### 4.4 LocationController (`app/Http/Controllers/LocationController.php`)

CRUD for locations with soft delete, restore, and toggle active.

### 4.5 RegisteredUserController (`app/Http/Controllers/Auth/RegisteredUserController.php`)

Handles registration with role selection (`job_seeker` or `employer`). Admins are created by the database seeder, not through public registration.

---

## 5. Services

### 5.1 ATSService (`app/Services/ATSService.php`)

The core ATS scoring engine. It calculates a match score between a resume and a job listing.

**Main method:** `calculateScore(Application $application, JobListing $jobListing): array`

**Process**

1. Extract text from the resume (PDF via `smalot/pdfparser`; DOC/DOCX via ZipArchive XML parsing).
2. Extract keywords from the job listing: stored keywords, `job_type`, category, `experience_level`, and words from the title, description, and requirements.
3. Clean the keywords: remove stop words, terms under 2 characters, and duplicates.
4. Match keywords using a case-insensitive substring search against the resume text.
5. Compute the score: `(matched / total) * 100`.
6. Extract skills from the resume (hardcoded list of ~50 common skills).
7. Extract years of experience (regex patterns).
8. Extract education (regex for bachelor/master/PhD/BS/MS/BA/MA/MBA).
9. Generate the analysis (level: Excellent / Good / Fair / Needs Improvement).

**Return array**

```php
[
    'total' => float,                   // raw score, 0-100
    'percentage' => float,              // rounded percentage
    'matched_keywords' => [],           // keywords found in the resume
    'missing_keywords' => [],           // keywords not found in the resume
    'matched_count' => int,
    'total_keywords' => int,
    'extracted_skills' => [],           // skills found in the resume
    'extracted_experience_years' => int,
    'extracted_education' => string,
    'analysis_details' => [             // human-readable analysis
        'level' => string,
        'message' => string,
        'matched_count' => int,
        'missing_count' => int,
        'top_matched' => [],
        'critical_missing' => [],
    ],
]
```

---

## 6. Jobs

### 6.1 CalculateAtsScore (`app/Jobs/CalculateAtsScore.php`)

A queued job implementing `ShouldQueue`.

| Property | Value |
|----------|-------|
| `$timeout` | 120 seconds |
| `$tries` | 3 |
| `$backoff` | `[10, 30, 60]` seconds |

**Handle:** Finds the application with its job listing and calls `calculateATSScore()`. Falls back to inline execution if queue dispatch fails.

---

## 7. Role System

Three roles are defined as a database `ENUM` on the `users` table. The default role is `job_seeker`.

| Role | Value | Purpose | Can register |
|------|-------|---------|--------------|
| Admin | `admin` | Full system access: manages all job listings, categories, locations, users, and applications | No (seeded only) |
| Employer | `employer` | Posts job listings, manages own listings, reviews applications for own jobs | Yes |
| Job Seeker | `job_seeker` | Browses jobs, applies, views own applications | Yes |

**User model role helpers**

```php
$user->isAdmin();                          // bool
$user->isEmployer();                       // bool
$user->isJobSeeker();                      // bool
$user->hasRole('admin');                   // bool
$user->hasAnyRole(['admin', 'employer']);  // bool
```

**Seeded users**

| Name | Email | Role | Password |
|------|-------|------|----------|
| Sazzadul Islam | psazadul1205@gmail.com | admin | _Development seeder password (see `DatabaseSeeder`)_ |
| Sazzadul Islam | www.sazzadul14@gmail.com | employer | _Development seeder password (see `DatabaseSeeder`)_ |
| Psazzasul Islam | psazzadul@gmail.com | job_seeker | _Development seeder password (see `DatabaseSeeder`)_ |

> **Important:** Change or remove these seeded accounts before any production deployment.

---

## 8. RBAC (Role-Based Access Control)

RBAC is implemented with **inline role checks** in controllers. There are no separate Policy or Gate classes. The pattern:

```php
$user = Auth::user();

if (!$user->isJobSeeker()) {
    return redirect()->back()->with('error', 'Only job seekers can apply for jobs.');
}
```

### 8.1 Authorization Rules by Action

| Action | Allowed roles | Condition |
|--------|--------------|-----------|
| View all job listings (index) | All authenticated | No restriction |
| Create job listing | Employer, Admin | Sets `user_id` to the current user |
| Edit / update job listing | Employer, Admin | Employers can edit only their own listings (enforced at the frontend/route level) |
| Delete job listing | Employer, Admin | Blocked if applications exist |
| Toggle job listing active | Employer, Admin | None |
| Apply for a job | Job Seeker only | One application per job per user |
| View own applications | Job Seeker | `user_id === Auth::id()` |
| View applications for own jobs | Employer | `jobListing.user_id === Auth::id()` |
| View all applications | Admin | No restriction |
| Update application status | Admin, Employer | Employer must own the job listing |
| Bulk update status | Admin, Employer | Employer scoped to own job listings |
| Download resume | Job Seeker (own), Employer (job owner), Admin | None |
| Delete application | Admin, Job Seeker (own) | None |
| Merge resumes | Admin, Employer | Employer scoped to own job listings |
| Recalculate ATS score | Admin, Employer (job owner) | None |
| View analytics dashboard | All authenticated | Role is passed to the view |
| View job ATS analysis | Admin, Employer (job owner) | None |
| Manage categories | All authenticated (backend) | No role restriction in the controller |
| Manage locations | All authenticated (backend) | No role restriction in the controller |

### 8.2 Middleware

Routes are grouped under the `auth` and `verified` middleware (email verification required). There is no dedicated role middleware; role checks are performed inline in controller methods.

### 8.3 Route Groups

```text
web.php
├── Public routes (no auth)
│   ├── GET /jobs                        → JobListingController@publicIndex
│   └── GET /jobs/{jobListing}           → JobListingController@publicShow
│
├── Auth + verified routes
│   ├── GET /dashboard
│   ├── backend/
│   │   ├── locations/                   → LocationController (CRUD + toggle/restore/force-delete)
│   │   ├── categories/                  → JobCategoryController (CRUD + toggle/restore/force-delete)
│   │   ├── listing/                     → JobListingController (CRUD + toggle-active + applications)
│   │   └── application/                 → ApplicationController (index, bulk-status, merge-resumes,
│   │                                      show, update-status, download-resume, destroy,
│   │                                      recalculate-score)
│   ├── applications/{jobListing}/create → ApplicationController@create (job seeker)
│   ├── applications/{jobListing}        → ApplicationController@store (job seeker)
│   └── settings/                        → ProfileController, PasswordController
│
└── Auth routes (auth.php)
    ├── register                         → RegisteredUserController (role selection)
    ├── login / logout
    └── password reset / email verification
```

---

## 9. ATS Score Flow

```text
Job seeker submits application
        │
        ▼
Resume uploaded (PDF/DOC/DOCX, max 5 MB)
        │
        ▼
Application created with status = 'pending'
        │
        ▼
CalculateAtsScore job dispatched (queue)
        │
        ▼
ATSService::calculateScore()
        │
        ├─ Extract text from resume
        ├─ Extract keywords from job listing
        ├─ Match keywords (case-insensitive)
        ├─ Compute score = (matched / total) * 100
        ├─ Extract skills, experience, education
        └─ Generate analysis level
        │
        ▼
Application updated: ats_score, matched_keywords, missing_keywords (all JSON)
        │
        ▼
Employer views applications with ATS scores
        │
        ▼
Employer updates status: pending → reviewed → shortlisted / rejected / hired
```

---

## 10. Soft Deletes

The following models use `SoftDeletes`:

| Model | Column |
|-------|--------|
| `JobListing` | `job_listings.deleted_at` |
| `JobCategory` | `job_categories.deleted_at` |
| `Location` | `locations.deleted_at` |
| `Application` | `applications.deleted_at` |
| `ApplicantProfile` | `applicant_profiles.deleted_at` |

Soft-deleted records can be restored through the controllers' `restore()` methods or permanently removed with `forceDelete()`.

---

## 11. Key Design Decisions

1. **Role as an ENUM column.** Simpler than a separate roles table; the roles are fixed (`admin`, `employer`, `job_seeker`).
2. **Inline RBAC checks.** Authorization happens in controller methods rather than in Laravel Policies or Gates.
3. **JSON fields.** `skills`, `benefits`, `responsibilities`, and `keywords` on job listings, and `ats_score`, `matched_keywords`, and `missing_keywords` on applications, are stored as JSON for a flexible schema.
4. **Unique application constraint.** `[job_listing_id, user_id]` prevents duplicate applications.
5. **Queued ATS scoring.** The `CalculateAtsScore` job retries with backoff and falls back to inline execution if the queue fails.
6. **Automatic status management.** Job listings activate and deactivate automatically based on `schedule_start_date` and `application_deadline`.

---

## 12. Summary of Files

| File | Purpose |
|------|---------|
| `app/Models/User.php` | User model with role helpers and relationships |
| `app/Models/JobListing.php` | Job listing model with scopes and soft deletes |
| `app/Models/JobCategory.php` | Job category model |
| `app/Models/Location.php` | Location model |
| `app/Models/Application.php` | Application model with ATS methods and status helpers |
| `app/Http/Controllers/JobListingController.php` | Job listing CRUD and status management |
| `app/Http/Controllers/ApplicationController.php` | Application CRUD, ATS, analytics, and resume merge |
| `app/Http/Controllers/JobCategoryController.php` | Category CRUD |
| `app/Http/Controllers/LocationController.php` | Location CRUD |
| `app/Http/Controllers/Auth/RegisteredUserController.php` | User registration with role |
| `app/Services/ATSService.php` | ATS scoring engine |
| `app/Jobs/CalculateAtsScore.php` | Queued ATS score calculation |
| `routes/web.php` | Job portal routes (`backend` prefix) |
| `routes/auth.php` | Auth routes with role registration |
| `database/migrations/*` | All migrations (users, categories, locations, profiles, listings, applications, indexes) |
