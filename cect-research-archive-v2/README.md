# CECT Research Archive System

**Wesleyan University-Philippines — College of Engineering, Computer and Technology**

A production-ready, defense-ready web application serving as a centralized digital archive for all research and capstone projects from the IT, Computer Engineering, and Computer Science programs (2020 to present).

---

## System Description

The CECT Research Archive is a **department-exclusive** secure platform. Only registered and admin-approved CECT students may access the archive. Unauthenticated visitors see only the public landing page and the login/registration pages.

**Three user roles:**
| Role | Access |
|---|---|
| **Admin** (Faculty / Dept. Head) | Full system management |
| **Active Student** (Approved) | View, search, filter, download research |
| **Pending Student** | Waiting page only — no archive access |

---

## Requirements

- **PHP** 8.0 or higher
- **MySQL** 5.7+ or MariaDB 10.3+
- **Apache** with mod_rewrite enabled (XAMPP recommended)
- **XAMPP** on Windows / LAMP on Linux
- PHP `fileinfo` extension enabled (for MIME type checking)

---

## Installation Steps

### 1. Download & Place Files
Copy the `cect-research-archive/` folder into:
- **Windows/XAMPP:** `C:\xampp\htdocs\cect-research-archive\`
- **Linux/LAMP:** `/var/www/html/cect-research-archive/`

### 2. Configure Database Credentials
Open `config/database.php` and update:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cect_archive');
define('DB_USER', 'root');
define('DB_PASS', '');           // your MySQL root password
```

### 3. Import the Schema
In phpMyAdmin:
1. Go to **Import**
2. Choose `database/cect_archive.sql`
3. Click **Go**

### 4. Run the Setup Installer
Open your browser and visit:
```
http://localhost/cect-research-archive/setup.php
```
This creates the database, tables, admin account, sample students, and sample research papers with properly bcrypt-hashed passwords.

> ⚠️ **Delete or rename `setup.php` immediately after running it.**

### 5. Set Folder Permissions (Linux/Mac only)
```bash
chmod 755 uploads/researches/
```

### 6. Access the System
```
http://localhost/cect-research-archive/
```

---

## Default Credentials

### Admin Account
| Field | Value |
|---|---|
| Username | `admin` |
| Password | `Admin@2025` |
| Login URL | `login.php` |

### Sample Student Accounts (status: active)
| Full Name | Email | Password |
|---|---|---|
| Juan dela Cruz | `juan@wesleyan.edu.ph` | `Student@2025` |
| Maria Santos | `maria@wesleyan.edu.ph` | `Student@2025` |

### Sample Pending Student
| Full Name | Email | Status |
|---|---|---|
| Pedro Reyes | `pedro@wesleyan.edu.ph` | Pending (needs admin approval) |

---

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
Admin clicks [Approve ✅] or [Reject ❌]
       ↓
Approve → status = 'active' → student can now log in
Reject  → registration deleted
       ↓
Student logs in → redirected to /home.php (Archive)
```

**Session rules:**
- Students: 60-minute inactivity timeout
- Admin: 30-minute inactivity timeout
- Pending login → redirected to pending.php
- Suspended login → redirected to suspended.php
- Rejected login → error message shown

---

## Full Folder Structure

```
cect-research-archive/
├── index.php                    ← Public landing page (entry point)
├── login.php                    ← Login page (students + admin)
├── register.php                 ← Student self-registration
├── pending.php                  ← Awaiting approval notice
├── suspended.php                ← Suspended account notice
├── logout.php                   ← Session destroy + redirect
├── home.php                     ← Student archive (AJAX search)
├── research.php                 ← Research detail page
├── download.php                 ← Secure PDF delivery
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
│   ├── footer.php               ← Student footer + Bootstrap JS
│   ├── admin_header.php         ← Admin sidebar layout (open)
│   └── admin_footer.php         ← Admin layout (close) + Bootstrap JS
│
├── admin/
│   ├── dashboard.php            ← Stats, charts, quick lists
│   ├── researches.php           ← Research table (search/filter)
│   ├── add_research.php         ← Add new research + PDF upload
│   ├── edit_research.php        ← Edit existing research
│   ├── delete_research.php      ← Delete research + PDF
│   ├── students.php             ← All students (filter/search)
│   ├── pending_students.php     ← Pending approvals + bulk approve
│   ├── add_student.php          ← Manually add student (auto-approved)
│   ├── approve_student.php      ← Single approval handler
│   ├── reject_student.php       ← Rejection with confirmation
│   ├── suspend_student.php      ← Suspend / reactivate handler
│   └── logs.php                 ← Activity log viewer + filters
│
├── assets/
│   ├── css/
│   │   └── style.css            ← Custom styles (Green/White/Gold theme)
│   ├── js/
│   │   ├── main.js              ← AJAX search & pagination
│   │   └── validation.js        ← Student ID + password validation
│   └── images/
│       └── logo.png             ← (optional) CECT logo
│
├── uploads/
│   └── researches/
│       └── .htaccess            ← Blocks direct access to PDFs
│
└── database/
    └── cect_archive.sql         ← Database schema (4 tables)
```

---

## Security Features

| Feature | Implementation |
|---|---|
| Password hashing | `password_hash()` with `PASSWORD_BCRYPT` |
| SQL injection | PDO prepared statements on ALL queries |
| XSS prevention | `htmlspecialchars()` via `e()` on ALL output |
| CSRF protection | `$_SESSION['csrf_token']` validated on every POST |
| Role separation | `auth_check.php` / `admin_auth_check.php` guards |
| PDF access control | All downloads via `download.php`, `.htaccess` blocks direct access |
| Session security | `httponly`, `SameSite=Strict`, `use_strict_mode`, regenerate on login |
| Session timeout | 30 min (admin) / 60 min (student) inactivity |
| File upload | Extension + MIME type check, `uniqid()` renamed, stored outside web root logic |
| Student ID | Enforced by regex `^\d{2}-\d{4}-\d{3}$` in both JS and PHP |

---

## Tech Stack

- **Backend:** PHP 8+
- **Database:** MySQL (PDO)
- **Frontend:** HTML5, Bootstrap 5.3, Bootstrap Icons 1.11
- **Charts:** Chart.js 4 (admin dashboard)
- **JavaScript:** Vanilla JS (no jQuery)
- **File storage:** Local `/uploads/researches/` folder
- **Auth:** PHP session-based

---

*Built for CECT Department — Wesleyan University-Philippines*
