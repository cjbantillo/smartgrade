**“Follow this README exactly.”**

---

# 📘 Automated Grading System

### Ampayon Senior High School

**Software Engineering 2 (CSC 107)**

---

## 🧠 AI INSTRUCTION (READ FIRST)

You are an AI software engineer assisting in building a **Medium-scale Automated Grading System** using **PHP, MySQL, HTML, CSS, JavaScript, and Bootstrap 5**, running locally on **XAMPP** (`htdocs`).

### 🔴 IMPORTANT RULES

- Use **plain PHP (no Laravel)**
- Use **MVC or clean MVC-like structure**
- Server-Side Rendering (PHP pages, not SPA)
- Secure, maintainable, readable code
- Assume this will be **graded by instructors**
- Do NOT over-engineer
- Always respect **role-based access control**

When generating code:

- Generate **only what is asked**
- Keep files small and modular
- Use comments to explain logic
- Assume XAMPP environment

---

## 🎯 PROJECT GOAL

Replace the current **manual Excel-based grading system** of Ampayon Senior High School with a **web-based Automated Grading System** that:

- Computes grades automatically
- Identifies honor students
- Generates certificates (single & bulk)
- Archives graduated student records
- Tracks grade changes using audit logs

**Note:** SF9 and SF10 forms are **NOT included** in this system as they must be requested through the official DepEd Learner Information System (LIS).

---

## 👥 USER ROLES (RBAC REQUIRED)

### 1️⃣ ICT Coordinator (Admin)

- Manage system settings (school year, grading periods, subjects)
- Manage users (teachers, students)
- View all student records (active & archived)
- View **audit & change logs**
- Full system access

---

### 2️⃣ Teacher

- Enter grades per subject and term
- Automatically compute final grades
- Generate and export certificates
- Bulk generation supported
- Can only access assigned students/classes
- All actions logged

---

### 3️⃣ Student

- Secure login
- View own academic records only
- View grades, honors status
- Download documents (if enabled)

---

## 🔐 AUTHENTICATION & SECURITY

- Session-based authentication (`$_SESSION`)
- Passwords must be **hashed**
- Role-based access control
- Block direct URL access without login
- Prepared statements (PDO or MySQLi)
- Input validation (grade ranges, required fields)
- Sensitive data handled securely

---

## 🗄️ DATABASE REQUIREMENTS

- Database: **MySQL**
- Normalized schema
- Core tables:

  - users
  - students
  - teachers
  - subjects
  - grades
  - school_year
  - audit_logs
  - archived_students

- Graduated students are **archived, not deleted**
- Archived records must be searchable

---

## 🧱 ARCHITECTURE (REQUIRED)

Use a **clean MVC-like structure**, example:

```
/htdocs/smartgrade/
│
├── app/
│   ├── config/
│   │   └── database.php
│   ├── controllers/
│   ├── models/
│   ├── views/
│
├── public/
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── index.php
│
├── routes/
│   └── web.php
│
├── database/
│   └── smartgrade.sql
│
├── auth/
│   ├── login.php
│   └── logout.php
│
└── README.md
```

---

## 🎨 UI / UX REQUIREMENTS

- Bootstrap 5
- Clean admin dashboard layout
- Sidebar navigation per role
- Responsive design
- Few-click workflows
- Fast bulk operations

---

## ⚙️ CORE FEATURES

- Grade input & auto-computation
- Bulk certificate generation
- Search & filtering
- Export to:

  - PDF
  - CSV / Excel

- Audit trail (who changed what & when)
- Archiving & retrieval

---

## 🚀 PERFORMANCE REQUIREMENTS

- Bulk certificate generation must not crash
- Target uptime: **99% during school hours**

---

## 🚫 SCOPE LIMITATIONS

- Senior High School only
- No attendance tracking
- No behavior/discipline reports
- **SF9 and SF10 forms are NOT included** - These official DepEd forms must be requested through the DepEd Learner Information System (LIS):

  1. Go to the DepEd LIS website
  2. Login using your LIS account (or register if needed)
  3. Request SF9 or SF10 and follow the instructions
  4. Fill out the required information and submit
  5. Download or print once processed

  _Note: Online request availability may vary depending on the school or division office. If not available online, request in person at the school or DepEd division office._

---

## 🧩 DEVELOPMENT FLOW (AI SHOULD FOLLOW THIS)

1. Design database schema
2. Create authentication system
3. Implement RBAC
4. Build admin features
5. Build teacher grading workflow
6. Build student portal
7. Implement exports (PDF/Excel)
8. Add audit logs
9. Add archival system
10. Optimize performance

---

## 🧪 EXPECTED OUTPUT FROM AI

- PHP source code
- SQL schema
- Clean folder structure
- Dummy data
- Clear setup instructions for XAMPP
- Maintainable, readable code

---

## 🧠 FINAL NOTE TO AI

This is a **Software Engineering course project**.
Prioritize:

- Correctness
- Security
- Maintainability
- Performance
- Clarity

Do not skip explanations in comments.

---

If you want, next I can:

- 🔥 Convert this into **step-by-step “prompt per phase”**
- 🧠 Generate the **database SQL**
- 💻 Start **vibe coding the auth + RBAC first**
- 🧩 Create **certificate templates**

Just say the word 😎
