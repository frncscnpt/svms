# SVMS - Student Violation Management System
**Streamline discipline tracking with QR-based student identification**

---

## Description

SVMS is a web-based platform designed for educational institutions to efficiently manage student discipline records, violation reports, and disciplinary actions. The system uses QR code technology for quick student identification and provides role-based access for administrators, discipline officers, teachers, and students.

Built for Lyceum of Subic Bay, this system digitizes the entire violation management workflow—from reporting incidents to tracking disciplinary actions and generating reports.

---

## Features

- **User Authentication** - Role-based login (Admin, Discipline Officer, Teacher, Student)
- **QR Code Scanner** - Quick student identification and lookup
- **Violation Reporting** - Teachers can report violations with evidence upload
- **Disciplinary Actions** - Track warnings, detentions, suspensions, and counseling
- **Uniform Pass System** - Issue temporary passes for uniform violations
- **Real-time Notifications** - Push notifications for violation updates
- **Academic Period Management** - Organize records by school year
- **Reports & Analytics** - Generate violation reports and statistics
- **Student Profiles** - View violation history and disciplinary records
- **Responsive Design** - Mobile-first design that works on all devices
- **PWA Support** - Install as a Progressive Web App

---

## Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript, Mobile-First Responsive Design
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+ / MariaDB
- **Server:** Apache (XAMPP)
- **Libraries:** 
  - SimpleXLSX (Excel import/export)
  - Web Push API (Push notifications)
  - QR Code generation
- **PWA:** Service Worker for offline support

---

## Installation / Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   ```

2. **Move project to htdocs**
   - Copy the project folder to your XAMPP `htdocs` directory

3. **Import the database**
   - Open phpMyAdmin (`http://localhost/phpmyadmin`)
   - Create a new database or import `database/svms_db.sql`

4. **Configure database connection**
   - Edit `config/database.php` with your database credentials

5. **Start Apache and MySQL**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

6. **Run the project**
   - Visit `http://localhost/svms/` in your browser

---

## Usage

**Login Credentials (Default):**

| Role                | Username   | Password     |
|---------------------|------------|--------------|
| Admin               | admin      | admin123     |
| Discipline Officer  | discipline | officer123   |
| Teacher             | teacher    | teacher123   |
| Student             | 2023010001 | student123   |

**Sample Workflow:**

1. **Teacher** logs in → Scans student QR code → Reports violation with evidence
2. **Discipline Officer** reviews violation → Issues disciplinary action
3. **Student** receives notification → Views violation details and action status
4. **Admin** generates reports → Analyzes violation trends by period

---

## Folder Structure

```
svms/
├── admin/              # Admin dashboard and management
├── discipline/         # Discipline officer module
├── teacher/            # Teacher violation reporting
├── student/            # Student portal
├── api/                # AJAX endpoints and API calls
├── assets/
│   ├── css/            # Stylesheets (responsive design)
│   ├── js/             # JavaScript files
│   ├── img/            # Images and icons
│   └── font/           # Custom fonts
├── config/             # Database configuration
├── database/           # SQL schema and seed data
├── includes/           # Shared components (header, footer, auth)
├── uploads/            # User uploads (avatars, evidence)
├── index.php           # Login page
├── manifest.json       # PWA manifest
└── sw.js               # Service worker
```

---

## Authors / Contributors

**John Francis B. Canapati**  
Full-stack Developer | Fresh Graduate

**Claire Jessica S. Cruz**  
UI/UX Designer

*This project was developed as part of academic requirements and portfolio demonstration.*

---

## License

This project is for educational purposes only.

---

## Future Improvements

- Add SMS notifications for parents/guardians
- Implement AI-based violation pattern detection
- Add data visualization dashboard with charts
- Integrate biometric authentication
- Mobile app version (Flutter/React Native)
- Multi-language support (English/Filipino)
- Parent portal for violation monitoring
- Automated email notifications for violations
- Integration with school management systems

---

## Contact

For questions or collaboration:
- Email: [jf.canapati@gmail.com]
- GitHub: [https://github.com/frncscnpt/]
- LinkedIn: [https://www.linkedin.com/in/jf-canapati/]
