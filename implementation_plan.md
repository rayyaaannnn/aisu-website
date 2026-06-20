# AISU Designation Management System — Implementation Plan

## Overview
Implement a comprehensive hierarchical designation system across 5 levels (National, State, District, Mandal, College) with:
- Complete designation catalog per the user's specification
- Add/delete designation privilege for top 4 national officers only
- Max 3 designations per individual
- Multi-designation approval workflow (requires approval from National President/VP/Gen Secretary/Joint Secretary)
- Single designation auto-applied without approval

## Files to Create/Modify

### Backend (PHP)
1. **`backend-php/rbac.php`** — Update `DESIGNATION_MAP` with all designations per level
2. **`backend-php/routes/rbac.php`** — Add endpoints:
   - `POST /designations` — Add a new designation to a level (privileged)
   - `DELETE /designations/{level}/{index}` — Remove a designation (privileged)
   - `POST /assign-designation/{memberId}` — Assign designation(s) with approval logic
   - `GET /pending-approvals` — List pending multi-designation approvals
   - `POST /approve-designation/{approvalId}` — Approve a pending multi-designation
   - `POST /reject-designation/{approvalId}` — Reject a pending multi-designation

### Frontend
3. **`admin.html`** — Add "Designation Management" panel in sidebar + panel content
4. **Inline JS in admin.html** — Add designation management UI logic

## Key Rules
- Only **National President**, **National Vice-President**, **National General Secretary**, **National Joint Secretary** can add/delete designations
- Max **3 designations** per individual
- **Single designation** → auto-applied, no approval needed
- **Multiple designations (2nd/3rd)** → requires approval from any of the 4 top officers
- Approval data stored in `data/designation_approvals.json`
