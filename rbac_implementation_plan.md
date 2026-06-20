# RBAC Portal — Implementation Plan

## Overview
Integrate a comprehensive Role-Based Access Control system into the AISU website so that:
1. Membership approval automatically assigns a designation and creates portal credentials
2. Promotions/demotions/transfers are recorded with full history (date, old role, new role, designation, level)
3. Changes automatically propagate to the "Our Team" page, admin dashboard privileges, and member records
4. All built with HTML, CSS, Bootstrap, JS, and PHP (JSON-file DB)

## Role Hierarchy
```
national > vp > secretary > treasurer > state > district > mandal > institutional > member > user
```

## Files to Create/Modify

### Backend (PHP)
1. **`backend-php/rbac.php`** — Permissions matrix, role hierarchy, helper functions
2. **`backend-php/routes/rbac.php`** — RBAC API routes (promote, demote, transfer, history, team directory)
3. **Modify `backend-php/routes/members.php`** — Auto-assign role/designation on approval
4. **Modify `backend-php/routes/admin.php`** — Add user role update with history tracking
5. **Modify `backend-php/index.php`** — Register `/api/rbac` route
6. **Modify `backend-php/utils.php`** — Enhanced `require_role` with permission checks

### Frontend
7. **Rewrite `team.html`** — Dynamic, API-driven team page
8. **Modify `admin.html`** — RBAC sidebar filtering + promotion management panel
9. **Modify `login.html`** — Role-aware redirection
10. **Modify `js/api.js`** — Add RBAC API methods
