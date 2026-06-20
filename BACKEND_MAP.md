# AISU Backend — Quick Reference Map

> **Use this file whenever you need to find which backend file to edit for a specific page or feature.**

---

## 🗺️ Frontend → Backend Route Map

| Frontend Page | Backend Route File | API Prefix | What It Handles |
|---|---|---|---|
| `index.html` | `routes/admin.php` | `/api/admin` | Announcements & press items shown on homepage |
| `primary-membership.html` | `routes/members.php` | `/api/members` | Primary membership applications, approval, rejection, renewal |
| `student-membership.html` | `routes/students.php` | `/api/students` | Student membership registration & management |
| `competition.html` | `routes/competition.php` | `/api/competitions` | Competition CRUD, registration, submissions |
| `complaint.html` | `routes/complaint.php` | `/api/complaints` | Complaint filing, status tracking, resolution |
| `internship.html` | `routes/internship.php` | `/api/internship` | Internship applications & status |
| `innovations.html` | `routes/icell.php` | `/api/icell` | Innovation Cell proposals & reviews |
| `affiliation.html` | `routes/affiliation.php` | `/api/affiliation` | Organization affiliation requests |
| Footer newsletter / `press.html` | `routes/newsletter.php` | `/api/newsletter` | Newsletter subscription storage |
| `cert-verify.html` | `routes/certs.php` | `/api/certs` | Certificate verification by cert number |
| `login.html` | `routes/auth.php` | `/api/auth` | Login, register, JWT tokens, password change |
| `team.html` | `routes/members.php` | `/api/members/directory` | Public team directory (approved members) |
| `gallery.html` / `press.html` | `routes/admin.php` | `/api/admin` | Press releases, gallery items |
| `quiz-room.html` | `routes/quiz.php` | `/api/quiz` | Quiz room creation & management |
| `admin.html` | `routes/rbac.php` | `/api/rbac` | Role management, designations, promotions |
| `admin.html` | `routes/cert_templates.php` | `/api/cert-templates` | Certificate template upload & generation |

---

## 🧩 Core Backend Files

| File | Purpose | When to Edit |
|---|---|---|
| `index.php` | Main entry point / router | Adding new route groups |
| `config.php` | Database paths, JWT secret, SMTP, upload limits | Changing credentials or settings |
| `db.php` | JSON-file database (CRUD, ID generators) | Adding new collections or query helpers |
| `utils.php` | Shared helpers (auth, validation, uploads, responses) | Adding reusable utility functions |
| `jwt_handler.php` | JWT token creation & verification | Changing auth/session behavior |
| `email_service.php` | Email templates & SMTP sending | Changing email content or adding new emails |
| `rbac.php` | Role hierarchy, permissions, panel access | Changing who can access what |
| `scheduler.php` | Scheduled tasks (expiry checks) | Adding automated jobs |

---

## 📁 Data & Uploads

| Directory | Contents |
|---|---|
| `data/` | JSON database files (one `.json` per collection) |
| `uploads/govtid/` | Government ID scans |
| `uploads/payment/` | Payment proof files |
| `uploads/photo/` | Member photos |
| `uploads/sign/` | Signature images |
| `uploads/complaint/` | Complaint attachments |
| `uploads/cert_templates/` | Certificate template files |
| `uploads/certificates/` | Generated certificates |
| `uploads/resume/` | Internship resumes |
| `uploads/submissions/` | Competition submissions |
| `uploads/innovations/` | Innovation proposal files |
| `uploads/press/` | Press release images |
| `uploads/gallery/` | Gallery images |

---

## 🔧 How to Add a New Feature

### 1. Add a new API endpoint

1. Create a new file in `backend-php/routes/` (e.g., `routes/events.php`)
2. Follow the pattern from existing routes:
   ```php
   <?php
   $path   = $GLOBALS['SUB_PATH'];
   $method = $GLOBALS['METHOD'];

   // Your endpoints here
   if ($path === '/' && $method === 'GET') {
       $items = DB::findAll('events');
       ok($items);
   }

   err('Endpoint not found', 404);
   ```
3. Register it in `index.php` by adding to the `$routes` array:
   ```php
   '/api/events' => 'routes/events.php',
   ```

### 2. Add a new admin panel section

1. In `admin.html`, add a sidebar link:
   ```html
   <a href="#" class="sidebar-link" data-panel="events"><i class="bi bi-calendar-event"></i> Events</a>
   ```
2. Add a panel div:
   ```html
   <div class="panel" id="panel-events">...</div>
   ```
3. Add the `loadPanel` case in the JavaScript:
   ```js
   if(id==='events') return loadEvents();
   ```

### 3. Add a new frontend page

1. Create `events.html` (copy structure from any existing page)
2. Use `fetch('http://localhost:8000/api/events')` to call your new API
3. Update this map file!

---

## 🚀 Running the Backend

```bash
# Start the PHP backend server
cd backend-php
php -S localhost:8000 index.php

# Or use the batch file
start-server.bat

# Or start everything (backend + quiz server)
start-all-servers.bat
```
