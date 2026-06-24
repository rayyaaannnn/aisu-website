# AISU Deployment Package

Scripts for deploying the AISU website to cPanel hosting.

## Files

- **setup.php** — First-run setup (checks PHP, extensions, permissions, DB, config)
- **build-zip.php** — Creates a deployment zip excluding dev files

## Quick Start

1. Upload all files to **public_html** via cPanel File Manager
2. Run: `php deploy/setup.php` (via SSH or cPanel terminal)
3. Edit: `backend-php/.env` with your real credentials
4. Run: `php backend-php/migrate.php` to initialize the database
5. Set permissions: `backend-php/data/` and `backend-php/uploads/` to 755
6. Verify: Visit your domain — the site should load
7. For quiz rooms: Deploy `quiz-server/` on a Node.js host separately