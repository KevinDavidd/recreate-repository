# CECT Research Repository

**Wesleyan University-Philippines — College of Engineering, Computer and Technology**

A production-ready, defense-ready web application serving as a centralized digital repository for all research and capstone projects from the IT, Computer Engineering, and Computer Science programs (2020 to present).

## System Description

The CECT Research Repository is a department-exclusive secure platform. Only registered and admin-approved CECT students may access the repository. Unauthenticated visitors see only the login page.

Three user roles:

| Role | Access |
|---|---|
| Admin (Faculty / Dept. Head) | Full system management |
| Active Student (Approved) | View, search, filter research (view-only, no downloads) |
| Pending Student | Waiting page only — no repository access |

## Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite enabled (XAMPP recommended)
- XAMPP on Windows / LAMP on Linux
- PHP fileinfo extension enabled (for MIME type checking)

## Installation Steps

### 1. Download & Place Files

Copy the `cect-research-repository/` folder into:

- **Windows/XAMPP:** `C:\xampp\htdocs\cect-research-repository\`
- **Linux/LAMP:** `/var/www/html/cect-research-repository/`

### 2. Configure Database Credentials

Open `config/database.php` and update:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cect_repository');
define('DB_USER', 'root');
define('DB_PASS', '');           // your MySQL root password
```

### 3. Import the Schema

In phpMyAdmin:

1. Go to **Import**
2. Choose `database/cect_repository.sql`
3. Click **Go**

### 4. Run the Setup Installer

Open your browser and visit:

```
http://localhost/cect-research-repository/setup.php
```

This creates the database, tables, admin account, sample students, and sample research papers with properly bcrypt-hashed passwords.

> ⚠️ **Delete or rename `setup.php` immediately after running it.**

### 5. Set Folder Permissions (Linux/Mac only)

```
chmod 755 uploads/researches/
```

### 6. Access the System

```
http://localhost/cect-research-repository/
```

## Default Credentials

### Admin Account

| Field | Value |
|---|---|
| Username | admin |
| Password | Admin@2025 |
| Login URL | admin/login.php |

### Sample Student Accounts (status: active)

| Full Name | Email | Password | Login URL |
|---|---|---|---|
| Juan dela Cruz | delacruz.juan@wesleyan.edu.ph | Student@2025 | index.php |
| Maria Santos | santos.maria@wesleyan.edu.ph | Student@2025 | index.php |

### Sample Pending Student

| Full Name | Email | Status |
|---|---|---|
| Pedro Reyes | reyes.pedro@wesleyan.edu.ph | Pending (needs admin approval) |

## Student Approval Flow

```
Student visits site
       ↓
Clicks "Register here"
       ↓
Fills out registration form
(Student ID, name, course, email, password)
       ↓
Account created with status = PENDING
Student redirected to /pending.php
       ↓
Admin logs in → Dashboard → Pending Approvals
       ↓
Admin clicks [Approve] or [Reject]
       ↓
Approve → status = 'active' → student can now log in
Reject  → registration deleted
       ↓
Student logs in → redirected to /home.php (Repository)
```

Session rules:

- Students: 60-minute inactivity timeout
- Admin: 30-minute inactivity timeout
- Pending login → redirected to `pending.php`
- Suspended login → redirected to `suspended.php`
- Rejected login → error message shown

## Separate Login Pages

The system uses **one** `admins` table, **one** `students` table, and a single
shared `authenticate_user()` helper (in `includes/functions.php`) — but two
distinct login entry points, each restricted to one role:

| Page | Audience | Wrong-role message |
|---|---|---|
| `index.php` | Students only | "Please use the Administrator Login page." |
| `admin/login.php` | Admins only | "This portal is for administrators only." |

Credentials are always verified in full (`password_verify`) **before** either
page discloses anything about the account's role, so a wrong password never
reveals which table an email/username belongs to. Each page cross-links to
the other (`Administrator Login` link on the student page, `Student Login`
link on the admin page).

## Full Folder Structure

```
cect-research-repository/
├── index.php                    ← Student login page (entry point)
├── register.php                 ← Student self-registration
├── pending.php                  ← Awaiting approval notice
├── suspended.php                ← Suspended account notice
├── logout.php                   ← Session destroy + redirect
├── home.php                     ← Student repository (AJAX search)
├── research.php                 ← Research detail page (embedded PDF preview)
├── view_research.php            ← Secure inline PDF viewer (view-only, no download)
├── profile.php                  ← Student profile & password change
├── setup.php                    ← One-time installer (DELETE AFTER USE)
│
├── config/
│   └── database.php             ← PDO connection singleton
│
├── includes/
│   ├── functions.php            ← All shared helper functions
│   ├── auth_check.php           ← Student session guard
│   ├── admin_auth_check.php     ← Admin session guard
│   ├── header.php               ← Student top navbar + HTML head
│   ├── footer.php                ← Student footer + Bootstrap JS
│   ├── admin_header.php          ← Admin sidebar layout (open)
│   ├── admin_footer.php          ← Admin layout (close) + Bootstrap JS
│   └── _results_fragment.php     ← Shared search-results partial (AJAX + full page)
│
├── admin/
│   ├── login.php                 ← Administrator login page (admin-only)
│   ├── dashboard.php             ← Stats, charts, quick lists
│   ├── researches.php            ← Research table (search/filter)
│   ├── add_research.php          ← Add new research + PDF upload
│   ├── edit_research.php         ← Edit existing research
│   ├── delete_research.php       ← Delete research + PDF
│   ├── students.php              ← All students (filter/search)
│   ├── pending_students.php      ← Pending approvals + bulk approve
│   ├── add_student.php           ← Manually add student (auto-approved)
│   ├── approve_student.php       ← Single approval handler
│   ├── reject_student.php        ← Rejection with confirmation
│   ├── suspend_student.php       ← Suspend / reactivate handler
│   └── logs.php                  ← Activity log viewer + filters
│
├── assets/
│   ├── css/
│   │   └── style.css             ← Custom styles (Green/White/Gold theme)
│   ├── js/
│   │   ├── main.js               ← AJAX search & pagination
│   │   └── validation.js         ← Student ID + password validation
│   └── images/
│       └── logo.png              ← (optional) CECT logo
│
├── uploads/
│   └── researches/
│       └── .htaccess             ← Blocks direct access to PDFs
│
└── database/
    └── cect_repository.sql       ← Database schema (4 tables)
```

## Security Features

| Feature | Implementation |
|---|---|
| Password hashing | `password_hash()` with `PASSWORD_BCRYPT` |
| SQL injection | PDO prepared statements on ALL queries |
| XSS prevention | `htmlspecialchars()` via `e()` on ALL output |
| CSRF protection | `$_SESSION['csrf_token']` validated on every POST |
| Role separation | `auth_check.php` / `admin_auth_check.php` guards |
| PDF access control | All PDF access via `view_research.php` (inline only, no download), `.htaccess` blocks direct access |
| Session security | httponly, SameSite=Strict, use_strict_mode, regenerate on login |
| Session timeout | 30 min (admin) / 60 min (student) inactivity |
| File upload | Extension + MIME type check, `uniqid()` renamed, stored outside web root logic |
| Student ID | Enforced by regex `^\d{2}-\d{4}-\d{3}$` in both JS and PHP |

## Tech Stack

- **Backend:** PHP 8+
- **Database:** MySQL (PDO)
- **Frontend:** HTML5, Bootstrap 5.3, Bootstrap Icons 1.11
- **Charts:** Chart.js 4 (admin dashboard)
- **JavaScript:** Vanilla JS (no jQuery)
- **File storage:** Local `/uploads/researches/` folder
- **Auth:** PHP session-based

Built for CECT Department — Wesleyan University-Philippines
