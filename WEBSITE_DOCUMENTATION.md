# AISU Website - Complete Technical Documentation
**Version:** 2.1  
**Built with:** HTML5, CSS3, JavaScript, PHP, Node.js, Socket.IO, WebRTC  
**Status:** Production Ready with Critical Features Implemented  
**Last Updated:** April 6, 2026

---

## Table of Contents
1. [Project Overview](#overview)
2. [System Architecture](#architecture)
3. [Technology Stack](#tech-stack)
4. [Installation & Setup](#setup)
5. [Feature Specifications](#features)
6. [API Documentation](#api)
7. [Database Schema](#database)
8. [Deployment Guide](#deployment)
9. [Troubleshooting](#troubleshooting)
10. [Future Enhancements](#future)

---

## 1. Project Overview <a name="overview"></a>

### About AISU
All India Students Union (AISU) is a national-level registered organization dedicated to:
- Student empowerment and leadership development
- Talent identification through competitions
- Innovation promotion via Innovation Cell
- Internship opportunities
- Transparent grievance redressal system
- Certificate generation and verification

### Website Purpose
This digital platform serves as the central hub for:
- Organization information and team visibility
- Member registration (Primary & Student)
- Organization affiliation management
- Online competition management (Group Quiz, Online Quiz, Essays, Paintings, Posters, Videos)
- Complaint filing and resolution tracking
- Internship and Innovation proposal management
- Certificate generation, issuance, and verification
- Press releases, gallery, and news updates

### Key Statistics
- **17 Public Pages** for various organizational functions
- **13 API Route Modules** handling different business logic
- **10+ Data Collections** in database system
- **Multiple User Roles:** Admin, National, State, District, Mandal, Members, Students
- **File Upload Support** for certificates, documents, images, PDFs

---

## 2. System Architecture <a name="architecture"></a>

```
AISU Website Architecture
========================

┌─────────────────────────────────────────────────────────────┐
│                    Frontend Layer (Port 3000)              │
│  HTML5 | CSS3 | JavaScript | Bootstrap 5 | Responsive     │
├─────────────────────────────────────────────────────────────┤
│  Pages: 17 HTML Files (Index, About, Team, Forms, etc.)    │
│  Styling: Custom AISU theme + Bootstrap                    │
│  Scripts: 5 JS modules (api.js, forms.js, quiz-webrtc.js)  │
└─────────────────────────────────────────────────────────────┘
                            ↓↓↓
┌─────────────────────────────────────────────────────────────┐
│                    API Gateway Layer                         │
│         RESTful API (Port 8000) with CORS Support           │
│                    JWT Authentication                        │
└─────────────────────────────────────────────────────────────┘
                       ↙↙↙    ↓↓↓    ↘↘↘
        ┌──────────────┴────────────┴──────────────────┐
        ↓                          ↓                   ↓
   ┌─────────┐            ┌──────────────┐      ┌──────────┐
   │  PHP    │◄──────────►│   Database   │      │ WebSocket│
   │ Backend │  File I/O  │   (JSON)     │      │ Server   │
   │(Port    │            │   /data/     │      │(Port     │
   │ 8000)   │            │   folder     │      │ 3001)    │
   └─────────┘            └──────────────┘      └──────────┘
        │                        │                    │
        ├─ Routes Handler        ├─ Collections       ├─ Quiz Rooms
        ├─ JWT Validation        ├─ Persistence      ├─ WebRTC
        ├─ File Upload/Download  └─ Real-time Sync   ├─ Recording
        ├─ Email Service                             └─ Admin Controls
        └─ Data Validation
```

### Component Breakdown

**Frontend (Client-Side)**
- Static HTML pages with dynamic content
- Bootstrap responsive grid system
- API client (js/api.js) for backend communication
- Form validation and submission handlers
- WebRTC implementation for video/audio (js/quiz-webrtc.js)
- Socket.IO for real-time quiz rooms

**PHP Backend (API Server)**
- RESTful API following standard HTTP methods
- JWT token-based authentication
- File upload processing and storage
- Email service integration (SMTP)
- Database abstraction layer
- Request validation and error handling

**WebSocket Server (Real-Time Communication)**
- Socket.IO for browser-to-server connectivity
- Quiz room management
- Participant tracking
- Real-time messaging
- Admin controls broadcasting
- Recording session management

**Database Layer**
- JSON-based file storage (/backend-php/data/)
- Collections: users, members, students, competitions, certificates, etc.
- Persistent storage with real-time sync capability

---

## 3. Technology Stack <a name="tech-stack"></a>

| Component | Technology | Version |
|-----------|-----------|---------|
| **Frontend** | HTML5, CSS3, JavaScript | ES6+ |
| **Responsive Framework** | Bootstrap | 5.3.0 |
| **CSS Framework** | Custom AISU Styling | - |
| **Icons** | Font Awesome + Bootstrap Icons | Latest |
| **Backend API** | PHP | 8.0+ |
| **Real-Time Communication** | Socket.IO | 4.7.4 |
| **WebRTC** | Native Browser API | Latest |
| **Node.js Runtime** | Node.js | 22.22.2 |
| **Database** | JSON Files | Native |
| **Email Service** | SMTP (Gmail) | TLS 587 |
| **Authentication** | JWT (JSON Web Tokens) | HS256 |
| **HTTP Server** | PHP Built-in Server | - |

### External CDN Dependencies
- Bootstrap CSS/JS: cdn.jsdelivr.net
- Font Awesome: cdnjs.cloudflare.com
- Google Fonts: fonts.googleapis.com
- Socket.IO Client: cdn.socket.io
- jQuery: code.jquery.com (optional)

---

## 4. Installation & Setup <a name="setup"></a>

### System Requirements
- **OS:** Windows/Linux/Mac
- **PHP:** 8.0 or higher
- **Node.js:** 20+ (included in project)
- **Browser:** Modern browser with WebRTC support (Chrome, Firefox, Safari, Edge)
- **Disk Space:** 500MB minimum
- **RAM:** 2GB minimum

### Prerequisites Installation

#### Windows
1. **Install PHP 8.0+**
   ```bash
   # Option 1: Use embedded PHP (included in project)
   # Located at: backend-php/php/php.exe
   
   # Option 2: Download from php.net
   ```

2. **Verify Node.js**
   ```bash
   node --version  # Should show v22.22.2 or higher
   npm --version
   ```

### Project Setup Steps

1. **Clone/Extract Project**
   ```bash
   cd c:\Users\Admin\Downloads\AISU-Website
   ```

2. **Install Node.js Dependencies**
   ```bash
   cd quiz-server
   npm install
   cd ..
   ```

3. **Create Data Directories (if missing)**
   ```bash
   mkdir backend-php/data
   mkdir backend-php/uploads/gallery
   mkdir backend-php/uploads/press
   mkdir backend-php/uploads/certificates
   mkdir backend-php/uploads/cert_templates
   ```

4. **Start All Servers**

   **Option A: Windows Batch File**
   ```bash
   start-all-servers.bat
   ```
   This will start:
   - PHP Backend (port 8000)
   - Quiz Server (port 3001)
   - Frontend HTTP Server (port 3000)

   **Option B: Manual Start (PowerShell/Terminal)**
   ```bash
   # Terminal 1 - PHP Backend
   cd backend-php
   php -S localhost:8000 index.php
   
   # Terminal 2 - Quiz Server
   cd quiz-server
   npm start
   
   # Terminal 3 - Frontend Server   # OR
   npx http-server -p 3000
   ```

5. **Verify Installation**
   ```bash
   # Check PHP Backend
   curl http://localhost:8000/api/health
   
   # Check Frontend
   curl http://localhost:3000/
   
   # Access Website
   http://localhost:3000/index.html
   ```

---

## 5. Feature Specifications <a name="features"></a>

### 5.1 Home Page (`index.html`)
**Features:**
- Primary call-to-action buttons
- Recent announcements/news feed
- Upcoming competitions display
- Organization credibility indicators
- Navigation to all major sections

**API Calls:**
- GET `/api/competitions?status=open` - Fetch upcoming competitions
- GET `/api/announcements` - Fetch recent news

---

### 5.2 Membership System

#### Primary Membership (`primary-membership.html`)
**Process:**
1. Multi-step form with 6 sections
2. Collects personal, professional, and qualification details
3. Generates PDF document automatically
4. Emails PDF to National Officers
5. Issues unique ID upon approval: `AISU[StateCode][Year][SerialNumber]`

**API:**
- POST `/api/members/apply` - Submit application
- GET `/api/members/list` - List all applications

**Example ID:** `AISUIL20260042`

#### Student Membership (`student-membership.html`)
**Process:**
1. Quick 3-step registration
2. Issues unique ID: `AISUSM[StateCode][Year][SerialNumber]`
3. Free access to competitions for 1 year
4. Email notifications to National & State officials

**API:**
- POST `/api/students/register` - Register student
- GET `/api/students/list` - List students

**Example ID:** `AISUSMAP2026000123`

#### Organization Affiliation (`affiliation.html`)
**Process:**
1. Organization representative fills form
2. Generates unique registration: `FIYAOA[Year][SerialNumber]`
3. National team approval required
4. Confirmation email on approval

**API:**
- POST `/api/affiliation/apply` - Submit affiliation request
- GET `/api/affiliation/list` - List applications

**Example ID:** `FIYAOA20260015`

---

### 5.3 Competition Portal (`competition.html`)

#### Competition Types Supported

**1. Group Quiz (Quiz Rooms) - `quiz-room.html`**
- **Admin Features:**
  - Create multiple quiz rooms
  - Assign participants to specific rooms
  - Control who joins which room
  - Record entire session
  - Mute/unmute individual participants
  - Mute/unmute entire teams
  - Announce final scores to all rooms
  - Live video/audio broadcast to all rooms

- **Participant Features:**
  - Join assigned room with WebRTC video/audio
  - Toggle video and audio independently
  - Answer questions orally and via chat
  - See team scores in real-time
  - Receive certificates upon completion
  - View final leaderboard

- **Technical Implementation:**
  - WebRTC peer-to-peer video/audio
  - Socket.IO for signaling and control
  - Real-time score broadcasting
  - Session recording capability
  - Admin control dashboard

**API:**
- POST `/api/quiz` - Create quiz room
- GET `/api/quiz` - List quiz rooms
- Socket events: `join_quiz_room`, `moderator_start_quiz`, `submit_answer`, `admin_mute_participant`, `admin_start_recording`

**2. Online Quiz (Test Mode)**
- Time-limited questions
- Exam-style interface
- Copy/paste restrictions
- Screen manipulation prevention
- Auto-submission on time expiry
- Immediate scoring
- Disqualification for rule violations

**API:**
- POST `/api/competitions/:id/submit-entry` - Submit responses
- GET `/api/competitions/:id` - Get quiz details

**3. Document Competitions (Essays, Paintings, Posters, Videos)**
- Registration with up to 3 competitions maximum
- Document upload (PDF, images, videos)
- Automatic forwarding to selection committee
- Status tracking

**API:**
- POST `/api/competitions/:id/register` - Register for competition
- POST `/api/competitions/:id/submit-entry` - Upload entry
- GET `/api/competitions/my-registrations` - Get user's registrations

#### Competition Notifications
**Requirement:** Notify all previous participants and student members when new competition launches

**Implementation:**
- Background queue system (scheduler.php)
- Template-based emails to previous participants
- Email to all active student members
- Customizable notification per competition

---

### 5.4 Certificate System

#### Certificate Generation (`/api/certs`)

**Manual Generation:**
```
Admin selects participants → Chooses template → Generates certificates
```

**Automatic Generation:**
```
Competition ends → System auto-generates for all participants → Sends notifications
```

**Certificate Number Format:** `AISUCERT[ProgramCode][Year][SerialNumber]`
**Example:** `AISUCERTCOMP2026000145`

**Components:**
- AISU: Organization prefix
- CERT: Certificate identifier
- COMP: Program code (COMP, INT, INN, EVENT, etc.)
- 2026: Year of issue
- 000145: Serial number

**Certificate Types:**
- Participation Certificate
- Winner Certificate
- Runner-up Certificate
- Internship Completion Certificate
- Innovation Recognition Certificate

**Template System:**
- Supports Word, Google Slides, Canva templates
- Placeholder system: `{{CertificateNo}}`, `{{ParticipantName}}`, `{{ProgramName}}`, etc.
- Admin can upload new templates via /api/cert-templates

#### Certificate Verification (`cert-verify.html`)
**Verification Methods:**
1. By Certificate Number: `AISUCERTCOMP2026000145`
2. By Mobile Number: Returns all certificates for that user
3. By Email ID: Returns all certificates for that user

**Display Information:**
- Participant Name
- Program/Competition Name
- Certificate Type
- Date of Issue
- Certificate Status
- Download Link (if available)

**API:**
- GET `/api/certs/verify/:certNum` - Verify by certificate number
- GET `/api/certs?identifier=mobile_or_email` - Search by contact info
- GET `/api/certs/:id/download` - Download certificate PDF

---

### 5.5 Complaint Portal (`complaint.html`)

**Features:**
- Secure, confidential grievance filing
- Anonymous complaint submission
- **Unique Complaint ID** auto-generated
- Multi-level access control
  - National Team: Full visibility
  - State/District/Mandal/Institutional Teams: Access only via Complaint ID
- Action tracking with email notifications
- Resolution workflow with confirmation
- Automatic disposal upon resolution

**Status Workflow:**
```
Filed → Under Review → Action Taken → Resolved → Disposed
        (Email updates at each stage)
```

**API:**
- POST `/api/complaints` - File complaint
- GET `/api/complaints` - List (with access control)
- PATCH `/api/complaints/:id/status` - Update status
- POST `/api/complaints/:id/action` - Record action

---

### 5.6 Internship Program (`internship.html`)

**Features:**
- Application form submission
- Automatic forwarding to relevant departments
- Status tracking (Application Received → Selected → Ongoing → Completed)
- Integration with department dashboards
- Certificate generation upon completion

**Departments:**
- Legal Cell
- IT Cell
- Press Department
- Innovation Cell
- Others

**API:**
- POST `/api/internship/apply` - Submit application
- GET `/api/internship` - Get opportunities
- GET `/api/internship/my-applications` - Get application status
- PATCH `/api/internship/:id/status` - Update status

---

### 5.7 Innovation Cell (ICell) (`innovations.html`)

**Features:**
- Proposal submission with detailed information
- Problem statement and proposed solution
- Implementation plan and funding requirements
- Proposal tracking with status updates
- Investor connection for fund disbursement
- Automatic funds transfer to applicant account

**Proposal Statuses:**
- Submitted
- Under Review
- Modification Requested
- Approved
- Rejected

**Unique ID Format:** `AISUIC[Year][SerialNumber]`
**Example:** `AISUIC20260025`

**API:**
- POST `/api/icell/propose` - Submit proposal
- GET `/api/icell` - List proposals
- GET `/api/icell/:id` - Get proposal details
- PATCH `/api/icell/:id/status` - Update status
- PUT `/api/icell/:id/fund-transfer` - Fund disbursement

---

### 5.8 Press Releases & Gallery (`press.html` & `gallery.html`)

#### Press Releases
**Features:**
- Title, description, publication details
- PDF attachment upload
- Author/Source information
- Publication date
- News categorization

**Admin Interface:**
- Form to add new press release
- PDF upload field
- Category selection
- Status publishing
- Archive management

#### Gallery
**Features:**
- Event photography display
- Competition result photos with winner information
- Category filtering (Competitions, Events, Workshops, Activities)
- Winner profile photos integrated
- Image optimization and thumbnail generation

**Winner Details Required:**
- Participant name
- Institution name
- Competition title
- Category and level
- Rank (Winner/Runner-up)
- Photo

**Admin Interface:**
- Bulk image upload
- Drag-and-drop gallery management
- Winner profile editing
- Image categorization
- Thumbnail auto-generation

**API:**
- POST `/api/gallery/upload` - Upload gallery images
- GET `/api/gallery` - Fetch gallery items
- POST `/api/press` - Add press release
- GET `/api/press` - Fetch press releases
- DELETE `/api/gallery/:id` - Remove image

---

### 5.9 Admin Portal (`admin.html`)

**Sections:**

1. **Dashboard**
   - Statistics overview
   - Recent activites
   - Pending approvals
   - System health monitoring

2. **Member Management**
   - Primary member applications
   - Student member registrations
   - Affiliation requests
   - Approval workflow

3. **Complaint Management**
   - Complaint queue
   - Resolution tracking
   - Action history
   - Department-wise filtering

4. **Competition Management**
   - Create competitions
   - View registrations
   - Manage entries
   - Score tracking
   - Certificate generation

5. **Certificate Management**
   - Upload certificate templates
   - Generate certificates (manual/automatic)
   - Managing participant data (Excel/Google Sheets)
   - Certificate records
   - Reissuance functionality

6. **Quiz Room Management**
   - Create quiz rooms
   - Assign participants
   - Monitor live sessions
   - Manage recording
   - Review scores

7. **Content Management**
   - Add press releases (with PDF upload)
   - Manage gallery images
   - Manage announcements
   - Edit team member profiles

8. **System Management**
   - Admin user creation
   - Role assignment
   - Permission management
   - Activity logs

---

### 5.10 Login System (`login.html`)

**Features:**
- Unified login for all user types
- Multiple login credentials:
  - Mobile Number
  - Unique ID (if provided)
  - Email Address
  - Password

**User Types:**
- Admin (National/State/District/Mandal/Institutional)
- Primary Members
- Student Members
- General Users (competition/complaint/proposal applicants)

**Implementation:**
- JWT token-based authentication
- Refresh token support (30-day validity)
- Access token validity (12 hours)
- Role-based access control (RBAC)

**API:**
- POST `/api/auth/login` - Login
- POST `/api/auth/register` - Registration (if first-time user)
- POST `/api/auth/refresh` - Refresh token
- GET `/api/auth/me` - Get current user info
- POST `/api/auth/change-password` - Change password

---

## 6. API Documentation <a name="api"></a>

### Base URL
`http://localhost:8000/api`

### Authentication
All protected endpoints require JWT token in Authorization header:
```
Authorization: Bearer <token>
```

### Response Format
```json
{
  "success": true/false,
  "message": "Description",
  "data": { /* response data */ },
  "error": "Error message (if applicable)"
}
```

### Core Endpoints

#### Authentication
```
POST   /auth/login              - User login
POST   /auth/register           - User registration
POST   /auth/refresh            - Refresh JWT token
GET    /auth/me                 - Get current user
POST   /auth/change-password    - Change password
POST   /auth/logout             - Logout (clear tokens)
```

#### Primary Members
```
POST   /members/apply           - Apply for primary membership
GET    /members/list            - List applications
GET    /members/:id             - Get application details
PATCH  /members/:id/status      - Approve/Reject application
POST   /members/:id/assign-id   - Assign unique ID
GET    /members/verify/:memberId - Verify member by ID
```

#### Student Members
```
POST   /students/register       - Register as student
GET    /students/list           - List students
GET    /students/:id            - Get student details
PATCH  /students/:id/status     - Update status (Active/Inactive/Renewal)
POST   /students/:id/renew      - Renew membership
```

#### Competitions
```
POST   /competitions            - Create competition (Admin)
GET    /competitions            - List competitions
GET    /competitions/:id        - Get competition details
PATCH  /competitions/:id        - Update competition
POST   /competitions/:id/register - Register for competition
GET    /competitions/my-registrations - Get user's registrations
POST   /competitions/:id/submit-entry - Submit competition entry
POST   /competitions/:id/close  - Close competition (Admin)
GET    /competitions/:id/results - Get results (Admin)
POST   /competitions/:id/declare-winners - Declare winners (Admin)
```

#### Certificates
```
POST   /certs/issue             - Issue certificate (Admin)
GET    /certs                   - List certificates (public with filter by identifier)
GET    /certs/verify/:certNum   - Verify certificate by number
POST   /certs/bulk-generate     - Generate multiple certificates (Admin)
PATCH  /certs/:id               - Update certificate
DELETE /certs/:id               - Revoke certificate (Admin)
POST   /certs/:id/download      - Download certificate PDF
```

#### Complaints
```
POST   /complaints              - File complaint
GET    /complaints              - List complaints (access-controlled)
GET    /complaints/:id          - Get complaint details
PATCH  /complaints/:id/status   - Update status
POST   /complaints/:id/action   - Record action taken
PATCH  /complaints/:id/assign   - Assign to department
```

#### Internship
```
POST   /internship/apply        - Apply for internship
GET    /internship              - List opportunities
GET    /internship/my-applications - Get user applications
PATCH  /internship/:id/status   - Update application status
GET    /internship/my-status    - Get application status
```

#### Innovation Cell
```
POST   /icell/propose           - Submit proposal
GET    /icell                   - List proposals
GET    /icell/:id               - Get proposal details
PATCH  /icell/:id/status        - Update proposal status
POST   /icell/:id/fund-transfer - Transfer funds (Admin)
DELETE /icell/:id               - Delete proposal
```

#### Affiliation
```
POST   /affiliation/apply       - Apply for affiliation
GET    /affiliation             - List applications
GET    /affiliation/:id         - Get application details
PATCH  /affiliation/:id/status  - Approve/Reject (Admin)
```

#### Quiz
```
POST   /quiz                    - Create quiz room (Admin)
GET    /quiz                    - List quiz rooms
WebSocket Events Available in quiz-server.js
```

#### Gallery & Press
```
POST   /gallery/upload          - Upload gallery images
GET    /gallery                 - List gallery items
DELETE /gallery/:id             - Delete image (Admin)
POST   /press                   - Add press release
GET    /press                   - List press releases
PATCH  /press/:id               - Update press release
DELETE /press/:id               - Delete press release (Admin)
```

---

## 7. Database Schema <a name="database"></a>

### Data Storage Location
`/backend-php/data/` - JSON files containing collections

### Collections

#### users.json
```json
{
  "_id": "unique-user-id",
  "email": "user@example.com",
  "mobile": "+919876543210",
  "password_hash": "hashed-password",
  "role": "student|member|admin|national|state|district",
  "organization_id": "AISU|AISUSM|FIYAOA-format-id",
  "status": "active|inactive|suspended",
  "created_at": "2026-01-15T10:30:00Z",
  "updated_at": "2026-04-06T15:45:00Z",
  "profile": {
    "name": "Full Name",
    "institution": "ABC University",
    "state": "Andhra Pradesh",
    "country": "India"
  }
}
```

#### primary_members.json
```json
{
  "_id": "app-id",
  "user_id": "user-id-reference",
  "member_id": "AISUAP20260014",
  "name": "Member Name",
  "email": "member@example.com",
  "status": "submitted|under_review|approved|rejected",
  "role_interest": "National President|State Vice President|...",
  "documents": {
    "govt_id": "/uploads/govtid/file-name",
    "photo": "/uploads/photo/file-name",
    "signature": "/uploads/sign/file-name"
  },
  "approved_by": "national_president_user_id",
  "approved_at": "2026-02-01T10:00:00Z",
  "created_at": "2026-01-15T10:30:00Z"
}
```

#### student_members.json
```json
{
  "_id": "student-id",
  "student_id": "AISUSMAP2026000123",
  "user_id": "user-id-reference",
  "name": "Student Name",
  "email": "student@example.com",
  "institution": "College Name",
  "state": "State Name",
  "registration_date": "2026-01-15T10:30:00Z",
  "membership_valid_till": "2027-01-15T10:30:00Z",
  "status": "active|inactive|renewal_pending",
  "access_level": "free|premium"
}
```

#### competitions.json
```json
{
  "_id": "comp-id",
  "comp_id": "COMP-2026-001",
  "title": "National Essay Competition 2026",
  "description": "Competition description",
  "category": "Essay Writing|Painting|Poster|Video|Quiz",
  "comp_type": "group_quiz|online_quiz|online_submission|contest",
  "last_date": "2026-05-31T23:59:59Z",
  "event_date": "2026-06-15",
  "status": "open|closed|completed",
  "group_size": 1,
  "time_limit": 60,
  "questions": [],
  "prizes": { "first": "₹5000", "second": "₹3000", "third": "₹1000" },
  "created_by": "admin-user-id",
  "created_at": "2026-01-15T10:30:00Z"
}
```

#### competition_registrations.json
```json
{
  "_id": "reg-id",
  "comp_id": "COMP-2026-001",
  "comp_title": "National Essay Competition 2026",
  "user_id": "student-user-id",
  "name": "Participant Name",
  "email": "participant@example.com",
  "institution": "College Name",
  "state": "State",
  "group_members": ["member1", "member2"],
  "registration_date": "2026-02-01T10:30:00Z",
  "fee_paid": true,
  "status": "registered|submitted|evaluated"
}
```

#### certificates.json
```json
{
  "_id": "cert-id",
  "cert_number": "AISUCERTCOMP2026000145",
  "participant_name": "Winner Name",
  "participant_email": "winner@example.com",
  "participant_mobile": "+919876543210",
  "participant_id": "unique-id-if-member",
  "program": "National Essay Competition 2026",
  "prog_code": "COMP",
  "cert_type": "Winner|Participation|Runner-up",
  "issued_at": "2026-06-15T10:00:00Z",
  "issued_by": "admin-user-id",
  "status": "valid|revoked|replaced",
  "file": "/uploads/certificates/cert-file-name.pdf"
}
```

#### complaints.json
```json
{
  "_id": "complaint-id",
  "complaint_id": "COMP-2026-00001",
  "complainant_id": "user-id",
  "complainant_name": "Complainant Name",
  "complainant_email": "complainant@example.com",
  "category": "Malpractice|Administrative|Financial|Others",
  "subject": "Complaint Subject",
  "description": "Detailed complaint description",
  "status": "filed|under_review|action_taken|resolved|disposed",
  "assigned_to": ["dept-head-user-id"],
  "actions": [
    {
      "action_date": "2026-02-05T10:30:00Z",
      "action_details": "Initial investigation started",
      "actor": "dept-head-user-id"
    }
  ],
  "filed_at": "2026-02-01T10:30:00Z",
  "resolution_date": "2026-02-15T15:00:00Z"
}
```

#### internships.json
```json
{
  "_id": "internship-id",
  "user_id": "user-id",
  "applicant_name": "Applicant Name",
  "email": "applicant@example.com",
  "department": "IT Cell|Legal Cell|Press Department|Innovation Cell",
  "role_interest": "Role Title",
  "status": "applied|selected|ongoing|completed",
  "start_date": "2026-03-01",
  "end_date": "2026-05-31",
  "assigned_to": "dept-head-user-id",
  "applied_at": "2026-02-01T10:30:00Z",
  "selected_at": "2026-02-10T10:30:00Z"
}
```

#### proposals.json (Innovation Cell)
```json
{
  "_id": "proposal-id",
  "proposal_id": "AISUIC20260025",
  "proposer_id": "user-id",
  "proposer_name": "Innovator Name",
  "title": "Innovation Title",
  "problem_statement": "Problem description",
  "proposed_solution": "Solution proposal",
  "implementation_plan": "Step-by-step plan",
  "expected_impact": "Expected outcomes",
  "funding_required": 50000,
  "documents": ["/uploads/innovations/doc.pdf"],
  "status": "submitted|under_review|modification_requested|approved|rejected",
  "reviewed_by": "reviewer-user-id",
  "submitted_at": "2026-02-01T10:30:00Z",
  "funding_received": 0,
  "funds_transferred_at": null
}
```

#### affiliations.json
```json
{
  "_id": "affiliation-id",
  "affiliation_id": "FIYAOA20260015",
  "organization_name": "Partner Organization",
  "representative_name": "Authorized Person",
  "email": "representative@org.com",
  "institution_type": "University|College|School|NGO|Corporate",
  "state": "State Name",
  "status": "submitted|approved|rejected",
  "applied_at": "2026-02-01T10:30:00Z",
  "approved_at": "2026-02-10T10:30:00Z",
  "approved_by": "national-team-user-id"
}
```

#### newsletter_subscriptions.json
```json
{
  "_id": "subscription-id",
  "email": "subscriber@example.com",
  "name": "Subscriber Name",
  "source": "/press.html",
  "status": "subscribed",
  "created_at": "2026-05-01T12:00:00Z"
}
```

---

## 8. Deployment Guide <a name="deployment"></a>

### Production Deployment Checklist

#### Pre-Deployment
- [ ] Update JWT_SECRET in config.php
- [ ] Configure SMTP credentials for production email
- [ ] Update database location (consider real database migration)
- [ ] Enable HTTPS/SSL certificates
- [ ] Configure firewall rules
- [ ] Set up backup system

#### Environment Configuration

**Production config.php:**
```php
define('JWT_SECRET', getenv('JWT_SECRET_KEY') ?: 'change-this-to-random-string');
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_USER', getenv('SMTP_USER') ?: 'your-email@aisu.org');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'your-app-password');
define('FROM_NAME', 'All India Students Union (AISU)');
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB for production
```

#### Server Deployment Options

**Option 1: Shared Hosting (cPanel)**
1. Upload FTP files to shared hosting
2. Create PHP wrapper script for Node.js server
3. Configure CronJob for quiz server
4. Email integration with hosting provider's SMTP

**Option 2: VPS/Cloud Server**
1. Install Ubuntu 20.04 LTS or similar
2. Install PHP 8.0, Node.js 20+
3. Install and configure Nginx as reverse proxy
4. Install SSL certificate (Let's Encrypt)
5. Set up systemd services for auto-start

**Option 3: Docker Containerization**
```dockerfile
# Create Docker image for deployment
FROM php:8.0-fpm
RUN apt-get update && apt-get install -y nodejs npm
WORKDIR /app
COPY . .
RUN npm install
EXPOSE 3000 3001 8000
CMD ["docker-compose up"]
```

#### Nginx Reverse Proxy Configuration

```nginx
# Reverse proxy for PHP backend
location /api/ {
    proxy_pass http://localhost:8000;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
}

# WebSocket proxy for quiz server
location /socket.io/ {
    proxy_pass http://localhost:3001/socket.io;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
}

# Static frontend files
location / {
    root /path/to/aisu-website;
    try_files $uri $uri/ /index.html;
}
```

#### Database Migration to Production

**From JSON to MySQL (Optional):**
1. Create migration scripts
2. Run data import
3. Update database abstraction layer
4. Test all API endpoints

**SQL Schema Example:**
```sql
CREATE TABLE users (
  id VARCHAR(50) PRIMARY KEY,
  email VARCHAR(100) UNIQUE,
  mobile VARCHAR(15) UNIQUE,
  password_hash VARCHAR(255),
  role ENUM('admin','member','student','user'),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE competitions (
  id VARCHAR(50) PRIMARY KEY,
  comp_id VARCHAR(50) UNIQUE,
  title VARCHAR(255),
  status ENUM('open','closed','completed'),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add more tables as needed
```

---

## 9. Troubleshooting <a name="troubleshooting"></a>

### Common Issues & Solutions

#### Issue 1: "Cannot connect to server" Error

**Symptoms:**
- Frontend shows "Cannot connect to API"
- Quiz room connection fails

**Solutions:**
```bash
# Check if PHP backend is running
curl http://localhost:8000/api/health

# Check if quiz server is running
curl http://localhost:3001

# Check firewall settings
# Windows Firewall: Allow PHP and Node.js
# Linux: iptables or UFW rules

# Restart all servers
start-all-servers.bat
```

#### Issue 2: File Upload Not Working

**Symptoms:**
- Upload errors
- Files not saved to `/uploads/`

**Solutions:**
```bash
# Check directory permissions
chmod 755 backend-php/uploads
chmod 755 backend-php/uploads/gallery
chmod 755 backend-php/uploads/press

# Verify max upload size in PHP
php.ini: upload_max_filesize = 50M
         post_max_size = 50M

# Check browser console for client-side errors
```

#### Issue 3: Email Not Sending

**Symptoms:**
- No emails for registrations, certificates, etc.
- Silent failures in logs

**Solutions:**
```php
// Check SMTP configuration
// Verify Gmail's "Less secure app" is enabled
// OR use Gmail App Passwords for 2FA accounts

// Test email function
send_certificate_issued('test@example.com', 'Test User', 'CERT123', 'Participation', 'Competition');

// Check email logs in error_log
tail -f /var/log/error_log
```

#### Issue 4: WebRTC Video/Audio Not Working

**Symptoms:**
- Black video screens
- No audio in quiz room
- "Permission Denied" errors

**Solutions:**
```javascript
// Check browser permissions
// Chrome: Settings > Privacy > Site Settings > Camera/Microphone

// Verify WebRTC support
if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
  console.log('WebRTC supported');
} else {
  console.error('WebRTC not supported');
}

// Check STUN server connectivity
// In js/quiz-webrtc.js - verify STUN servers
```

#### Issue 5: Quiz Room Connection Issues

**Symptoms:**
- Participants can't join room
- WebSocket connection timeout
- Admin controls not working

**Solutions:**
```bash
# Verify Node.js server is running
ps aux | grep node
# or
Get-Process node

# Check if port 3001 is in use
netstat -ano | findstr :3001
# or
ss -tulpn | grep 3001

# Restart quiz server
cd quiz-server
npm start

# Check browser console for WebSocket errors
```

---

## 10. Future Enhancements <a name="future"></a>

### Phase 2 Features (Recommended)

1. **Database Migration**
   - Move from JSON to MySQL/PostgreSQL
   - Better scalability and query performance
   - Improved concurrent access handling
   - Backup and recovery capabilities

2. **Advanced Video Features**
   - Screen sharing in quiz rooms
   - Whiteboard collaboration
   - Chat transcripts and history
   - Video recording and playback

3. **Mobile Application**
   - Native Android/iOS apps
   - Push notifications
   - Offline capability
   - QR code-based registration

4. **AI/ML Integration**
   - Plagiarism detection for submissions
   - Automated scoring for essays
   - Personalized recommendations
   - Predictive analytics

5. **Payment Gateway Integration**
   - Razorpay/PayPal for fees
   - Prize distribution
   - Sponsorship management
   - Donation tracking

6. **Analytics Dashboard**
   - Participant demographics
   - Competition performance metrics
   - Complaint resolution analytics
   - Revenue tracking

7. **Blockchain Certificates**
   - Immutable certificate storage
   - Verifiable credentials
   - Digital wallets integration

8. **Multi-language Support**
   - Internationalization (i18n)
   - Regional language support
   - Automatic translation

9. **Enhanced Accessibility**
   - WCAG 2.1 AA compliance
   - Screen reader optimization
   - Keyboard navigation
   - High contrast mode

10. **Performance Optimization**
    - CDN integration
    - Image optimization
    - Code splitting and lazy loading
    - Service worker for offline support

---

## Support & Maintenance

### Regular Maintenance Tasks
- **Daily:** Monitor server logs, backup data
- **Weekly:** Check API performance, email logs
- **Monthly:** Update dependencies, security patches
- **Quarterly:** Full system audit, performance review

### Contact Information
- **IT Cell:** itcell.aisu@gmail.com
- **Technical Support:** [support-email]
- **Reporting Issues:** [github-repository]

---

## License & Legal

**AISU Website © 2026**
- All India Students Union
- National-Level Organization
- Registration Number: [To be filled]
- Jurisdiction: All Indian States

### Terms of Use
- [Link to terms page]

### Privacy Policy
- [Link to privacy page]

### Data Protection
- GDPR Compliant
- Regular security audits
- Data encryption in transit and at rest

---

**Document Version:** 2.1  
**Last Updated:** April 6, 2026  
**Next Review:** July 6, 2026

---

## Appendix

### A. File Structure
```
AISU-Website/
├── index.html                    # Home page
├── about.html                    # About us
├── team.html                     # Our team
├── primary-membership.html       # Primary membership form
├── student-membership.html       # Student membership form
├── affiliation.html              # Organization affiliation
├── competition.html              # Competition portal
├── quiz-room.html                # Group quiz room (WebRTC-enabled)
├── internship.html               # Internship opportunities
├── innovations.html              # Innovation cell proposals
├── complaint.html                # Complaint filing
├── press.html                    # Press releases & gallery
├── gallery.html                  # Photo gallery & results
├── cert-verify.html              # Certificate verification
├── login.html                    # User login
├── admin.html                    # Admin portal
├── contact.html                  # Contact information
│
├── css/
│   ├── bootstrap.min.css         # Bootstrap framework
│   ├── aisu-custom.css           # Custom AISU styling
│   └── style.css                 # Additional styles
│
├── js/
│   ├── api.js                    # API client library
│   ├── aisu-forms.js             # Form handlers
│   ├── aisu-main.js              # Main functionality
│   ├── main.js                   # Helper functions
│   └── quiz-webrtc.js            # WebRTC implementation
│
├── img/                           # Images and assets
├── imgnew/                        # Updated images
│
├── backend-php/                   # PHP API Backend
│   ├── config.php                # Configuration
│   ├── db.php                    # Database abstraction
│   ├── index.php                 # API router
│   ├── jwt_handler.php           # JWT authentication
│   ├── email_service.php         # Email functionality
│   ├── utils.php                 # Utility functions
│   ├── scheduler.php             # Background tasks
│   │
│   ├── routes/                   # API endpoints
│   │   ├── auth.php              # Authentication
│   │   ├── members.php           # Primary members
│   │   ├── students.php          # Student members
│   │   ├── competitions.php      # Competitions
│   │   ├── certs.php             # Certificates
│   │   ├── complaints.php        # Complaints
│   │   ├── internship.php        # Internships
│   │   ├── icell.php             # Innovation cell
│   │   ├── affiliation.php       # Affiliations
│   │   ├── quiz.php              # Quiz management
│   │   ├── admin.php             # Admin functions
│   │   └── contact.php           # Contact form
│   │
│   ├── data/                     # Data storage
│   │   ├── users.json
│   │   ├── competitions.json
│   │   ├── certificates.json
│   │   ├── complaints.json
│   │   └── [other collections]
│   │
│   ├── uploads/                  # File uploads
│   │   ├── gallery/
│   │   ├── press/
│   │   ├── certificates/
│   │   ├── cert_templates/
│   │   ├── govtid/
│   │   └── [other folders]
│   │
│   ├── php/                      # PHP runtime (optional)
│   └── start-server.bat
│
├── quiz-server/                  # Node.js WebSocket Server
│   ├── quiz-server.js            # Server implementation
│   ├── package.json              # Node dependencies
│   ├── node_modules/             # Dependencies
│   └── nodejs/                   # Node.js runtime
│
└── start-all-servers.bat         # Batch file to start all servers

```

### B. Key Files Reference

| File | Purpose | Language |
|------|---------|----------|
| `index.html` | Landing page | HTML + JS |
| `api.js` | REST client | JavaScript |
| `quiz-webrtc.js` | WebRTC handler | JavaScript |
| `index.php` | API router | PHP |
| `db.php` | Database layer | PHP |
| `jwt_handler.php` | Authentication | PHP |
| `quiz-server.js` | Real-time server | Node.js |
| `aisu-custom.css` | Branding styles | CSS |
| `config.php` | Settings | PHP |

---

**END OF DOCUMENTATION**

For further assistance or updates, contact the IT Cell or development team.
