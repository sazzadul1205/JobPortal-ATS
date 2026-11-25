# Job Portal ATS System Documentation (v2)

|                  |                                                                                                  |
| ---------------- | ------------------------------------------------------------------------------------------------ |
| **Project**      | Dwip-Unnayan-Sangstha-Website                                                                    |
| **Scope**        | Job portal, Applicant Tracking System (ATS), applicant profiles, and RBAC                        |
| **Stack**        | Laravel, Inertia.js, React, MySQL                                                                |
| **Source**       | `master` at commit `3030cf1` (2026-11-18), read directly from the repository                     |
| **Out of scope** | CMS (pages, blogs, programs, publications, shared data), newsletter, backup, audit-log internals |

> **Replaces the earlier version of this document.** That version described commit `645eefb` ("Completed job listing") and no longer matches the code. The main differences: roles are now a full permission system (not an ENUM), applications use saved CVs (not per-application uploads), ATS runs inline (there is no queued job), and nine tables were missing from the earlier schema. See [Section 13](#13-changes-from-the-previous-document).

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Database Schema](#2-database-schema)
3. [Models](#3-models)
4. [Application Flow](#4-application-flow)
5. [ATS Scoring](#5-ats-scoring)
6. [Controllers and Routes](#6-controllers-and-routes)
7. [RBAC](#7-rbac-role-based-access-control)
8. [Middleware, Rate Limits, and Scheduling](#8-middleware-rate-limits-and-scheduling)
9. [Notifications and Email](#9-notifications-and-email)
10. [Soft Deletes](#10-soft-deletes)
11. [Key Design Decisions](#11-key-design-decisions)
12. [Known Issues](#12-known-issues-verified-in-code)
13. [Changes from the Previous Document](#13-changes-from-the-previous-document)
14. [File Map](#14-file-map)

---

## 1. System Overview

The platform lets employers post job listings, lets job seekers build a profile with up to three CVs and apply to jobs, and scores each application against the job's keywords using an ATS engine. Staff review, shortlist, reject, or hire applicants from a permission-gated backend.

Four roles are seeded: **super-admin**, **admin**, **employer**, and **job-seeker**. Access is decided by permissions and module access levels, not by the role name alone (see [Section 7](#7-rbac-role-based-access-control)).

---

## 2. Database Schema

### 2.1 Table Inventory

| Table                                      | Purpose                                          |
| ------------------------------------------ | ------------------------------------------------ |
| `users`                                    | Accounts (email/password or Google OAuth)        |
| `password_reset_tokens`, `sessions`        | Laravel auth support                             |
| `applicant_profiles`                       | Job seeker profile (one per user)                |
| `applicant_cvs`                            | Uploaded CV files (max 3 active per profile)     |
| `job_histories`                            | Work experience entries on a profile             |
| `education_histories`                      | Education entries on a profile                   |
| `achievements`                             | Awards and achievements on a profile             |
| `job_categories`                           | Job categories                                   |
| `locations`                                | Job locations                                    |
| `job_listings`                             | Job postings                                     |
| `job_listing_location`                     | Pivot: a job can have several locations          |
| `job_views`                                | One row per job view (used for popular/trending) |
| `applications`                             | Job applications and ATS results                 |
| `status_timelines`                         | Status history for each application              |
| `notifications`                            | Laravel database notifications                   |
| `permissions`, `roles`, `role_permissions` | RBAC core                                        |
| `user_roles`                               | Role assignments (with expiry)                   |
| `role_module_access`                       | Per-role access level per module                 |
| `jobs`, `job_batches`, `failed_jobs`       | Laravel queue tables                             |

### 2.2 `users`

| Column                     | Type       | Notes                                          |
| -------------------------- | ---------- | ---------------------------------------------- |
| `id`                       | id         | PK                                             |
| `name`                     | string     |                                                |
| `email`                    | string     | Unique                                         |
| `email_verified_at`        | timestamp  | Nullable                                       |
| `password`                 | string     | Nullable (Google OAuth users have no password) |
| `google_id`                | string     | Nullable, unique                               |
| `google_avatar`            | string     | Nullable                                       |
| `remember_token`           | string     |                                                |
| `created_at`, `updated_at` | timestamps |                                                |
| `deleted_at`               | timestamp  | Soft delete                                    |

There is no `role` column. Roles come from `user_roles` ([Section 2.9](#29-rbac-tables)).

### 2.3 `applicant_profiles`

| Column                     | Type       | Notes                                 |
| -------------------------- | ---------- | ------------------------------------- |
| `id`                       | id         | PK                                    |
| `user_id`                  | foreignId  | FK → `users.id` (cascade), unique     |
| `first_name`, `last_name`  | string     | Nullable                              |
| `birth_date`               | date       | Nullable                              |
| `gender`                   | string     | Nullable                              |
| `blood_type`               | enum       | `A+ A- B+ B- AB+ AB- O+ O-`, nullable |
| `phone`                    | string(20) | Nullable                              |
| `address`                  | text       | Nullable                              |
| `photo_path`               | string     | Nullable                              |
| `social_links`             | json       | Nullable                              |
| `experience_years`         | integer    | Nullable                              |
| `current_job_title`        | string     | Nullable                              |
| `created_at`, `updated_at` | timestamps |                                       |
| `deleted_at`               | timestamp  | Soft delete                           |

### 2.4 `applicant_cvs`

| Column                     | Type       | Notes                                   |
| -------------------------- | ---------- | --------------------------------------- |
| `id`                       | id         | PK                                      |
| `applicant_profile_id`     | foreignId  | FK → `applicant_profiles.id` (cascade)  |
| `cv_path`                  | string     | Path on the `public` disk               |
| `original_name`            | string     | Nullable                                |
| `status`                   | enum       | `pending`, `active` (default `pending`) |
| `order_position`           | integer    | Default 0                               |
| `is_primary`               | boolean    | Default `false`                         |
| `created_at`, `updated_at` | timestamps |                                         |

**Indexes:** `applicant_profile_id`, `status`, `is_primary`  
**Unique:** `[applicant_profile_id, order_position]`

### 2.5 Profile detail tables

| Table                 | Columns                                                                                                                                                         | Soft delete |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------- |
| `job_histories`       | `id`, `applicant_profile_id` (FK, cascade), `company_name`, `position`, `starting_year` (year), `ending_year` (year, nullable), `is_current` (bool), timestamps | Yes         |
| `education_histories` | `id`, `applicant_profile_id` (FK, cascade), `institution_name`, `degree`, `passing_year` (year), timestamps                                                     | Yes         |
| `achievements`        | `id`, `applicant_profile_id` (FK, cascade), `achievement_name`, `achievement_details` (text), timestamps                                                        | Yes         |

**Indexes:** `job_histories` on `[applicant_profile_id, is_current]` and `starting_year`; `education_histories` on `applicant_profile_id` and `passing_year`; `achievements` on `applicant_profile_id`.

### 2.6 `job_categories` and `locations`

| Table            | Columns                                                                                        | Indexes     |
| ---------------- | ---------------------------------------------------------------------------------------------- | ----------- |
| `job_categories` | `id`, `name`, `slug` (unique), `is_active` (default true), timestamps, `deleted_at`            | `is_active` |
| `locations`      | `id`, `name`, `address` (text, nullable), `is_active` (default true), timestamps, `deleted_at` | `is_active` |

### 2.7 `job_listings`

| Column                                               | Type             | Notes                                           |
| ---------------------------------------------------- | ---------------- | ----------------------------------------------- |
| `id`                                                 | id               | PK                                              |
| `title`                                              | string           |                                                 |
| `slug`                                               | string           | Unique                                          |
| `description`, `requirements`                        | longText         |                                                 |
| `job_type`                                           | string           |                                                 |
| `salary_min`, `salary_max`                           | decimal(10,2)    | Nullable                                        |
| `is_salary_negotiable`                               | boolean          | Default `false`                                 |
| `as_per_companies_policy`                            | boolean          | Default `false`                                 |
| `category_id`                                        | foreignId        | FK → `job_categories.id` (cascade)              |
| `experience_level`                                   | string           |                                                 |
| `education_requirement`                              | string           | Nullable                                        |
| `education_details`                                  | string           | Nullable                                        |
| `benefits`, `skills`, `responsibilities`, `keywords` | json             | Nullable                                        |
| `application_deadline`                               | date             |                                                 |
| `publish_at`                                         | date             | Nullable                                        |
| `views_count`                                        | unsigned integer | Counter cache, default 0                        |
| `is_active`                                          | boolean          | Default `true`                                  |
| `user_id`                                            | foreignId        | FK → `users.id` (cascade); the employer         |
| `required_facebook_link`, `required_linkedin_link`   | boolean          | Default `false`; applicant must supply the link |
| `created_at`, `updated_at`                           | timestamps       |                                                 |
| `deleted_at`                                         | timestamp        | Soft delete                                     |

**Indexes:** `[is_active, application_deadline]`, `job_type`, `publish_at`, `user_id`, `category_id`, `[is_active, publish_at]`

**Pivot `job_listing_location`:** `id`, `job_listing_id` (FK, cascade), `location_id` (FK, cascade), timestamps. **Unique:** `[job_listing_id, location_id]`. Locations are many-to-many; `job_listings` has no `location_id` column.

**`job_views`:** `id`, `job_listing_id` (FK, cascade), `user_id` (FK, nullable, set null), `ip_address` (string 45, nullable), timestamps. **Indexes:** `[job_listing_id, user_id]`, `[job_listing_id, ip_address]`.

### 2.8 `applications` and `status_timelines`

| Column                                 | Type             | Notes                                                              |
| -------------------------------------- | ---------------- | ------------------------------------------------------------------ |
| `id`                                   | id               | PK                                                                 |
| `user_id`                              | foreignId        | FK → `users.id` (cascade); the applicant                           |
| `job_listing_id`                       | foreignId        | FK → `job_listings.id` (cascade)                                   |
| `applicant_profile_id`                 | foreignId        | Nullable, FK → `applicant_profiles.id` (set null)                  |
| `name`, `email`                        | string           |                                                                    |
| `phone`                                | string           | Nullable                                                           |
| `education_level`                      | string           | Nullable                                                           |
| `years_of_experience`                  | unsigned integer | Nullable                                                           |
| `resume_path`                          | string           | Nullable; copied from the chosen CV at apply time                  |
| `expected_salary`                      | decimal(12,2)    | Nullable                                                           |
| `ats_score`                            | json             | Nullable; full ATS result                                          |
| `matched_keywords`, `missing_keywords` | json             | Nullable                                                           |
| `ats_calculation_status`               | enum             | `pending`, `processing`, `completed`, `failed` (default `pending`) |
| `ats_attempt_count`                    | unsigned tinyint | Default 0                                                          |
| `ats_last_attempted_at`                | timestamp        | Nullable                                                           |
| `status`                               | enum             | `pending`, `shortlisted`, `rejected`, `hired` (default `pending`)  |
| `employer_notes`                       | text             | Nullable                                                           |
| `facebook_link`, `linkedin_link`       | text             | Nullable                                                           |
| `created_at`, `updated_at`             | timestamps       |                                                                    |
| `deleted_at`                           | timestamp        | Soft delete                                                        |

**Indexes:** `status`, `[job_listing_id, status]`, `user_id`, `[user_id, status]`, `created_at`  
**Unique:** `[job_listing_id, user_id]` (one application per user per job)

**`status_timelines`:** `id`, `application_id` (FK, cascade), `status` (string), `notes` (text, nullable), timestamps. **Index:** `application_id`.

### 2.9 RBAC tables

| Table                | Columns                                                                                                                                                                      |
| -------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `permissions`        | `id`, `name` (unique), `slug` (unique), `module`, `action`, `description`, `is_active`, timestamps. **Unique:** `[module, action]`                                           |
| `roles`              | `id`, `name` (unique), `slug` (unique), `description`, `level` (int), `is_default`, `is_active`, `created_by`, `updated_by` (FK → users, set null), timestamps, `deleted_at` |
| `role_permissions`   | `id`, `role_id` (FK), `permission_id` (FK), `granted` (bool, default true), timestamps. **Unique:** `[role_id, permission_id]`                                               |
| `user_roles`         | `id`, `user_id` (FK), `role_id` (FK), `assigned_by` (FK, nullable), `assigned_at`, `is_active`, `expires_at` (nullable), timestamps. **Unique:** `[user_id, role_id]`        |
| `role_module_access` | `id`, `role_id` (FK), `module`, `access_level` enum (`no_access`, `read`, `write`, `manage`; default `no_access`), timestamps. **Unique:** `[role_id, module]`               |

---

## 3. Models

All models are in `app/Models/`.

### 3.1 User

- **Uses:** `HasApiTokens`, `HasFactory`, `Notifiable`, `SoftDeletes`, `HasRoles`; implements `MustVerifyEmail`
- **Fillable:** `name`, `email`, `password`, `google_id`, `google_avatar`, `email_verified_at`
- **Appends:** `roles_list`, `permissions_list`
- **Relationships:** `applicantProfile()` (hasOne), `jobListings()`, `applications()`, `jobViews()`, `receivedApplications()` (hasManyThrough), `notifications()`, `unreadNotifications()`
- Sends custom password-reset and email-verification notifications.

### 3.2 ApplicantProfile

- **Uses:** `HasFactory`, `SoftDeletes`
- **Fillable:** `user_id`, `first_name`, `last_name`, `birth_date`, `gender`, `blood_type`, `phone`, `address`, `photo_path`, `social_links`, `experience_years`, `current_job_title`
- **Required fields for "complete":** `first_name`, `last_name`, `phone`
- **Relationships:** `user()`, `applications()`, `cvs()`, `primaryCv()`, `jobHistories()`, `currentJob()`, `educationHistories()`, `achievements()`
- **Methods:** `isComplete()`, `completionPercentage()` (7 fields: the three required plus `birth_date`, `gender`, `experience_years`, `current_job_title`), `getFullNameAttribute()`, scope `complete()`

### 3.3 ApplicantCv

- **Limit:** `MAX_CVS_PER_PROFILE = 3` active CVs (enforced in the `creating` hook with a validation error)
- **Fillable:** `applicant_profile_id`, `cv_path`, `original_name`, `order_position`, `is_primary`, `status`
- **Model hooks:**
  - `creating`: enforces the limit, assigns the next `order_position`, clears other primaries if this one is primary
  - `updating`: clears other primaries when this CV becomes primary
  - `deleting`: if the primary is deleted, another active CV is promoted
  - `deleted`: remaining CVs are re-ordered
- **Methods:** `setAsPrimary()`, `swapPosition()`, `reorderCvs()`, `getPrimaryCv()`, `hasReachedMaxEntries()`, `getRemainingSlots()`, `getUrlAttribute()`
- **Scopes:** `active()`, `primary()`, `ordered()`
- No soft deletes.

### 3.4 JobHistory, EducationHistory, Achievement

| Model              | Limit per profile                  | Validation in model hooks                                 |
| ------------------ | ---------------------------------- | --------------------------------------------------------- |
| `JobHistory`       | 3 (`MAX_ENTRIES_PER_PROFILE`)      | Year range 1900–2100; ending year checks when not current |
| `EducationHistory` | 3                                  | Year range 1900–2100                                      |
| `Achievement`      | 3 (`MAX_ACHIEVEMENTS_PER_PROFILE`) | Limit enforced on create; logs counts                     |

All three use `SoftDeletes` and belong to `ApplicantProfile`. `JobHistory` adds a `duration` accessor; `EducationHistory` has scope `latestFirst()`; `Achievement` has scopes `recent()` and `search()` plus `getStats()`.

### 3.5 JobListing

- **Uses:** `HasFactory`, `SoftDeletes`
- **Fillable:** `title`, `slug`, `description`, `requirements`, `job_type`, `salary_min`, `salary_max`, `is_salary_negotiable`, `as_per_companies_policy`, `category_id`, `experience_level`, `education_requirement`, `education_details`, `benefits`, `skills`, `responsibilities`, `keywords`, `application_deadline`, `publish_at`, `views_count`, `is_active`, `user_id`, `required_facebook_link`, `required_linkedin_link`
- **Casts:** JSON fields → array; `application_deadline`, `publish_at` → date; decimals and booleans as expected
- **Relationships:** `employer()` (belongsTo User), `category()`, `locations()` (belongsToMany), `applications()`, `views()`
- **Slug:** generated from `title` on create; regenerated on title change, with `-1`, `-2` suffixes to stay unique
- **Scopes:** `active()` (`is_active` and deadline ≥ now), `published()` (`publish_at` ≤ now), `byEmployer()`, `byCategory()`, `byJobType()`, `byLocation()`, `search()`
- **Methods:** `canApply()`, `isExpired()`, `incrementViews()`, `getSalaryRangeAttribute()`, label accessors for job type and experience level
- **Route binding:** `resolveRouteBinding()` includes soft-deleted listings

### 3.6 JobCategory and Location

- Both use `HasFactory`, `SoftDeletes`; fillable `name`, `slug`/`address`, `is_active`
- `JobCategory`: `jobListings()`, `activeJobListings()`, scope `active()`, `getJobCountAttribute()`
- `Location`: `jobListings()`, scope `active()`

### 3.7 Application

- **Uses:** `HasFactory`, `SoftDeletes`
- **Status constants:** `STATUS_PENDING`, `STATUS_SHORTLISTED`, `STATUS_REJECTED`, `STATUS_HIRED`
- **ATS constants:** `ATS_PENDING`, `ATS_PROCESSING`, `ATS_COMPLETED`, `ATS_FAILED`
- **Fillable:** `user_id`, `job_listing_id`, `applicant_profile_id`, `name`, `email`, `phone`, `education_level`, `years_of_experience`, `resume_path`, `expected_salary`, `ats_score`, `matched_keywords`, `missing_keywords`, `ats_last_attempted_at`, `ats_attempt_count`, `ats_calculation_status`, `status`, `employer_notes`, `facebook_link`, `linkedin_link`
- **Relationships:** `user()`, `jobListing()`, `applicantProfile()`, `statusTimelines()`
- **Scopes:** `pending()`, `shortlisted()`, `rejected()`, `hired()`, `byJob()`, `byEmployer()`, `minAtsScore()`, `atsScoreBetween()`
- **Methods:**
  - `updateStatus($status, $notes)`: updates the status and notes, writes a `status_timelines` row, and sends an `ApplicationStatusUpdated` notification to the applicant
  - `getActualResumePath()`: uses `resume_path`, falling back to the profile's primary CV
  - `calculateATSScore()`: marks `processing`, calls `ATSService`, stores the result, marks `completed` (or `failed` with an error payload and increments `ats_attempt_count`)
  - `recalculateAtsScoreInline()`: resets ATS fields and recalculates
  - `isAtsCompleted()`, `isAtsCalculationStuck($minutes = 30)`, `canBeUpdated()` (false for hired or rejected)

### 3.8 StatusTimeline and JobView

- `StatusTimeline`: fillable `application_id`, `status`, `notes`; belongs to `Application`.
- `JobView`: fillable `job_listing_id`, `user_id`, `ip_address`; static `recordView($jobId, $userId, $ip)`.

### 3.9 RBAC models

| Model              | Notes                                                                                                                                                                                                                                                                                                                                                            |
| ------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Role`             | Fillable `name`, `slug`, `description`, `level`, `is_default`, `is_active`, `created_by`, `updated_by`. Relationships: `users()`, `activeUsers()`, `permissions()`, `grantedPermissions()`, `moduleAccess()`. Methods: `grantPermission()`, `revokePermission()`, `setModuleAccess()`, `canAccessModule()`, `getPermissionsGroupedByModule()`. Uses soft deletes |
| `Permission`       | Fillable `name`, `slug`, `module`, `action`, `description`, `is_active`. Scopes `active()`, `byModule()`, `byAction()`                                                                                                                                                                                                                                           |
| `RoleModuleAccess` | Constants `ACCESS_NO_ACCESS`, `ACCESS_READ`, `ACCESS_WRITE`, `ACCESS_MANAGE`; `isAtLeast($level)`                                                                                                                                                                                                                                                                |
| `UserRole`         | Pivot with `assigned_by`, `assigned_at`, `is_active`, `expires_at`; scopes `active()`, `expired()`; `expire()`                                                                                                                                                                                                                                                   |

---

## 4. Application Flow

### 4.1 Profile first

A user with only the `job-seeker` role is redirected to `/complete-profile` until `first_name`, `last_name`, and `phone` are filled (`profile.complete` middleware, result cached 60 seconds). Users who also hold any other role bypass this check.

On the profile page the applicant can add:

- Photo (jpeg, png, jpg, gif; max 2 MB)
- Up to 3 CVs (pdf, doc, docx; max 5 MB each)
- Up to 3 job histories, 3 education entries, and 3 achievements

### 4.2 Applying

```text
GET  /backend/apply/create/{slug}      → ApplyController@create
POST /backend/apply/store/{slug}       → ApplyController@store
```

1. The job must be active, not soft-deleted, and not past its deadline.
2. If the user already has an application for the job, it is rejected. A soft-deleted earlier application is force-deleted (with its resume file) so the user can re-apply.
3. Validation: `cv_id` (required, must exist in `applicant_cvs`), `name`, `email`, `phone`, `expected_salary`, `cover_letter` (accepted by validation but not stored), plus `linkedin_link` and/or `facebook_link` when the job requires them.
4. The application is created with `status = pending`, `ats_calculation_status = pending`, and `resume_path` copied from the chosen CV.
5. The ATS score is calculated **inline** in the same request. If it fails, the user is told and can retry from the application page.
6. Rate limit: `apply_store` (10 attempts per hour by default).

### 4.3 Review

Staff open `/backend/applications`, filter by status, job, category, location, job type, education level, and minimum ATS score, then:

- Update status (single or bulk), which writes a timeline row and notifies the applicant
- Send an email to one or many applicants
- Download one resume or a ZIP/merged bundle
- Export applications (single or per job)
- Recalculate the ATS score

Statuses: `pending → shortlisted / rejected / hired`. There is no `reviewed` status.

---

## 5. ATS Scoring

**File:** `app/Services/ATSService.php`  
**Entry point:** `calculateScore(Application $application, JobListing $jobListing): array`

### 5.1 Process

1. Get the resume path (`Application::getActualResumePath()`).
2. Extract text:
    - **PDF:** `smalot/pdfparser`, falling back to the `pdftotext` command if it is available
    - **DOCX:** read `word/document.xml` from the zip and strip tags
    - **DOC:** `antiword` if available, otherwise a raw-byte text scrape
3. Build the keyword list from the job's `keywords` field only: trimmed, lowercased, whitespace collapsed, empty values and duplicates removed, capped at 100.
4. Match each keyword against the resume text, case-insensitive:
    - Single words: whole-word regex match
    - Multi-word phrases: substring match
5. Score = `(matched keywords / total keywords) * 100`, rounded to two decimals.
6. Build an analysis block and suggestions.

If there is no resume, no extractable text, or no job keywords, a default score of 0 with an explanatory error is returned.

### 5.2 Result structure (`applications.ats_score`)

```php
[
    'percentage' => float,
    'matched_keywords' => [],
    'missing_keywords' => [],
    'matched_count' => int,
    'total_keywords' => int,
    'extracted_skills' => [],             // always empty (not implemented)
    'extracted_experience_years' => 0,    // always 0 (not implemented)
    'extracted_education' => 'Not specified',  // always this value (not implemented)
    'analysis' => [
        'level' => 'Excellent|Good|Fair|Needs Improvement|Error',
        'message' => string,
        'color' => 'green|blue|yellow|red',
        'matched_count' => int,
        'missing_count' => int,
        'top_matched' => [],              // first 10
        'top_missing' => [],              // first 10
        'suggestions' => [],
    ],
    'calculated_at' => string,
]
```

### 5.3 Levels

| Level             | Score |
| ----------------- | ----- |
| Excellent         | ≥ 80  |
| Good              | 60–79 |
| Fair              | 40–59 |
| Needs Improvement | < 40  |

### 5.4 Calculation status

`pending → processing → completed`, or `failed` (with an error payload in `ats_score`). `isAtsCalculationStuck()` reports a pending or processing application whose last attempt is older than 30 minutes. Staff and applicants can retry with `recalculate-ats`; the applicant can poll `ats-status`.

> The earlier document described a queued `CalculateAtsScore` job, skill extraction, experience extraction, and education extraction. None of those exist in the current code. The only job class is `SendNewsletterBulkEmail`.

---

## 6. Controllers and Routes

### 6.1 Route files

| File                                                                                                 | Contents                                                                                 |
| ---------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| `routes/web.php`                                                                                     | Includes the other files                                                                 |
| `routes/auth.php`                                                                                    | Staff and seeker login, registration, Google OAuth, password reset, verification         |
| `routes/job-seeker.php`                                                                              | Profile completion, photo and CV endpoints, seeker job browsing                          |
| `routes/admin/dashboard.php`                                                                         | The `auth`, `verified`, `profile.complete` group that mounts every `backend/` route file |
| `routes/admin/job-listings.php`                                                                      | Listings and statistics                                                                  |
| `routes/admin/applications.php`                                                                      | Application management                                                                   |
| `routes/apply.php`                                                                                   | Applicant-side applications                                                              |
| `routes/categories.php`, `routes/locations.php`                                                      | Category and location management                                                         |
| `routes/applicant-profiles.php`, `routes/admin/applicant-profiles.php`, `routes/admin/applicant.php` | Applicant profile management                                                             |
| `routes/notifications.php`                                                                           | Notifications                                                                            |
| `routes/api.php`                                                                                     | Public content and job APIs                                                              |
| `routes/public.php`                                                                                  | Public pages                                                                             |

### 6.2 Job listings: `JobListing\JobListingController`

Backend routes (prefix `backend/listing`):

| Route                                                            | Method         | Permission checked           |
| ---------------------------------------------------------------- | -------------- | ---------------------------- |
| `GET /`                                                          | `adminIndex`   | `job_listings.view`          |
| `GET /create`                                                    | `adminCreate`  | `job_listings.create`        |
| `POST /`                                                         | `adminStore`   | `job_listings.store`         |
| `GET /{job}`                                                     | `adminShow`    | `job_listings.show`          |
| `GET /{job}/edit`                                                | `adminEdit`    | `job_listings.edit`          |
| `PUT /{job}`                                                     | `adminUpdate`  | `job_listings.update`        |
| `DELETE /{job}`                                                  | `adminDestroy` | `job_listings.destroy`       |
| `PATCH /{job}/toggle-active`                                     | `toggleActive` | `job_listings.toggle_active` |
| `PATCH /{job}/restore`                                           | `restore`      | `job_listings.restore`       |
| `DELETE /{job}/force-delete`                                     | `forceDelete`  | `job_listings.force_delete`  |
| `GET /{job}/applications`                                        | `applications` | `job_listings.applications`  |
| `POST /bulk-activate`, `/bulk-deactivate`; `DELETE /bulk-delete` | bulk actions   | `job_listings.bulk_*`        |
| `GET backend/statistics`                                         | `statistics`   | `job_listings.statistics`    |

Other behavior: `updateJobStatuses()` (run by the scheduler), automatic active-status from dates, view recording, related jobs.

### 6.3 Public jobs

- `JobListing\PublicJobListingController`: `index`, `show`, `popular`, `trending` (also used under `backend/seeker/jobs`)
- `Api\JobListingApiController`: JSON API

| Endpoint                       | Description          |
| ------------------------------ | -------------------- |
| `GET /api/jobs`                | Paginated jobs       |
| `GET /api/jobs/filter-options` | Filter options       |
| `GET /api/jobs/popular`        | Popular jobs         |
| `GET /api/jobs/trending`       | Trending jobs        |
| `GET /api/jobs/{identifier}`   | One job (ID or slug) |
| `GET /api/jobs/{slug}/related` | Related jobs         |

### 6.4 Applications (staff): `Backend\ApplicationsController`

Prefix `backend/applications`. Each action checks a permission.

| Route                                         | Method                       | Permission                                                |
| --------------------------------------------- | ---------------------------- | --------------------------------------------------------- |
| `GET /`                                       | `index`                      | `applications.view`                                       |
| `GET /job/{jobId}`                            | `jobApplications`            | `applications.job_applications`                           |
| `GET /{id}`                                   | `show`                       | `applications.show`                                       |
| `PUT /{id}/status`                            | `updateStatus`               | `applications.status.update`                              |
| `POST /bulk-status`                           | `bulkUpdateStatus`           | `applications.bulk_status.update`                         |
| `DELETE /{id}`                                | `destroy`                    | `applications.destroy`                                    |
| `POST /bulk-delete`                           | `bulkDelete`                 | `applications.bulk_delete`                                |
| `GET /{id}/download-resume`                   | `downloadResume`             | `applications.download_resume`                            |
| `POST /bulk-download`                         | `bulkDownloadResumes`        | `applications.bulk_download_resumes`                      |
| `POST /{id}/send-email`, `/bulk-send-email`   | `sendEmail`, `sendBulkEmail` | `applications.email.send`, `applications.bulk_email.send` |
| `POST /{id}/recalculate-ats`                  | `recalculateAts`             | `applications.recalculate_ats`                            |
| `POST /export/{jobId}`, `/export-single/{id}` | exports                      | `applications.export`, `applications.export_single`       |

Bulk resume download merges PDFs using Fpdi, Ghostscript, or pdftk, with a ZIP fallback.

### 6.5 Applications (applicant): `Backend\ApplyController`

Prefix `backend/apply`. These actions are scoped by `user_id` to the logged-in user and have no permission checks.

`index`, `create/{slug}`, `store/{slug}`, `show/{id}`, `edit/{id}`, `update/{id}`, `destroy/{id}`, `restore/{id}`, `force-delete/{id}`, `trashed`, `recalculate-ats/{id}`, `ats-status/{id}`.

### 6.6 Profile and auth controllers

| Controller                                                               | Role                                                        |
| ------------------------------------------------------------------------ | ----------------------------------------------------------- |
| `Auth\JobSeeker\JobSeekerRegisterController`, `JobSeekerLoginController` | Seeker registration and login (`/login/seeker`)             |
| `Auth\AdminStaff\AdminLoginController`                                   | Staff login (`/login/staff`)                                |
| `Auth\Shared\GoogleAuthController`                                       | Google OAuth                                                |
| `Auth\JobSeeker\ProfileCompletionController`                             | Profile completion, photo, and CV upload/delete/set-primary |
| `Profile\ApplicantProfileController`                                     | Staff and own-profile management                            |
| `Profile\EmployerProfileController`, `AdminProfileController`            | Employer and admin profiles                                 |

### 6.7 Management controllers

`Backend\JobCategoryController`, `LocationController` (CRUD, toggle active, bulk actions, restore, force delete), `RoleController`, `UserController`, `NotificationController`, `DashboardController`.

---

## 7. RBAC (Role-Based Access Control)

### 7.1 Model

Three layers:

1. **Roles** (`roles`) with a numeric `level` for hierarchy.
2. **Permissions** (`permissions`), about 357 seeded entries named `module.action` (for example `applications.status.update`), linked to roles through `role_permissions.granted`.
3. **Module access** (`role_module_access`), an access level per role per module: `no_access < read < write < manage`.

A user can hold several roles. Assignments can expire (`user_roles.expires_at`) and can be deactivated (`is_active`).

### 7.2 Seeded roles

| Role        | Slug          | Level | Default | Permissions                                                                                                                   |
| ----------- | ------------- | ----- | ------- | ----------------------------------------------------------------------------------------------------------------------------- |
| Super Admin | `super-admin` | 100   | No      | All permissions; `hasPermission()` always returns true                                                                        |
| Admin       | `admin`       | 90    | No      | All permissions; `manage` on every module                                                                                     |
| Employer    | `employer`    | 50    | No      | About 50 named permissions (below)                                                                                            |
| Job Seeker  | `job-seeker`  | 20    | Yes     | None; module access is `no_access` for `dashboard`, `public_jobs`, `apply`, `profiles`, `applicant_profiles`, `notifications` |

**Employer permissions:** dashboard (`view`, `stats.view`, `employer`); `job_listings.*` (view, create, store, edit, update, show, destroy, toggle_active, applications); `applications.*` (view, `view.for_own_jobs`, show, status update, bulk status update, download resume, bulk download, email, bulk email); categories and locations (view, get_active); employer profile (view, edit, update, update_password); notifications; statistics (view, ats, jobs, dashboard); applicant profiles (view, view.any, show).

**Employer module access:** `write` on dashboard, job_listings, applications, employer_profile, notifications; `read` on categories, locations, statistics, applicant_profiles, profiles, public_jobs.

### 7.3 Enforcement

- Controllers call `$user->hasPermission('<slug>')` per action and redirect to `unauthorized.access` when it fails.
- `HasRoles` (the trait on `User`) provides: `hasRole()`, `hasAnyRole()`, `hasAllRoles()`, `assignRole()`, `assignRoles()`, `syncRoles()`, `removeRole()`, `getPrimaryRole()`, `hasPermission()`, `hasAnyPermission()`, `hasAllPermissions()`, `getAllPermissions()`, `getModuleAccess()`, `canAccess($module, $level)`, `clearPermissionCache()`.
- Role, permission, and module-access lookups are cached for 300 seconds per user, so changes can take up to five minutes to apply unless the cache is cleared.
- There is no permission middleware; checks live inside controller methods. The only RBAC-related middleware is `profile.complete`.

### 7.4 Seeders

`database/seeders/RBAC/`: `RolesSeeder`, `PermissionsSeeder`, `RolePermissionsSeeder`, `ModuleAccessSeeder`, `UserRolesSeeder`, orchestrated by `RBACSeeder`.

---

## 8. Middleware, Rate Limits, and Scheduling

### 8.1 Middleware

| Middleware                                            | Purpose                                                |
| ----------------------------------------------------- | ------------------------------------------------------ |
| `auth`, `verified`                                    | Login and verified email                               |
| `profile.complete` (`EnsureApplicantProfileComplete`) | Forces seeker-only users to finish their profile first |
| `AuditMutations`                                      | Audit-logs every state-changing web request            |
| `SecurityHeaders`                                     | Security headers                                       |
| `SanitizeInput`                                       | Detects SQL injection and XSS attempts                 |
| `PerformanceMonitor`                                  | Execution time tracking                                |
| `StaticAssetCache` (`static.cache`)                   | Static asset cache headers                             |

### 8.2 Rate limits

Named limiters: `web`, `api`, `auth`, `password_reset`, `register`, `email_verification`, `profile-cv` (CV upload). Controllers also add per-action limits, for example `apply_store`, `application_status_update`, `application_recalculate_ats`, `job_update`, `job_delete`.

### 8.3 Scheduled tasks (`bootstrap/app.php`)

| Command              | Schedule    | Purpose                                                                                        |
| -------------------- | ----------- | ---------------------------------------------------------------------------------------------- |
| `jobs:update-status` | Hourly      | Activate or deactivate jobs based on dates (calls `JobListingController::updateJobStatuses()`) |
| `audit:prune`        | Daily 03:30 | Remove old audit logs                                                                          |
| `logs:prune`         | Daily 04:00 | Remove old system logs                                                                         |

---

## 9. Notifications and Email

- **`ApplicationStatusUpdated`** (database channel): sent to the applicant when `Application::updateStatus()` runs. Message example: "Your application for {job} moved from {old} to {new}."
- **`ApplicationEmail`** (mail): used by `sendEmail` and `sendBulkEmail` for staff-to-applicant messages.
- Templates in `resources/views/emails/`: `application`, `shortlisted`, plus newsletter, password reset, and verification templates.

---

## 10. Soft Deletes

| Uses `SoftDeletes`                                                                                                                          | Does not                                                                                 |
| ------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| `User`, `ApplicantProfile`, `JobHistory`, `EducationHistory`, `Achievement`, `JobListing`, `JobCategory`, `Location`, `Application`, `Role` | `ApplicantCv`, `StatusTimeline`, `JobView`, `Permission`, `UserRole`, `RoleModuleAccess` |

Controllers expose `restore` and `forceDelete` for listings, categories, locations, applications, and applicant profiles.

---

## 11. Key Design Decisions

1. **Permission-based RBAC** instead of a role ENUM: roles are editable data, with module access levels and expiring assignments.
2. **Inline ATS calculation**: scored during the apply request, with `pending/processing/completed/failed` status, an attempt counter, and a manual retry. There is no queue dependency for scoring.
3. **Saved CVs**: applicants maintain up to three CVs on their profile and pick one when applying.
4. **Keyword-only scoring**: the score depends entirely on the keywords the employer enters on the job.
5. **Many-to-many locations** through `job_listing_location`.
6. **Status history** in `status_timelines`, with a notification on each change.
7. **JSON columns** for job `benefits`, `skills`, `responsibilities`, `keywords`, and application ATS data.
8. **Unique `[job_listing_id, user_id]`** prevents duplicate applications; soft-deleted applications are force-deleted to allow re-applying.
9. **Automatic job status** from `publish_at` and `application_deadline`, enforced by an hourly command.
10. **Per-model limits** (3 CVs, 3 job histories, 3 education entries, 3 achievements) enforced in model hooks.

---

## 12. Known Issues (verified in code)

These come from reading the source at `3030cf1`. Each is worth reviewing before production use.

| #   | Issue                                                                                                                                                                                                                                                        | Where                                                                                               | Impact                                                                                                            |
| --- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| 1   | **Employers are not limited to their own applications.** The permission `applications.view.for_own_jobs` is seeded and given to employers, but nothing in the code checks it. Application actions load records with `findOrFail($id)` and no ownership check | `ApplicationsController` (`index`, `show`, `updateStatus`, `downloadResume`, `destroy`, and others) | Any employer with these permissions can view, change, download, and delete applications for other employers' jobs |
| 2   | **Employers can edit or delete any listing.** `adminEdit`, `adminUpdate`, and `adminDestroy` check only the permission, not that `job.user_id` matches the current user                                                                                      | `JobListingController`                                                                              | Any employer can modify another employer's listing                                                                |
| 3   | **An applicant can attach another user's CV.** `store()` validates only that `cv_id` exists, then loads `ApplicantCv::findOrFail($cv_id)` without checking it belongs to the applicant's profile. That CV's `cv_path` is copied to the application           | `ApplyController@store`                                                                             | Access to another user's CV file                                                                                  |
| 4   | **Employer statistics query roles that are not seeded.** It filters by the slugs `employer-admin`, `hr-manager`, and `recruiter`; the seeded employer role is `employer`                                                                                     | `JobListingController@statistics` (and the filter options)                                          | Employer lists and rankings can come back empty                                                                   |
| 5   | **Apply routes have no permission or role check.** Any verified, logged-in user (including staff) can apply. Job-seeker module access for `apply` is `no_access`, but it is not enforced                                                                     | `routes/apply.php`, `ApplyController`                                                               | Role data and runtime behavior disagree                                                                           |
| 6   | **ATS output fields that are never filled.** `extracted_skills`, `extracted_experience_years`, and `extracted_education` are always empty/0/"Not specified"                                                                                                  | `ATSService`                                                                                        | The UI or docs may promise data that is not produced                                                              |
| 7   | **`cover_letter` is validated but not saved.**                                                                                                                                                                                                               | `ApplyController@store`                                                                             | Applicant input is silently dropped                                                                               |

---

## 13. Changes from the Previous Document

| Topic                      | Previous document                                                                       | Current code                                                                                                                                                           |
| -------------------------- | --------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Roles                      | `users.role` ENUM (`admin`, `employer`, `job_seeker`) and inline checks                 | `roles`, `permissions`, `role_permissions`, `user_roles`, `role_module_access`; `HasRoles` trait; roles `super-admin`, `admin`, `employer`, `job-seeker`               |
| Tables missing             | n/a                                                                                     | `applicant_cvs`, `job_histories`, `education_histories`, `achievements`, `status_timelines`, `job_listing_location`, `job_views`, `notifications`, and the RBAC tables |
| Applicant profile          | Had `email`, `cv_path`                                                                  | No `email` or `cv_path`; adds `gender`, `blood_type`, `address`, `social_links`, `experience_years`, `current_job_title`                                               |
| Job listing                | `location_id`, `salary` string, `schedule_start_date`, `show_linkedin`, `show_facebook` | Many-to-many locations; `salary_min`, `salary_max`, negotiable flags, `publish_at`, `views_count`, `education_details`, `required_*_link`                              |
| Application                | Statuses incl. `reviewed`; resume uploaded per application                              | No `reviewed`; adds ATS status and attempt tracking, links; resume copied from a saved CV                                                                              |
| ATS job                    | Queued `CalculateAtsScore` with retries                                                 | Inline calculation; no such job                                                                                                                                        |
| ATS keywords               | Job keywords plus title/description/requirements words                                  | `keywords` field only                                                                                                                                                  |
| Controllers                | `ApplicationController`, `JobListingController` in `Controllers/`                       | `Backend\ApplicationsController`, `Backend\ApplyController`, `JobListing\JobListingController`, and the profile/auth controllers listed in Section 6                   |
| Seeded users and passwords | Listed with passwords                                                                   | Removed from this document                                                                                                                                             |

---

## 14. File Map

| Path                                                                                                                           | Purpose                              |
| ------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------ |
| `app/Models/User.php`, `ApplicantProfile.php`, `ApplicantCv.php`, `JobHistory.php`, `EducationHistory.php`, `Achievement.php`  | Users and applicant data             |
| `app/Models/JobListing.php`, `JobCategory.php`, `Location.php`, `JobView.php`                                                  | Jobs                                 |
| `app/Models/Application.php`, `StatusTimeline.php`                                                                             | Applications                         |
| `app/Models/Role.php`, `Permission.php`, `RoleModuleAccess.php`, `UserRole.php`                                                | RBAC                                 |
| `app/Traits/HasRoles.php`                                                                                                      | Role and permission checks on `User` |
| `app/Services/ATSService.php`                                                                                                  | ATS scoring                          |
| `app/Http/Controllers/Backend/ApplicationsController.php`, `ApplyController.php`                                               | Applications                         |
| `app/Http/Controllers/JobListing/JobListingController.php`, `PublicJobListingController.php`                                   | Jobs                                 |
| `app/Http/Controllers/Api/JobListingApiController.php`                                                                         | Jobs API                             |
| `app/Http/Controllers/Profile/ApplicantProfileController.php`, `Auth/JobSeeker/ProfileCompletionController.php`                | Profiles                             |
| `app/Http/Controllers/Backend/JobCategoryController.php`, `LocationController.php`, `RoleController.php`, `UserController.php` | Management                           |
| `app/Http/Middleware/EnsureApplicantProfileComplete.php`                                                                       | Profile gate                         |
| `app/Console/Commands/UpdateJobStatuses.php`                                                                                   | Hourly job status update             |
| `app/Notifications/ApplicationStatusUpdated.php`, `app/Mail/ApplicationEmail.php`                                              | Notifications and email              |
| `database/migrations/*`                                                                                                        | Schema (35 files)                    |
| `database/seeders/RBAC/*`                                                                                                      | Roles, permissions, module access    |
| `routes/*`, `routes/admin/*`                                                                                                   | Route definitions                    |
