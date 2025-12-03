# 📌 Affiliate Referral Management System

A web-based platform that manages affiliate referrals, quotation tracking, commission calculations, and tax deductions for partnered affiliates.

This system allows individuals (affiliates) to generate leads for a business using **unique WhatsApp referral links**. Admin users track referred quotations, mark deals as Won/Lost, and commission earnings are automatically calculated.

📌 **Tax withholding compliance** is built-in for affiliates without valid tax clearance documentation.

---

## 📚 Table of Contents

- [Overview](#overview)
- [Core Features](#core-features)
- [Technology Stack](#technology-stack)
- [Project Phases](#project-phases)
- [Database Structure](#database-structure)
- [User Roles & Permissions](#user-roles--permissions)
- [Installation Guide](#installation-guide)
- [Folder Structure](#folder-structure)
- [Security & Compliance](#security--compliance)
- [Future Enhancements](#future-enhancements)
- [Contributing](#contributing)
- [License](#license)

---

## 📝 Overview

The **Affiliate Referral Management System** records and manages referrals brought in by affiliates. Each affiliate receives:

- A **unique Affiliate ID**
- A **custom WhatsApp referral link**
- A dashboard to monitor their earnings and statuses

Admins use the backend interface to:

- Register & manage affiliates
- Record quotations received via WhatsApp or in-person referrals
- Mark deals as Closed Won or Closed Lost
- Generate revenue and commission reports

📌 **The system automatically applies withholding tax** if the affiliate has no tax clearance, ensuring compliance with ZIMRA requirements.

---

## ⭐ Core Features

### Affiliate User Features

- Self-registration with validation
- Login using phone number and password
- Upload tax clearance certificates (PDF/JPG/PNG)
- Auto-generated Affiliate ID (e.g., `AFF001`)
- Auto-generated WhatsApp referral link
- Dashboard visibility for:
  - Referral link
  - Quotation statuses (Upcoming Phases)
  - Earnings & Tax deductions

---

### Admin Features

- Secure Admin login
- View all affiliates and their statuses
- CRUD (Create/Update/Delete) affiliates (Phase 2+)
- Create and update quotations linked to affiliates (Phase 3)
- Commission and payment status tracking (Phase 4)
- Export reports for compliance and payouts (Phase 4)

---

## 🏗 Technology Stack

| Layer    | Technology                                                     |
| -------- | -------------------------------------------------------------- |
| Backend  | Pure PHP (no framework)                                        |
| Frontend | HTML + CSS + JavaScript                                        |
| Database | MySQL / MariaDB                                                |
| Server   | Apache or NGINX                                                |
| Hosting  | XAMPP Local / Free cPanel Hosting                              |
| Security | Sessions, password hashing (bcrypt), prepared statements (PDO) |

Low hosting requirements → easy deployment on free PHP hosts.

---

## 📌 Project Phases (Roadmap)

| Phase       | Description                                     | Status            |
| ----------- | ----------------------------------------------- | ----------------- |
| **Phase 1** | Affiliate registration + login + referral links | 🚧 In development |
| **Phase 2** | Admin portal + affiliate portal enhancements    | ⏳ Planned        |
| **Phase 3** | Quotation tracking & reporting                  | ⏳ Planned        |
| **Phase 4** | Commission & revenue management                 | ⏳ Planned        |

---

## 🗄 Database Structure

`affiliates` table (core authentication + tax + banking info)

| Field         | Type                             | Notes                         |
| ------------- | -------------------------------- | ----------------------------- |
| affiliate_id  | VARCHAR                          | Unique ID like `AFF001`       |
| full_name     | VARCHAR                          | Required                      |
| phone_number  | VARCHAR                          | Used for login                |
| email         | VARCHAR                          | Optional                      |
| password      | VARCHAR                          | Hashed                        |
| tax_clearance | BOOLEAN                          | If no → apply withholding tax |
| referral_link | VARCHAR                          | WhatsApp share link           |
| role          | ENUM(admin, affiliate)           | User permissions              |
| status        | ENUM(active, suspended, deleted) | Control access                |

Additional tables in future phases:

- `quotations`
- `commissions`

---

## 🔐 User Roles & Permissions

| Role      | Permissions                                    |
| --------- | ---------------------------------------------- |
| Affiliate | Register, login, track referrals and payouts   |
| Admin     | Manage affiliates, quotations, and commissions |

Security includes:

✔ Role-based access  
✔ Session authentication  
✔ Secure password hashing  
✔ Input sanitization & PDO prepared statements

---

## ⚙ Installation Guide

### Local Setup — XAMPP / LAMPP

1️⃣ Clone or download the repository

```bash
git clone https://github.com/yourusername/affiliates-system.git
```

2️⃣ Place project in web root:

- Windows: `C:\xampp\htdocs\affiliates_project\`
- Linux: `/opt/lampp/htdocs/affiliates_project/`

3️⃣ Create database:

```sql
CREATE DATABASE affiliates_db;
```

4️⃣ Import `sql/schema.sql` into MySQL

5️⃣ Edit DB credentials in:  
`/includes/config.php`

6️⃣ Run in browser:

```
http://localhost/affiliates_project/public/
```

7️⃣ Log in as admin using credentials from schema file

---

## ✉️ SMTP Environment Variables

The application reads SMTP configuration from environment variables to avoid committing secrets.
Set the following variables on your server (example for bash):

```bash
export SMTP_HOST=smtp.gmail.com
export SMTP_PORT=587
export SMTP_SECURE=tls        # use 'ssl' or 'tls'
export SMTP_USERNAME=your@domain.com
export SMTP_PASSWORD=your_smtp_password
export MAIL_FROM=no-reply@yourdomain.com
export MAIL_FROM_NAME="Your Company"
```

On Apache you can set these with `SetEnv` in the virtual host config, or with a systemd service file for PHP-FPM. For deployments, prefer server-level environment provisioning or a secrets manager.

### Free Hosting Deployment Support

✔ cPanel compatible  
✔ MySQL support  
✔ FTP upload friendly  
✔ Set document root → `/public` folder

Recommended free hosts:

- InfinityFree
- GoogieHost
- AwardSpace

---

## 📁 Folder Structure

```
affiliates_project/
├─ admin/            # Admin-only pages
├─ includes/         # Core business logic and database interactions
├─ public/           # Public-facing web routes
├─ uploads/          # User-uploaded documents (secured later)
├─ sql/              # Database schema files
└─ README.md         # Project documentation
```

---

## 🔒 Security & Compliance

| Topic                 | Status                    |
| --------------------- | ------------------------- |
| Password protection   | ✔ bcrypt hashing          |
| SQL Injection defense | ✔ PDO prepared statements |
| Session security      | ✔ Required login checks   |
| File upload safety    | ✔ MIME validation         |
| ZIMRA tax compliance  | 🚧 Phase 4                |

Upcoming:

- CSRF tokens for forms
- Access and audit logs

---

## 🚀 Future Enhancements

| Feature                |  Priority  |
| ---------------------- | :--------: |
| Quotation tracking     | ⭐⭐⭐⭐⭐ |
| Commission calculation | ⭐⭐⭐⭐⭐ |
| Reporting & Exports    |  ⭐⭐⭐⭐  |
| Mobile UI improvements |  ⭐⭐⭐⭐  |
| Admin user management  |   ⭐⭐⭐   |
| API support            |    ⭐⭐    |

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit meaningful changes
4. Submit a Pull Request
5. Include SQL updates for DB schema changes

---

Affiliate Management System - Future Enhancements Development Roadmap
======================================================================

Prepared for: Future Development & Branching
Date: Automatically Generated

------------------------------------------------------------
1️⃣ Referral Tracking System
------------------------------------------------------------
- Create new table: referrals or customers
- Track:
  • Affiliate ID
  • Customer details (optional)
  • Referral timestamp
  • Status (Pending / Confirmed / Paid)
- Display on:
  • Admin: full referrals list, filtering, payouts
  • Affiliate: referral count, progress stats

------------------------------------------------------------
2️⃣ Activity Logging / Audit Trail
------------------------------------------------------------
Log key events:
- Admin edits: who, what changed, when
- Status changes: suspend/reactivate/delete
- Login attempts: admin & affiliate
Purpose:
✔ Accountability
✔ Fraud Mitigation
✔ Incident Debugging

------------------------------------------------------------
3️⃣ Security Hardening
------------------------------------------------------------
Critical upgrades:
- Add CSRF tokens on all forms
- Convert sensitive GET actions to POST
- Rate-limit login attempts
- Secure password reset handling
- Validate & sanitize all inputs

------------------------------------------------------------
4️⃣ Dashboard Enhancements
------------------------------------------------------------
Affiliate Dashboard:
- Referral analytics
- Click count tracking
- Auto-copy share button
- WhatsApp direct open

Admin Dashboard:
- Filtering: program, status, date range
- Export referral analytics
- KPI Overview (active affiliates, conversions, payouts)

------------------------------------------------------------
5️⃣ File Storage Improvements
------------------------------------------------------------
Current: /uploads/clearance_docs/

Recommendations:
- Validate file size (avoid server overload)
- Rename uploaded files to include affiliate ID
- Remove documents from public access:
  • Instead: store outside web-root + secure access script

------------------------------------------------------------
6️⃣ UI & UX Improvements
------------------------------------------------------------
- Toast notifications for success/error instead of alerts
- Mobile-first responsive layout tables
- Icons per program: energy icon for GetSolar, tech icon for TechVouch
- Badge color coding for programs

------------------------------------------------------------
Priority Table
------------------------------------------------------------
| Task Category            | Priority | Difficulty |
|------------------------ |---------:|-----------:|
| Referral Tracking       | ⭐⭐⭐⭐⭐    | Medium     |
| Security Improvements   | ⭐⭐⭐⭐     | Medium     |
| Activity Logging        | ⭐⭐⭐      | Low-Med    |
| Dashboard Enhancements  | ⭐⭐⭐      | Medium     |
| Storage Security        | ⭐⭐⭐      | Low        |
| UI & UX Polish          | ⭐⭐       | Optional   |

------------------------------------------------------------
Notes
------------------------------------------------------------
Git Branch naming suggestions:
- feature/referral-tracking
- feature/admin-logs
- feature/security-upgrade
- feature/dashboard-analytics
- fix/file-storage-permissions


Restricted use license for assigned development stakeholders only.  
Not permitted for external redistribution or resale without authorization.

---

Made with ❤️ for our affiliate partners and development team.
