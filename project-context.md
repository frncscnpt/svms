    # SVMS Project Context
    **Student Violation Management System — Lyceum of Subic Bay**

    ---

    ## What It Is

    SVMS is a web-based Progressive Web App (PWA) built for Lyceum of Subic Bay to manage student disciplinary records. It covers the full lifecycle of a violation — from a teacher scanning a student's QR code and filing a report, to a discipline officer reviewing and issuing a disciplinary action, to the student viewing their own record.

    The system is built with **PHP + MySQL (PDO)**, styled with **Bootstrap 5.3 + custom CSS**, and deployed as a PWA with a service worker and web push notifications.

    ---

    ## Tech Stack

    | Layer | Technology |
    |-------|-----------|
    | Backend | PHP (procedural, no framework) |
    | Database | MySQL via PDO (`svms_db`) |
    | Frontend | Bootstrap 5.3, Bootstrap Icons 1.11, custom CSS |
    | Charts | Chart.js 4.4 |
    | QR Scanning | html5-qrcode 2.3.8 |
    | QR Generation | qrcodejs |
    | PWA | Web App Manifest + Service Worker (`sw.js`) |
    | Push Notifications | Web Push API (`push-manager.js`) |
    | Session | PHP sessions (`SVMS_SESSION`, 1-hour lifetime) |
    | File Uploads | Server-side PHP, stored in `/uploads/` |

    ---

    ## Database Schema

    ### Tables

    **`users`** — All system accounts
    - Roles: `admin`, `discipline_officer`, `teacher`, `student`
    - Links to `students` via `student_id` (nullable, only for student accounts)
    - Tracks `last_login`, `avatar`, `status`

    **`students`** — Student records
    - Fields: `student_number`, `first_name`, `last_name`, `middle_name`, `gender`, `date_of_birth`, `grade_level`, `section`, `contact`, `email`, `guardian_name`, `guardian_contact`, `address`, `photo`
    - Status: `active`, `inactive`, `graduated`, `transferred`

    **`violation_types`** — Catalog of violation categories
    - Severity: `minor`, `major`, `critical`
    - 14 default types seeded (Tardiness, Bullying, Cheating, Fighting, etc.)

    **`violations`** — Individual violation records
    - Links: `student_id`, `violation_type_id`, `reported_by` (user)
    - Fields: `description`, `evidence_path`, `location`, `date_occurred`
    - Status: `pending` → `reviewed` → `resolved` / `dismissed`

    **`disciplinary_actions`** — Actions issued per violation
    - Types: `warning`, `detention`, `suspension`, `expulsion`, `community_service`, `counseling`
    - Fields: `start_date`, `end_date`, `issued_by`, `description`, `remarks`
    - Status: `pending`, `active`, `completed`, `cancelled`

    **`qr_codes`** — One QR per student
    - `qr_data` format: `LSB-STU-{UUID}` (e.g. `LSB-STU-a1b2c3d4-...`)
    - Auto-generated on student creation

    **`notifications`** — In-app notifications
    - Types: `info`, `success`, `warning`, `danger`
    - Per-user, with `is_read` flag and optional `link`

    **`push_subscriptions`** — Web Push API subscriptions
    - Stores `endpoint`, `p256dh`, `auth` per user

    **`activity_log`** — Audit trail
    - Logs every login, logout, violation report, etc.
    - Stores `user_id`, `action`, `details`, `ip_address`

    ---

    ## User Roles & Access

    | Role | Interface | Access |
    |------|-----------|--------|
    | `admin` | Desktop sidebar | Full access: students, users, violations, reports |
    | `discipline_officer` | Desktop sidebar | Violations, disciplinary actions, history, reports |
    | `teacher` | Mobile PWA | QR scanner, file reports, view own reports |
    | `student` | Mobile PWA | View own violations and active disciplinary actions |

    Role-based redirects on login:
    - admin → `/admin/dashboard.php`
    - discipline_officer → `/discipline/dashboard.php`
    - teacher → `/teacher/index.php`
    - student → `/student/index.php`

    Unauthorized access returns HTTP 403 via `includes/403.php`.

    ---

    ## Feature Map

    ### Admin (`/admin/`)

    | Page | Feature |
    |------|---------|
    | `dashboard.php` | Stats (students, violations, pending, users), monthly trend chart (Chart.js bar), violations by type (doughnut), recent violations table, activity timeline, QR scanner for quick student lookup |
    | `students.php` | Student CRUD (modal form), search + grade filter, pagination (15/page), QR code view/print/download modal, bulk QR generation for missing codes |
    | `users.php` | User management (admin, discipline officer, teacher accounts) |
    | `violations.php` | All violations with search + severity + status filters, print report action |
    | `reports.php` | Reporting/analytics |
    | `report_violation.php` | Admin can file a direct violation report |
    | `violation_print.php` | Printable violation report |
    | `report_print.php` | Printable summary report |

    ### Discipline Officer (`/discipline/`)

    | Page | Feature |
    |------|---------|
    | `dashboard.php` | Stats (pending, reviewed, resolved, this month), QR scanner panel, pending violations table with review action, recent disciplinary actions timeline |
    | `violations.php` | Full violations list, review/update status, issue disciplinary actions |
    | `actions.php` | Manage disciplinary actions |
    | `history.php` | Student violation history (searchable by student number) |
    | `reports.php` | Reports view |
    | `report_violation.php` | File a direct violation report |

    ### Teacher (`/teacher/`) — Mobile PWA

    | Page | Feature |
    |------|---------|
    | `index.php` | Dashboard: greeting, 4-stat grid (total, pending, resolved, this month), quick action cards (Scan QR / File Report), recent reports list |
    | `scan.php` | Live QR scanner → student profile card (name, grade, contact, guardian, violation count) → link to file report |
    | `report.php` | Violation report form: student selector (or pre-filled from scan), violation type, description, location, date/time, evidence upload (image/video, max 5MB) |
    | `my_reports.php` | Teacher's own submitted reports |
    | `profile.php` | Teacher profile / avatar upload |

    ### Student (`/student/`) — Mobile PWA

    | Page | Feature |
    |------|---------|
    | `index.php` | Greeting with name + grade/section, stats (total violations, active actions), active disciplinary actions list, recent violations list |
    | `violations.php` | Full violations history |
    | `profile.php` | Student profile / avatar upload |

    ### Shared / API

    | Path | Purpose |
    |------|---------|
    | `index.php` | Login page (split-screen, handles all roles) |
    | `notifications.php` | Notification center (all roles) |
    | `api/logout.php` | Session destroy + redirect |
    | `api/qr_lookup.php` | QR scan lookup — returns student JSON (name, grade, contact, guardian, violation count, avatar HTML, initials) |
    | `api/mark_read.php` | Mark notification(s) as read |
    | `api/save_subscription.php` | Save Web Push subscription |
    | `api/upload_avatar.php` | Avatar upload handler |

    ---

    ## QR Code System

    - Each student gets one unique QR code on creation, format: `LSB-STU-{UUID}`
    - Stored in `qr_codes` table, one-to-one with `students`
    - Admin can bulk-generate missing QR codes via `?generate_all=1`
    - QR display uses `qrcodejs` (brand purple `#2e1731` on white)
    - QR scanning uses `html5-qrcode` (rear camera, `facingMode: environment`)
    - Lookup hits `/api/qr_lookup.php` which returns student data as JSON
    - Teachers scan → get student profile → tap "File Report" → pre-filled form

    ---

    ## Notification System

    Two layers:

    1. **In-app notifications** (`notifications` table)
    - Triggered on: new violation filed (notifies student + all admins/discipline officers), disciplinary action issued
    - Displayed in topbar bell dropdown (desktop) and Inbox tab (mobile)
    - Unread count badge on bell icon

    2. **Web Push notifications** (`push_subscriptions` table + `push-manager.js`)
    - Browser push via Web Push API
    - "Enable Alerts" button in notification dropdown
    - Subscriptions stored per user

    ---

    ## Authentication & Session

    - Session name: `SVMS_SESSION`
    - Session lifetime: 1 hour (`SESSION_LIFETIME = 3600`)
    - `requireLogin()` — redirects to login with `?error=session_expired` if no session
    - `requireRole($role)` — checks `$_SESSION['role']`, returns 403 if mismatch
    - Passwords hashed with `password_hash()` / verified with `password_verify()`
    - CSRF token generation/validation available (`generateCSRFToken()` / `validateCSRFToken()`)
    - Activity logged on every login, logout, and key action

    Default seed credentials:
    | Username | Password | Role |
    |----------|----------|------|
    | `admin` | `admin123` | Admin |
    | `discipline` | `officer123` | Discipline Officer |
    | `teacher1` | `teacher123` | Teacher |
    | `2024-0001` | `student123` | Student |

    ---

    ## File Uploads

    - Upload directory: `/uploads/`
    - `/uploads/avatars/` — user profile photos
    - `/uploads/evidence/` — violation evidence (images/video)
    - Max size: 5MB
    - Allowed for avatars: `jpeg`, `png`, `gif`, `webp`
    - Allowed for evidence: `jpeg`, `png`, `gif`, `webp`, `mp4`
    - Filenames: `uniqid() + '_' + time() + ext` (collision-safe)

    ---

    ## PWA Configuration

    - Manifest: `manifest.json` (served as base64 Data URI via `getManifestDataUri()` to work around InfinityFree hosting restrictions)
    - Display: `standalone`, orientation: `portrait-primary`
    - Theme color: `#2e1731`
    - Icons: 72, 96, 128, 144, 152, 192, 384, 512px (both `any` and `maskable`)
    - Service worker: `sw.js` registered at root scope `/`
    - Mobile meta: `apple-mobile-web-app-capable`, `user-scalable=no`, safe area insets

    ---

    ## Key Utility Functions (`includes/functions.php`)

    | Function | Purpose |
    |----------|---------|
    | `sanitize($input)` | `htmlspecialchars` + trim, works on arrays |
    | `formatDate($date)` | Format to `M d, Y` |
    | `formatDateTime($datetime)` | Format to `M d, Y h:i A` |
    | `timeAgo($datetime)` | Human-readable relative time |
    | `severityBadge($severity)` | Returns soft badge HTML for minor/major/critical |
    | `statusBadge($status)` | Returns soft badge HTML for violation/action statuses |
    | `actionBadge($type)` | Returns soft badge HTML for disciplinary action types |
    | `getAvatarHtml($path, $name, $class)` | Returns `<img>` if avatar exists, else initials `<div>` |
    | `getInitials($name)` | Extracts 2-letter initials from full name |
    | `uploadFile($file, $dir, $types)` | Validates + moves uploaded file, returns path |
    | `setFlash($type, $msg)` / `renderFlash()` | Session-based flash messages |
    | `generateUUID()` | UUID v4 via `random_bytes` |
    | `logActivity($userId, $action, $details)` | Writes to `activity_log` |
    | `paginate($query, $params, $page, $perPage)` | Generic pagination helper |
    | `getManifestDataUri()` | Converts manifest.json to base64 Data URI with absolute URLs |

    ---

    ## Project Structure

    ```
    svms/
    ├── index.php                  # Login page
    ├── notifications.php          # Notification center
    ├── manifest.json              # PWA manifest
    ├── sw.js                      # Service worker
    ├── .htaccess                  # Apache config
    ├── config/
    │   └── database.php           # DB connection + app constants
    ├── includes/
    │   ├── auth.php               # Auth middleware, session, CSRF
    │   ├── functions.php          # Shared utility functions
    │   ├── notification_functions.php  # Notification helpers
    │   ├── header.php             # Desktop layout header (sidebar)
    │   ├── footer.php             # Desktop layout footer
    │   ├── mobile_header.php      # Mobile PWA header
    │   ├── mobile_footer.php      # Mobile PWA footer + bottom nav
    │   └── 403.php                # Forbidden page
    ├── admin/                     # Admin pages (desktop)
    ├── discipline/                # Discipline officer pages (desktop)
    ├── teacher/                   # Teacher pages (mobile PWA)
    ├── student/                   # Student pages (mobile PWA)
    ├── api/                       # JSON API endpoints
    ├── assets/
    │   ├── css/
    │   │   ├── style.css          # Main stylesheet (desktop)
    │   │   └── mobile.css         # Mobile PWA stylesheet
    │   ├── js/
    │   │   ├── app.js             # Shared JS utilities
    │   │   └── push-manager.js    # Web Push subscription manager
    │   ├── font/
    │   │   └── Chillax-Variable.ttf
    │   └── img/
    │       ├── logo.png
    │       └── icons/             # PWA icons (72–512px)
    ├── uploads/
    │   ├── avatars/
    │   └── evidence/
    └── database/
        └── svms_db.sql            # Full schema + seed data
    ```

    ---

    ## Violation Lifecycle

    ```
    Teacher scans QR / selects student
            ↓
    Files report (violation type, description, location, evidence, date)
            ↓
    Violation created → status: "pending"
            ↓
    Notifications sent → student + all admins/discipline officers
            ↓
    Discipline officer reviews → status: "reviewed"
            ↓
    Discipline officer issues action (warning/detention/suspension/etc.)
            ↓
    Violation resolved → status: "resolved"
            ↓
    Student sees active action on their dashboard
    ```

    ---

    ## Constants (defined in `config/database.php`)

    | Constant | Value |
    |----------|-------|
    | `APP_NAME` | `SVMS` |
    | `APP_FULL_NAME` | `Student Violation Management System` |
    | `SCHOOL_NAME` | `Lyceum of Subic Bay` |
    | `APP_VERSION` | `1.0.0` |
    | `BASE_PATH` | Auto-detected from document root |
    | `QR_PREFIX` | `LSB-STU-` |
    | `SESSION_LIFETIME` | `3600` |
    | `MAX_FILE_SIZE` | `5242880` (5MB) |
