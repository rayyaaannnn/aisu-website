# AISU Website — cPanel / Shared Hosting Deployment Guide

This guide explains how to deploy the AISU Website (Frontend + Vanilla PHP Backend) to a standard shared hosting environment like cPanel.

## Prerequisites

1. A domain name (e.g., `aisu.org`).
2. cPanel hosting with PHP 8.0+ enabled.

---

## 🚀 Step 1: Prepare Files for Upload

1. Open the project folder `AISU-Website_PHP` on your local computer.
2. Select **all files and folders** inside this directory (including `index.html`, `backend-php/`, `css/`, etc.).
3. Right-click and compress them into a single zip file (e.g., `aisu-website.zip`).

---

## 🚀 Step 2: Upload to cPanel

1. Log in to your cPanel dashboard.
2. Click on **File Manager**.
3. Navigate to `public_html` (this is usually the root directory for your primary domain).
   - *If you are deploying to an Addon Domain or Subdomain, navigate to that specific folder instead.*
4. Click **Upload** in the top menu and select `aisu-website.zip`.
5. Once uploaded (100%), go back to the File Manager.
6. Right-click the `aisu-website.zip` file and select **Extract**.
7. Delete the `aisu-website.zip` file to save space.

---

## 🚀 Step 3: Set Correct Permissions (Crucial for Data/Uploads)

Since the PHP backend uses JSON files for the database and stores uploaded images locally, those directories **must be writable** by the server.

1. In File Manager, open the `backend-php` folder.
2. Right-click the **`data`** folder and click **Change Permissions**.
   - Set the permissions to `755` (Read/Write/Execute for User, Read/Execute for Group/World).
   - *(If `755` doesn't work on your specific host and you get permission errors when registering, try `775` or `777` as a last resort, but `755` is recommended for security).*
3. Right-click the **`uploads`** folder and change permissions to `755` as well.
4. If you have any `.json` files inside `data/`, ensure they are set to `644`.

---

## 🚀 Step 4: Email SMTP Configuration

For the website to send emails (Forgot Password, Registration Approvals), you must update the SMTP details:

1. In File Manager, edit `backend-php/config.php`.
2. Locate the **SMTP Configuration** block:
   ```php
   define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
   define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
   define('SMTP_USER', getenv('SMTP_USER') ?: 'your-email@gmail.com');
   define('SMTP_PASS', getenv('SMTP_PASS') ?: 'your-app-password');
   ```
3. Replace the fallback strings (`'smtp.gmail.com'`, `'your-email@gmail.com'`, etc.) with your actual email credentials.
   > **Note**: If using Gmail, you **must** use an "App Password" generated from your Google Account settings, NOT your regular login password.

---

## 🚀 Step 5: Handling the Quiz Server (WebSocket)

The real-time Quiz functionality relies on a **Node.js** server (`quiz-server` folder). Shared hosting typically runs PHP natively, but running Node.js requires special steps.

### Option A: cPanel Node.js App (If supported)
1. In cPanel, search for **Setup Node.js App**.
2. Create a new Application.
3. Set Node.js version to 18+ or 20+.
4. Set Application root to `quiz-server`.
5. Application URL could be a subdomain like `quiz.aisu.org`.
6. Click **Run NPM Install**, then Start the app.
7. You will need to edit `quiz-room.html` on the frontend and change the `SOCKET_URL` to point to `wss://quiz.aisu.org`.

### Option B: Host Quiz Server on Render / Railway (Recommended)
Since shared hosting Node.js can be restrictive:
1. Upload the `quiz-server` folder to a free service like [Render.com](https://render.com) or [Railway.app](https://railway.app).
2. It will give you a URL like `https://aisu-quiz.onrender.com`.
3. In your `quiz-room.html` file on cPanel, change the `SOCKET_URL` to point to that Render URL.

---

## ✅ Step 6: Verify Deployment

1. Visit your domain: `https://yourdomain.com`.
2. Ensure the layout and images load correctly.
3. Check the API connection by visiting `https://yourdomain.com/backend-php/api/health` directly in your browser. It should return a JSON status page.
4. Try registering a test student member and check the Admin Portal to ensure the database (`data/` folder) is saving data correctly.

**Congratulations! Your AISU Website is now live.**
