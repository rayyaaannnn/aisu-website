# AISU Website Form Data Storage

This document explains where each website form stores submitted data in the backend.

## Data storage backend
- Backend root: `backend-php/`
- Persisted data files: `backend-php/data/*.json`
- Forms submit to API endpoints under `backend-php/index.php` with route files in `backend-php/routes/`

## Form storage mapping

| Frontend Page | Form Action | API Endpoint | Stored JSON Collection |
|---|---|---|---|
| `primary-membership.html` | Primary membership application | `POST /api/members/apply` | `backend-php/data/primary_members.json` |
| `student-membership.html` | Student membership application | `POST /api/students/apply` | `backend-php/data/student_members.json` |
| `internship.html` | Internship application | `POST /api/internship/apply` | `backend-php/data/internships.json` |
| `affiliation.html` | Affiliation request | `POST /api/affiliation/apply` | `backend-php/data/affiliations.json` |
| `complaint.html` | Complaint filing | `POST /api/complaints/submit` | `backend-php/data/complaints.json` |
| `contact.html` | Contact message | `POST /api/contact/send` | `backend-php/data/contacts.json` |
| Footer newsletter forms (`index.html`, `contact.html`, `team.html`, etc.) | Newsletter subscription | `POST /api/newsletter/subscribe` | `backend-php/data/newsletter_subscriptions.json` |
| `press.html` | Press page subscription | `POST /api/newsletter/subscribe` | `backend-php/data/newsletter_subscriptions.json` |

## New newsletter storage
- Added backend route: `backend-php/routes/newsletter.php`
- Registered in `backend-php/index.php` under `/api/newsletter`
- Stores subscriptions in `backend-php/data/newsletter_subscriptions.json`
- The frontend newsletter forms now submit actual backend requests rather than only displaying a UI confirmation.

## Notes
- The JSON collections are created automatically by `backend-php/db.php` when the first record is inserted.
- Uploaded files (resumes, ID scans, payment proofs, photos, signatures) are stored under `backend-php/uploads/` in type-specific subfolders.
- Existing forms already persist data via backend routes; newsletter subscription is now also persisted.
