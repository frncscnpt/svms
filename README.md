# SVMS — Student Violation Management System
**Lyceum of Subic Bay**

A web-based platform for managing student discipline records, violation reports, and QR-based student identification.

---

## Requirements

- PHP 7.4+
- MySQL 5.7+ / MariaDB
- Apache with `mod_rewrite` enabled (XAMPP recommended)

---

## Setup

1. Clone or copy the `svms/` folder into your web server root (e.g. `htdocs/`)
2. Import `database/svms_db.sql` into MySQL
3. Update `config/database.php` with your DB credentials
4. Visit `http://localhost/svms/`

---

## Roles

| Role       | Entry Point              |
|------------|--------------------------|
| Admin      | `admin/dashboard.php`    |
| Discipline | `discipline/dashboard.php` |
| Teacher    | `teacher/index.php`      |
| Student    | `student/index.php`      |

---

## Structure

```
svms/
├── admin/          # Admin module
├── discipline/     # Discipline officer module
├── teacher/        # Teacher module
├── student/        # Student module
├── api/            # AJAX endpoints
├── assets/         # CSS, JS, fonts, images (video in assets/img/bg/)
├── config/         # Database config
├── database/       # SQL dump
├── includes/       # Shared layout, auth, functions
├── uploads/        # User-uploaded files
├── index.php       # Login page
└── sw.js           # Service worker (PWA)
```

---

## Notes

- Mobile layout is detected server-side via user-agent in `includes/layout.php`
- Push notifications use the Web Push API (`api/save_subscription.php`)
- Avatar uploads are stored in `uploads/avatars/`
- Evidence images are stored in `uploads/evidence/`
