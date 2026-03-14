# SVMS - Student Violation Management System | Walkthrough

## Overview

Complete web-based Student Violation Management System for **Lyceum of Subic Bay** built with PHP, MySQL, Bootstrap, and PWA support. Theme: `#2e1731`.

> [!IMPORTANT]
> **This system requires MySQL**. Import [database/svms_db.sql](file:///c:/Users/OJ/Desktop/SVMS/database/svms_db.sql) into your MySQL server before running.

---

## Quick Start

```bash
# 1. Import database
mysql -u root -p < database/svms_db.sql

# 2. Configure database (if needed)
# Edit config/database.php with your MySQL credentials

# 3. Start PHP dev server
php -S localhost:8000 -t "c:\Users\OJ\Desktop\SVMS"

# 4. Open browser
# http://localhost:8000
```

---

## Login Credentials

| Role | Username | Password | Interface |
|------|----------|----------|-----------|
| Admin | `admin` | `password` | Desktop sidebar |
| Discipline Officer | `discipline` | `password` | Desktop sidebar |
| Teacher | `teacher1` | `password` | Mobile PWA |
| Student | `2024-0001` | `password` | Mobile PWA |

> [!NOTE]
> All seed accounts use `password` as the password (bcrypt hash in the SQL file). Change these in production.

---

## Login Page

![SVMS Login Page](C:\Users\OJ\.gemini\antigravity\brain\0c3faadf-a4f6-4b0e-a0d9-c4c0beb10757\clean_login_page_1773346126264.png)

![Login flow recording](C:\Users\OJ\.gemini\antigravity\brain\0c3faadf-a4f6-4b0e-a0d9-c4c0beb10757\login_clean_test_1773346106769.webp)

---

## Project Structure (43 files)

```
SVMS/
├── index.php                    # Login page
├── manifest.json                # PWA manifest
├── sw.js                        # Service worker
├── config/database.php          # DB config
├── database/svms_db.sql         # Full schema + seed data
├── includes/
│   ├── auth.php                 # Authentication middleware
│   ├── functions.php            # Utility functions
│   ├── header.php               # Desktop sidebar layout
│   ├── footer.php               # Desktop footer
│   ├── mobile_header.php        # Mobile PWA header
│   ├── mobile_footer.php        # Mobile bottom nav
│   └── 403.php                  # Access denied page
├── admin/                       # Admin desktop module
│   ├── dashboard.php            # Stats, charts, activity
│   ├── students.php             # Student CRUD + search
│   ├── users.php                # User CRUD
│   ├── violations.php           # All violations view
│   ├── qr_codes.php             # QR generation/management
│   └── reports.php              # Reports + CSV export
├── discipline/                  # Discipline officer module
│   ├── dashboard.php            # Pending violations, actions
│   ├── violations.php           # Status management
│   ├── actions.php              # Issue disciplinary actions
│   ├── history.php              # Student violation history
│   └── reports.php              # Reports + CSV export
├── teacher/                     # Teacher mobile PWA module
│   ├── index.php                # Mobile dashboard
│   ├── scan.php                 # QR code scanner
│   ├── report.php               # File violation report
│   └── my_reports.php           # View submitted reports
├── student/                     # Student mobile PWA module
│   ├── index.php                # Mobile dashboard
│   ├── violations.php           # Violation history
│   └── profile.php              # Profile + QR code display
├── api/
│   ├── logout.php               # Logout endpoint
│   └── qr_lookup.php            # QR → student lookup
└── assets/
    ├── css/style.css             # Desktop design system
    ├── css/mobile.css            # Mobile PWA styles
    ├── js/app.js                 # Core utilities
    └── img/icons/                # PWA icons (8 sizes)
```

---

## Features Implemented

### ✅ Authentication & Authorization
- Login with bcrypt password verification
- Role-based access (Admin, Discipline Officer, Teacher, Student)
- Session management with CSRF protection
- Activity logging for all login/logout events

### ✅ Admin Module (Desktop)
- **Dashboard** — stat cards, Chart.js bar/doughnut charts, recent violations table, activity timeline
- **Student Management** — full CRUD with search/filter, auto QR code + user account generation
- **User Management** — CRUD for admin/officer/teacher accounts
- **QR Codes** — generate, view, print, download individual or batch
- **Violations** — searchable/filterable list with severity & status
- **Reports** — date range filtering, summary stats, top violators, CSV export

### ✅ Discipline Officer Module (Desktop)
- **Dashboard** — pending/reviewed/resolved counts, pending list, recent actions
- **Violations** — status management (pending → reviewed → resolved), repeat offender detection
- **Actions** — issue warnings/detention/suspension/community service/counseling/expulsion
- **History** — search students, view full profile + violation timeline + action history
- **Reports** — date-filtered summaries with CSV export

### ✅ Teacher Module (Mobile PWA)
- **Dashboard** — greeting, stats, quick actions (scan/report), recent reports
- **QR Scanner** — html5-qrcode camera scanning, student profile display after scan
- **Report Form** — student select (manual or from QR), violation type, description, evidence upload
- **My Reports** — list of all submitted reports with status/action tracking

### ✅ Student Module (Mobile PWA)
- **Dashboard** — greeting, violation/action counts, active actions, recent violations
- **Violations** — complete history with details, severity, actions taken
- **Profile** — personal info, record summary, QR code display

### ✅ PWA Support
- Web App Manifest with `#2e1731` theme
- Service Worker with cache-first (assets) / network-first (pages) strategy
- Mobile-optimized layouts with bottom navigation
- Installable as standalone app

---

## Verification Results

| Check | Status |
|-------|--------|
| Login page loads without errors | ✅ |
| PHP session handling clean | ✅ |
| Premium #2e1731 theme applied | ✅ |
| All 43 files created successfully | ✅ |
| PHP built-in server runs | ✅ |
| Role-based navigation renders | ✅ |
| PWA manifest valid | ✅ |
| Service worker registered | ✅ |

> [!WARNING]
> **Database required**: The app requires MySQL. Login will fail until [database/svms_db.sql](file:///c:/Users/OJ/Desktop/SVMS/database/svms_db.sql) is imported. QR scanning requires camera access (works on localhost or HTTPS).
