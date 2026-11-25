# Job Portal Pages & Controllers Technical Documentation

| | |
|---|---|
| **Project** | Dwip-Unnayan-Sangstha-Website |
| **Scope** | Job Portal / ATS: pages, controllers, routing, and UI components |
| **Stack** | Laravel, Inertia.js, React, Tailwind CSS |
| **Status** | Finalized, pre-CMS integration |
| **Branch** | `master` (commit `645eefb`, "Completed job listing") |

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Routing Architecture](#2-routing-architecture)
3. [Controllers](#3-controllers)
4. [Pages](#4-pages-inertia-react-components)
5. [Components](#5-components)
6. [Data Flow](#6-data-flow)
7. [Controller–Page Mapping](#7-controllerpage-mapping)
8. [Business Logic](#8-business-logic)
9. [Flash Messages](#9-flash-message-handling)
10. [File Reference Index](#10-file-reference-index)
11. [Known Issues & Recommendations](#11-known-issues--recommendations)

---

## 1. System Overview

The Job Portal follows the Inertia.js architecture: Laravel controllers are the data layer, and React components are the pages. A controller returns an Inertia response, and the matching React page receives the data as props.

Most routes require authentication and email verification. Only the public job browsing routes (`/jobs`, `/jobs/{jobListing}`) are open to guests.

Pages fall into three families:

| Family | Location | Purpose |
|--------|----------|---------|
| Backend | `resources/js/pages/Backend/` | Admin/employer management: job listings, categories, locations |
| Applications | `resources/js/pages/applications/` | Applying, reviewing, and tracking applications |
| Public jobs | `resources/js/pages/jobs/` | Browsing and viewing jobs |

---

## 2. Routing Architecture

Routes are defined in `routes/web.php` and `routes/auth.php`. Backend routes use the URL prefix `backend/` and the route-name prefix `backend.`.

### 2.1 Route Groups

```text
web.php
├── Public routes (no authentication)
│   ├── GET    /jobs                                  → JobListingController@publicIndex
│   └── GET    /jobs/{jobListing}                     → JobListingController@publicShow
│
├── Authenticated + verified routes
│   ├── GET    /dashboard
│   │
│   ├── backend/
│   │   ├── locations/                                → LocationController
│   │   │   ├── GET    /                              → index
│   │   │   ├── POST   /                              → store
│   │   │   ├── GET    /active                        → getActiveLocations (JSON)
│   │   │   ├── PUT    /{location}                    → update
│   │   │   ├── PATCH  /{location}/toggle             → toggleActive
│   │   │   ├── PATCH  /{location}/restore            → restore
│   │   │   ├── DELETE /{location}                    → destroy
│   │   │   └── DELETE /{location}/force-delete       → forceDelete
│   │   │
│   │   ├── categories/                               → JobCategoryController
│   │   │   ├── GET    /                              → index
│   │   │   ├── POST   /                              → store
│   │   │   ├── GET    /active                        → getActiveCategories (JSON)
│   │   │   ├── PUT    /{category}                    → update
│   │   │   ├── PATCH  /{category}/toggle             → toggleActive
│   │   │   ├── PATCH  /{category}/restore            → restore
│   │   │   ├── DELETE /{category}                    → destroy
│   │   │   └── DELETE /{category}/force-delete       → forceDelete
│   │   │
│   │   ├── listing/                                  → JobListingController
│   │   │   ├── GET    /                              → index
│   │   │   ├── GET    /create                        → create
│   │   │   ├── POST   /                              → store
│   │   │   ├── GET    /{jobListing}                  → show
│   │   │   ├── GET    /{jobListing}/edit             → edit
│   │   │   ├── PUT    /{jobListing}                  → update
│   │   │   ├── DELETE /{jobListing}                  → destroy
│   │   │   ├── PATCH  /{jobListing}/toggle-active    → toggleActive
│   │   │   └── GET    /{jobListing}/applications     → applications
│   │   │
│   │   └── application/                              → ApplicationController
│   │       ├── GET|POST /                            → index (with filters)
│   │       ├── PATCH  /bulk-status                   → bulkUpdateStatus
│   │       ├── GET    /merge-resumes                 → mergeResumes
│   │       ├── GET    /{application}                 → show
│   │       ├── PATCH  /{application}/status          → updateStatus
│   │       ├── GET    /{application}/resume          → downloadResume
│   │       ├── DELETE /{application}                 → destroy
│   │       └── POST   /{application}/recalculate-score → recalculateScore
│   │
│   ├── applications/{jobListing}/create              → ApplicationController@create (job seeker)
│   ├── applications/{jobListing}                     → ApplicationController@store (job seeker)
│   │
│   └── settings/
│       ├── GET    /profile                           → ProfileController@edit
│       ├── PATCH  /profile                           → ProfileController@update
│       ├── DELETE /profile                           → ProfileController@destroy
│       ├── GET    /password                          → PasswordController@edit
│       └── PUT    /password                          → PasswordController@update
│
└── Auth routes (auth.php)
    ├── register                                      → RegisteredUserController (role selection)
    ├── login / logout                                → AuthenticatedSessionController
    └── password reset / email verification
```

### 2.2 Route Protection

All backend routes use:

- `auth`: the user must be logged in.
- `verified`: the user's email must be verified.

Role-based authorization is enforced **inline in controller methods**; there is no dedicated role middleware. Controllers call `isAdmin()`, `isEmployer()`, and `isJobSeeker()` on the authenticated user before allowing an action.

---

## 3. Controllers

### 3.1 JobListingController (`app/Http/Controllers/JobListingController.php`)

Full CRUD for job listings, with automatic status management based on dates.

**Dependencies:** `JobListing`, `JobCategory`, `Location`, `Auth`, `Inertia`, `Str`, `Carbon`, `Log`

| Method | HTTP | Route name | Description |
|--------|------|------------|-------------|
| `index()` | GET | `backend.listing.index` | List all job listings, eager-loading category, location, and user |
| `create()` | GET | `backend.listing.create` | Render the create form with active categories and locations |
| `store()` | POST | `backend.listing.store` | Create a listing: sets `user_id = Auth::id()`, generates the slug, determines the initial active status from dates |
| `show()` | GET | `backend.listing.show` | Show a single listing with its relationships |
| `edit()` | GET | `backend.listing.edit` | Render the edit form, pre-populated |
| `update()` | PUT | `backend.listing.update` | Update a listing: regenerates the slug if the title changed, recalculates the active status |
| `destroy()` | DELETE | `backend.listing.destroy` | Soft-delete (blocked if applications exist) |
| `toggleActive()` | PATCH | `backend.listing.toggle-active` | Toggle `is_active` |
| `applications()` | GET | `backend.listing.applications` | List applications for a specific listing |

All routes require `auth` + `verified`. The controller applies no role restriction to these methods (see [11](#11-known-issues--recommendations)).

**Protected methods**

| Method | Purpose |
|--------|---------|
| `updateJobStatuses()` | Scheduled task: activates jobs past `schedule_start_date` and deactivates jobs past `application_deadline` |
| `determineInitialStatus(array $data)` | Determines `is_active` on creation from the deadline and start dates |
| `determineStatusFromDates(array $data, JobListing $existingJob)` | Determines `is_active` on update from the dates |

**Slug logic:** Generated from the title on create and regenerated when the title changes on update. A `-N` counter is appended to keep it unique.

### 3.2 ApplicationController (`app/Http/Controllers/ApplicationController.php`)

Handles the application lifecycle, ATS scoring, status management, resume handling, and analytics. The constructor injects `ATSService`. All routes require `auth` + `verified`.

| Method | HTTP | Route name | RBAC | Description |
|--------|------|------------|------|-------------|
| `index()` | GET/POST | `application.index` | Job seeker: own. Employer: own jobs. Admin: all. | List applications with role-based filtering and ATS score filters |
| `create()` | GET | `applications.create` | Job seeker only | Render the application form for a job |
| `store()` | POST | `applications.store` | Job seeker only | Create the application; validates the resume (pdf/doc/docx, 5 MB max); dispatches `CalculateAtsScore` |
| `show()` | GET | `application.show` | Owner (seeker) or job owner (employer) | Show application details with the ATS score breakdown |
| `updateStatus()` | PATCH | `application.update-status` | Admin or job owner (employer) | Update status (`pending`, `reviewed`, `shortlisted`, `rejected`, `hired`) and employer notes |
| `bulkUpdateStatus()` | PATCH | `application.bulk-status` | Admin or employer | Bulk-update applications; employers are scoped to their own listings |
| `mergeResumes()` | GET | `application.merge-resumes` | Admin or employer | Merge selected PDF resumes into one PDF (Fpdi) |
| `downloadResume()` | GET | `application.download-resume` | Owner or job owner | Download the resume file from storage |
| `destroy()` | DELETE | `application.destroy` | Admin or own application | Soft-delete the application and delete the resume file |
| `recalculateScore()` | POST | `application.recalculate-score` | Admin or job owner | Recalculate the ATS score on demand |
| `analyticsDashboard()` | GET | `backend.analytics.dashboard` | All roles | Render the ATS statistics dashboard |
| `jobATSAnalysis()` | GET | `backend.analytics.job-analysis` | Admin or job owner | Per-job keyword analysis and top candidates |
| `getATSStats()` | API | inline | Employer (own jobs) | JSON stats: total applications, average score, score distribution, top skills, status breakdown |

**Authorization summary**
- Job seekers can view, create, and delete only their own applications.
- Employers can view and update applications only for jobs they own.
- Admins have unrestricted access.

**ATS score distribution tiers**

| Tier | Score |
|------|-------|
| Excellent | ≥ 80 |
| Good | 60–79 |
| Fair | 40–59 |
| Poor | < 40 |

**Private helpers**

| Method | Purpose |
|--------|---------|
| `getTopSkillsFromApplications($applications)` | Aggregate extracted skills from ATS scores |
| `getKeywordAnalysisData(JobListing $jobListing)` | Keyword match statistics per job |
| `getTopCandidatesData(JobListing $jobListing)` | Top 10 candidates by ATS score |
| `getATSStatsData()` | Wrapper used by `getATSStats()` |

### 3.3 JobCategoryController (`app/Http/Controllers/JobCategoryController.php`)

CRUD for job categories with soft delete, restore, and force delete.

| Method | HTTP | Route name | Description |
|--------|------|------------|-------------|
| `index()` | GET | `backend.categories.index` | List all categories, including soft-deleted |
| `store()` | POST | `backend.categories.store` | Create a category with an auto-generated slug |
| `update()` | PUT | `backend.categories.update` | Update a category; regenerates the slug if the name changed |
| `destroy()` | DELETE | `backend.categories.destroy` | Soft-delete (blocked if job listings exist) |
| `toggleActive()` | PATCH | `backend.categories.toggle` | Toggle `is_active` |
| `restore()` | PATCH | `backend.categories.restore` | Restore a soft-deleted category |
| `forceDelete()` | DELETE | `backend.categories.force-delete` | Permanent deletion |
| `getActiveCategories()` | GET | `backend.categories.active` | JSON list of active categories for dropdowns |

### 3.4 LocationController (`app/Http/Controllers/LocationController.php`)

CRUD for job locations.

| Method | HTTP | Route name | Description |
|--------|------|------------|-------------|
| `index()` | GET | `backend.locations.index` | List all locations, including soft-deleted |
| `store()` | POST | `backend.locations.store` | Create a location |
| `update()` | PUT | `backend.locations.update` | Update a location |
| `destroy()` | DELETE | `backend.locations.destroy` | Soft-delete a location |
| `toggleActive()` | PATCH | `backend.locations.toggle` | Toggle `is_active` |
| `restore()` | PATCH | `backend.locations.restore` | Restore a soft-deleted location |
| `forceDelete()` | DELETE | `backend.locations.force-delete` | Permanent deletion |
| `getActiveLocations()` | GET | `backend.locations.active` | JSON list of active locations for dropdowns |

### 3.5 RegisteredUserController (`app/Http/Controllers/Auth/RegisteredUserController.php`)

Handles registration with role selection.

| Method | HTTP | Route name | Description |
|--------|------|------------|-------------|
| `create()` | GET | `register` | Render the registration form |
| `store()` | POST | `register` | Validate and create the user with a role; sends the email verification |

**Validation:** `role` is required and must be `job_seeker` or `employer`. Admins cannot be created through public registration (seeded only).

---

## 4. Pages (Inertia React Components)

Authenticated pages use `AuthenticatedLayout`, which wraps content in a role-based sidebar. Pages receive their props from controller methods through Inertia.

### 4.1 Layout: AuthenticatedLayout (`resources/js/layouts/AuthenticatedLayout.jsx`)

**Props:** `children`

**Behavior**
- Reads `auth.user.role` from the Inertia page props.
- Renders `Sidebar` with the role-specific menu.
- Offsets the main content area with `ml-64` to clear the fixed sidebar.

**Sidebar menu by role**

| Role | Items |
|------|-------|
| `job_seeker` | Dashboard, Browse Jobs, My Applications (dropdown with status filters), Profile, Logout |
| `employer` | Dashboard, Job Location, Job Category, Jobs Management (dropdown), Applications (dropdown), Analytics, Company Profile, Logout |
| `admin` | Dashboard, Manage Jobs, All Applications, Users, Settings, Logout |

### 4.2 Backend Job Listing Pages

#### 4.2.1 Index (`Backend/JobListings/Index.jsx`)

**Props:** `jobListings`, `flash`

**Features**
- Data table with columns: Job Details, Location, Type & Level, Deadline, Status, Actions.
- Staggered fade-in row animation.
- Status toggle and delete, each behind a SweetAlert2 confirmation (delete is blocked for trashed rows).
- Row actions: View Applications, View Details, Edit, Delete.
- Color-coded badges for job type and experience level.
- Expired job detection (deadline earlier than today).
- Empty state with a "Create Job" call to action.

**Icons:** `FaPlus`, `FaEdit`, `FaTrash`, `FaSpinner`, `FaEye`, `FaBriefcase`, `FaMapMarkerAlt`, `FaCalendarAlt`, `FaToggleOn`, `FaToggleOff`, `FaUsers`

#### 4.2.2 Create (`Backend/JobListings/Create.jsx`)

**Props:** `categories`, `locations`, `errors` (as `serverErrors`)

**Five-step wizard**

| Step | Component | Fields |
|------|-----------|--------|
| 1 | `BasicInfoStep` | Title, Category, Location, Job Type, Experience Level, Salary |
| 2 | `JobDetailsStep` | Education Requirement, Application Deadline, Schedule Start Date |
| 3 | `DescriptionStep` | Job Description and Job Requirements (rich text) |
| 4 | `SkillsStep` | Skills, Responsibilities, Benefits, auto-generated keywords, publishing option, social sharing |
| 5 | `PreviewStep` | Read-only preview of all entered data |

**Features**
- Client-side validation before advancing to the next step.
- Form state is held centrally; step components receive props and callbacks.
- Salary mode toggle: single amount or range, with comma formatting.
- Categories and locations are exposed through `window.categories` and `window.locations` for the step components.
- Error summary at the top of the form.
- Submission is allowed only on step 5, via `router.post()` to `backend.listing.store`. Success redirects to `backend.listing.index`.

#### 4.2.3 Edit (`Backend/JobListings/Edit.jsx`)

**Props:** `jobListing`, `categories`, `locations`, `errors` (as `serverErrors`)

**Features**
- The same five-step wizard as Create, pre-populated with the existing job.
- Salary parsing handles a `$` prefix and the range format (`"X - Y"`).
- JSON array fields (benefits, skills, responsibilities, keywords) are parsed from the database values.
- Submits with `router.put()` to `backend.listing.update`. Success redirects to `backend.listing.index`.

#### 4.2.4 Show (`Backend/JobListings/Show.jsx`)

**Props:** `jobListing`, `auth`

**Features**
- Full job view: description, requirements, skills, responsibilities, benefits, keywords.
- Details sidebar: Category, Location, Salary, Job Type, Experience Level, Education, Deadline, Start Date.
- "Posted by" card: poster name, created and updated dates.
- LinkedIn/Facebook sharing indicators.
- Statistics card: views count and applications count (links to the applications page).
- Activate/deactivate button and Edit button.
- Delete modal with an application-count warning.
- JSON fields are safely parsed from strings when needed.

#### 4.2.5 Applications (`Backend/JobListings/Applications.jsx`)

Rendered by `JobListingController@applications` for the route `backend.listing.applications`. It receives `jobListing` and `applications`.

> **To verify:** The original notes were unsure whether this file exists or whether the list is rendered inside `Show.jsx`. Confirm the file is present before relying on this entry.

### 4.3 Job Listing Step Components (`Backend/JobListings/Steps/`)

#### 4.3.1 BasicInfoStep

**Props:** `formData`, `setFormData`, `errors`

| Field | Details |
|-------|---------|
| Job Title | Required, minimum 5 characters |
| Category | Dropdown from `window.categories` |
| Location | Dropdown from `window.locations` |
| Job Type | full-time, part-time, contract, freelance, internship, remote |
| Experience Level | entry, junior, mid, senior, lead, executive, intern |
| Salary | Single amount or range, with comma formatting |

**Helpers:** `formatNumber()` adds thousands separators; `getRawNumber()` strips formatting before submission.

#### 4.3.2 JobDetailsStep

**Props:** `formData`, `setFormData`, `errors`

| Field | Details |
|-------|---------|
| Education Requirement | high-school, associate, bachelor, master, phd, none |
| Application Deadline | Required; date picker, minimum today, maximum 1 year ahead |
| Schedule Start Date | Optional; date picker, minimum today |

#### 4.3.3 DescriptionStep

**Props:** `description`, `setDescription`, `requirements`, `setRequirements`, `errors`

| Field | Details |
|-------|---------|
| Job Description | Required, minimum 50 characters; `CustomEditor` |
| Job Requirements | Required, minimum 50 characters; `CustomEditor` |

#### 4.3.4 SkillsStep

**Props:** `skills`, `setSkills`, `responsibilities`, `setResponsibilities`, `benefits`, `setBenefits`, `keywords`, `setKeywords`, `formData`, `setFormData`, `errors`

| Field | Details |
|-------|---------|
| Required Skills | Required; tag input with add/remove |
| Key Responsibilities | Required; list input with add/remove |
| Benefits & Perks | Optional; list input with add/remove |
| Auto-generated Keywords | Extracted from skills and responsibilities |
| Publishing | `is_active` checkbox |
| Social sharing | `show_linkedin` and `show_facebook` checkboxes |

**Keyword extraction:** Strips HTML, lowercases, removes punctuation, filters stop words and short words, ranks by frequency, and keeps the top 20.

#### 4.3.5 PreviewStep

**Props:** `formData`, `description`, `requirements`, `skills`, `responsibilities`, `benefits`, `keywords`, `categories`, `locations`, `setCurrentStep`

**Features**
- Read-only preview of all form data.
- Resolves category and location names from their IDs.
- Formats job type, experience level, and salary (single or range).
- Renders description and requirements as HTML; skills, responsibilities, benefits, and keywords as badges or lists.
- Publishing status indicator.
- "Edit Details" button returns to step 4.
- The Enter key is suppressed to prevent accidental submission.

### 4.4 Job Categories Page

**Index** (`Backend/JobCategories/Index.jsx`). **Props:** `categories`. Lists all categories, including soft-deleted, showing name and status.

### 4.5 Locations Page

**Index** (`Backend/Locations/Index.jsx`). **Props:** `locations`. Lists all locations, including soft-deleted.

### 4.6 Application Pages

#### 4.6.1 Index (`applications/index.jsx`)

**Props:** `applications`, `filters`, `userRole`

**Features**
- Role-based data: job seekers see their own applications; employers see applications for their jobs.
- Status filter chips (All, Pending, Reviewed, Shortlisted, Rejected, Hired) with counts.
- ATS score filter (employers only): 80%+, 60%+, 40%+, 20%+.
- Table columns: Applicant, Job Title, Applied On, ATS Score (employer), Status, Actions.
- Row actions: View Details, Download Resume, Update Status (employer), Withdraw (job seeker).
- Bulk actions (employer/admin): select all, bulk status update, merge resumes.
- Laravel-style pagination.
- Status update modal and delete confirmation modal.
- Role-specific empty states.

**Score badge:** color-coded progress bar: green ≥ 80, blue ≥ 60, yellow ≥ 40, red < 40.

#### 4.6.2 Create (`applications/create.jsx`)

**Props:** `job`, `hasApplied`

**Features**
- "Application Already Submitted" state if the user has applied.
- "Application Closed" state if the deadline has passed or the job is inactive.
- Job summary sidebar: title, category, location, experience, salary, deadline, requirements, key skills.
- Form fields: Full Name, Email, Phone, Resume upload (PDF/DOC/DOCX, 5 MB max).
- Client-side file type and size validation.
- Application tips panel.
- Uses Inertia's `useForm()` and submits to `applications.store`.

#### 4.6.3 Show (`applications/show.jsx`)

**Props:** `application`, `userRole`

**Features**
- Applicant information: name, email, phone, applied date.
- Resume section with a download button.
- Employer notes (employer/admin): editable textarea with save and cancel.
- ATS score card: circular gauge, score label, and a score breakdown (keyword match, experience match, education match).
- Skills match: matched keywords (green tags) and missing keywords (red tags).
- Job details: title, location, deadline.
- Status update (employer/admin): buttons for pending, reviewed, shortlisted, rejected, hired.
- Application timeline: submitted, last updated, shortlisted, hired.
- Recalculate Score button (admin only).

**Score labels**

| Label | Score | Color / icon |
|-------|-------|--------------|
| Excellent | ≥ 80 | Green, `FiAward` |
| Good | ≥ 60 | Blue, `FiBarChart2` |
| Fair | ≥ 40 | Yellow |
| Needs Improvement | < 40 | Red |

### 4.7 Public Job Pages

#### 4.7.1 Index (`jobs/index.jsx`)

**Props:** `jobs`, `filters`, `userRole`

**Features**
- Search by title, description, or location.
- Filter panel: Job Type, Category, Experience Level, Location, Status (employer/admin). Filter keys: `search`, `job_type`, `category`, `experience_level`, `location`, `status`.
- Active filter chips with remove buttons and a counter badge.
- Table columns: Job Details, Location, Type, Deadline, Status, Actions.
- Role-based actions:

| Role | Actions |
|------|---------|
| All | View Details |
| Job seeker | Apply Now (if active and not expired), or an "Applied" state |
| Employer/Admin | View Applicants, Edit Job, Delete Job |

- Pagination and an empty state.

#### 4.7.2 Show (`jobs/show.jsx`)

**Props:** `job`, `hasApplied`, `canApply`, `userRole`, `applications`

**Features**
- Tab-based layout (the details tab is shown for all roles).
- Header with status badge, job type, and experience level.
- Role-based actions:
  - Employer/Admin: Edit, Activate/Deactivate, Delete.
  - Job seeker: Apply Now (if `canApply`), or an "Applied" state.
- Job meta: Location, Salary, Deadline, Posted On, Remote Policy.
- Description and requirements (rich text); key skills and benefits (badges).
- Contact information: email, phone, website.
- Delete confirmation modal and status toggle modal.

---

## 5. Components

### 5.1 Sidebar (`resources/js/components/Sidebar.jsx`)

**Behavior**
- Reads `auth.user` from the Inertia page props.
- Renders a role-specific menu:

| Menu | Items |
|------|-------|
| `jobSeekerItems` | Dashboard, Browse Jobs, My Applications (dropdown with status filters), Profile |
| `employerItems` | Dashboard, Job Location, Job Category, Jobs Management (dropdown), Applications (dropdown), Analytics, Company Profile |
| `adminItems` | Dashboard, Manage Jobs, All Applications, Users, Settings |

- Collapsible dropdowns with chevron indicators.
- Highlights the active item based on the current URL.
- User profile section with a logout button.
- Fixed left sidebar, `w-64` wide.

### 5.2 CustomEditor (`resources/js/components/CustomEditor.jsx`)

**Props:** `value`, `onChange`, `placeholder`

**Features**
- Formatting: bold, italic, underline, strikethrough.
- Lists (unordered, ordered) and alignment (left, center, right).
- Undo and redo.
- Preview mode toggle (renders HTML with `dangerouslySetInnerHTML`).
- Saves and restores the cursor position.
- Tracks the active state of the formatting buttons.
- Built on a `contentEditable` element.

---

## 6. Data Flow

### 6.1 Job Creation

```text
Employer opens backend.listing.create
        │
        ▼
JobListingController@create → fetches categories and locations
        │
        ▼
Create.jsx renders the 5-step wizard
        ├─ Step 1: BasicInfoStep      (title, category, location, type, level, salary)
        ├─ Step 2: JobDetailsStep     (education, deadline, start date)
        ├─ Step 3: DescriptionStep    (description, requirements)
        ├─ Step 4: SkillsStep         (skills, responsibilities, benefits, keywords, publishing)
        └─ Step 5: PreviewStep        (full preview)
        │
        ▼
Submit → router.post() to backend.listing.store
        │
        ▼
JobListingController@store
        ├─ Sets user_id = Auth::id()
        ├─ Generates the slug from the title
        ├─ Determines the initial is_active from the dates
        └─ Creates the JobListing record
        │
        ▼
Redirect to backend.listing.index with a success flash
```

### 6.2 Application Submission

```text
Job seeker opens a job → clicks Apply Now
        │
        ▼
ApplicationController@create → checks the role and any existing application
        │
        ▼
applications/create.jsx → form with resume upload
        │
        ▼
Submit → useForm().post() to applications.store
        │
        ▼
ApplicationController@store
        ├─ Validates that the job is accepting applications
        ├─ Validates that the user is a job seeker
        ├─ Checks for a duplicate application
        ├─ Uploads the resume to public/resumes
        ├─ Creates the Application (status: pending)
        └─ Dispatches the CalculateAtsScore job (queued)
        │
        ▼
CalculateAtsScore runs
        ├─ ATSService::calculateScore() extracts the resume text
        ├─ Matches keywords against the job listing
        ├─ Computes the score; extracts skills, experience, education
        └─ Updates the application's ats_score JSON
        │
        ▼
Redirect to application.show with a success flash
```

### 6.3 Application Review

```text
Employer opens the applications index
        │
        ▼
ApplicationController@index → filters to the employer's job listings
        │
        ▼
applications/index.jsx → table with ATS scores and status filters
        │
        ▼
Employer clicks Update Status → status modal opens
        │
        ▼
router.patch() to application.update-status
        │
        ▼
ApplicationController@updateStatus
        ├─ Authorizes (admin or job owner)
        └─ Updates status and employer_notes
        │
        ▼
Applications list reloads
```

---

## 7. Controller–Page Mapping

| Controller method | Route | Page component | Props |
|-------------------|-------|----------------|-------|
| `JobListingController@index` | `backend.listing.index` | `Backend/JobListings/Index.jsx` | `jobListings`, `flash` |
| `JobListingController@create` | `backend.listing.create` | `Backend/JobListings/Create.jsx` | `categories`, `locations`, `errors` |
| `JobListingController@edit` | `backend.listing.edit` | `Backend/JobListings/Edit.jsx` | `jobListing`, `categories`, `locations`, `errors` |
| `JobListingController@show` | `backend.listing.show` | `Backend/JobListings/Show.jsx` | `jobListing`, `auth` |
| `JobListingController@applications` | `backend.listing.applications` | `Backend/JobListings/Applications.jsx` | `jobListing`, `applications` |
| `ApplicationController@index` | `application.index` | `applications/index.jsx` | `applications`, `filters`, `userRole` |
| `ApplicationController@create` | `applications.create` | `applications/create.jsx` | `job`, `hasApplied` |
| `ApplicationController@show` | `application.show` | `applications/show.jsx` | `application`, `userRole` |
| `JobCategoryController@index` | `backend.categories.index` | `Backend/JobCategories/Index.jsx` | `categories` |
| `LocationController@index` | `backend.locations.index` | `Backend/Locations/Index.jsx` | `locations` |
| `RegisteredUserController@store` | `register` | none (redirects to the dashboard) | none |

---

## 8. Business Logic

### 8.1 Authorization Matrix

| Action | Admin | Employer | Job seeker |
|--------|:-----:|:--------:|:----------:|
| View all jobs | ✓ | ✓ | ✓ |
| Create job | ✓ | ✓ | ✗ |
| Edit job | ✓ (any) | ✓ (own) | ✗ |
| Delete job | ✓ | ✓ (own) | ✗ |
| Toggle job active | ✓ | ✓ (own) | ✗ |
| View applications for a job | ✓ | ✓ (own jobs) | ✗ |
| View all applications | ✓ | ✗ | ✗ |
| View own applications | ✓ | ✗ | ✓ |
| Apply for a job | ✗ | ✗ | ✓ |
| Update application status | ✓ | ✓ (own jobs) | ✗ |
| Download resume | ✓ | ✓ (own jobs) | ✓ (own) |
| Delete application | ✓ | ✗ | ✓ (own) |
| Merge resumes | ✓ | ✓ (own jobs) | ✗ |
| Recalculate ATS score | ✓ | ✓ (own jobs) | ✗ |
| View analytics | ✓ | ✓ | ✓ |
| Manage categories | ✓ | ✓ | ✓ |
| Manage locations | ✓ | ✓ | ✓ |

> The last two rows reflect the current controllers, which apply no role check. See [11](#11-known-issues--recommendations).

### 8.2 Validation Rules

Some limits below (for example minimum lengths) are documented from the step components and may be client-side only. Confirm they are also enforced in the controllers' server-side validation.

**Job listing**

| Field | Rule |
|-------|------|
| Title | Required |
| Category | Required |
| Location | Required |
| Job Type | Required |
| Experience Level | Required |
| Application Deadline | Required |
| Description | Required, minimum 50 characters |
| Requirements | Required, minimum 50 characters |
| Skills | At least 1 |
| Responsibilities | At least 1 |

**Application**

| Field | Rule |
|-------|------|
| Name | Required |
| Email | Required, valid email |
| Phone | Optional |
| Resume | Required; pdf/doc/docx; max 5 MB |

**Category**

| Field | Rule |
|-------|------|
| Name | Required, unique |
| Is Active | Boolean |

**Location**

| Field | Rule |
|-------|------|
| Name | Required |
| Address | Optional |
| Is Active | Boolean |

---

## 9. Flash Message Handling

Pages read `flash.success` and `flash.error` from the Inertia props and show SweetAlert2 notifications at the top of the page content.

| Type | Icon | Dismissal |
|------|------|-----------|
| Success | Green | Auto-dismisses after 2 seconds |
| Error | Red | Manual |

---

## 10. File Reference Index

**Controllers**
- `app/Http/Controllers/JobListingController.php`
- `app/Http/Controllers/ApplicationController.php`
- `app/Http/Controllers/JobCategoryController.php`
- `app/Http/Controllers/LocationController.php`
- `app/Http/Controllers/Auth/RegisteredUserController.php`

**Services and jobs**
- `app/Services/ATSService.php`
- `app/Jobs/CalculateAtsScore.php`

**Pages: backend**
- `resources/js/pages/Backend/JobListings/Index.jsx`
- `resources/js/pages/Backend/JobListings/Create.jsx`
- `resources/js/pages/Backend/JobListings/Edit.jsx`
- `resources/js/pages/Backend/JobListings/Show.jsx`
- `resources/js/pages/Backend/JobListings/Applications.jsx`
- `resources/js/pages/Backend/JobListings/Steps/BasicInfoStep.jsx`
- `resources/js/pages/Backend/JobListings/Steps/JobDetailsStep.jsx`
- `resources/js/pages/Backend/JobListings/Steps/DescriptionStep.jsx`
- `resources/js/pages/Backend/JobListings/Steps/SkillsStep.jsx`
- `resources/js/pages/Backend/JobListings/Steps/PreviewStep.jsx`
- `resources/js/pages/Backend/JobCategories/Index.jsx`
- `resources/js/pages/Backend/Locations/Index.jsx`

**Pages: applications**
- `resources/js/pages/applications/index.jsx`
- `resources/js/pages/applications/create.jsx`
- `resources/js/pages/applications/show.jsx`

**Pages: public jobs**
- `resources/js/pages/jobs/index.jsx`
- `resources/js/pages/jobs/show.jsx`

**Layouts and components**
- `resources/js/layouts/AuthenticatedLayout.jsx`
- `resources/js/components/Sidebar.jsx`
- `resources/js/components/CustomEditor.jsx`

**Routes**
- `routes/web.php`
- `routes/auth.php`

---

## 11. Known Issues & Recommendations

These come from inconsistencies within this document. Each should be checked against the code.

### Security

1. **No role checks on categories and locations.** Any authenticated user, including job seekers, can create, edit, delete, and force-delete them. Restrict these routes to admin (and employer, if intended) using a Policy or role middleware.
2. **No role or ownership checks documented for the JobListingController.** The controller table shows no restriction, but the matrix says employers may edit only their own listings. If ownership is enforced only in the UI, any employer can edit or delete another employer's listing with a direct request. Add a server-side check in `edit`, `update`, `destroy`, and `toggleActive`.
3. **`mergeResumes` is a GET route** that takes selected IDs. Use POST so selections aren't exposed in URLs, logs, or browser history.
4. **Resumes are stored in `public/resumes`.** That makes them reachable by URL without any authorization check. Store them on a private disk and serve them only through `downloadResume`.
5. **`CustomEditor` and the preview render HTML with `dangerouslySetInnerHTML`.** Sanitize job descriptions and requirements on the server (or with DOMPurify) before saving or rendering.

### Documentation mismatches

6. **File extensions.** Pages are documented as `.jsx`, but `app.tsx` resolves only `./pages/${name}.tsx`. Confirm the real extensions and update this document (or the resolver) to match.
7. **Route names are inconsistent.** Application routes sit under `backend/` in the route tree but are named `application.*` and `applications.*` rather than `backend.application.*`. Verify the real names with `php artisan route:list`.
8. **Routes not shown in the tree.** `backend.analytics.dashboard`, `backend.analytics.job-analysis`, and the `getATSStats()` endpoint are documented in the controller but absent from the route tree.
9. **UI fields with no matching schema column.** Job `Show` pages display Remote Policy and contact email/phone/website, and the backend Show page displays a views count, but `job_listings` has no such columns.
10. **Score breakdown.** The application Show page displays keyword, experience, and education match bars, but `ATSService` computes the score from keyword matching only. Experience and education are extracted but not scored.
11. **Timeline milestones.** The application timeline shows "shortlisted" and "hired" dates, but `applications` has only `created_at` and `updated_at`. Those dates would be inaccurate after any later edit.
12. **Recalculate Score permission.** The button is admin-only in `applications/show.jsx`, but the controller allows admin or the job's employer.

### Code improvements

13. **Replace `window.categories` and `window.locations`.** Pass them to the step components as props or through context, or fetch them from the existing `/active` endpoints.
14. **Share validation.** Duplicate rules (minimum lengths, date limits) live in both the step components and the controllers. Keep the server as the source of truth and surface its errors in the wizard.
15. **Use Form Request classes** for job listing and application validation to keep controllers small.
