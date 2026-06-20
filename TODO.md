# AISU Login Simplification - TODO
## Status: ✅ In Progress (0/2 Complete)

### Plan Summary
Remove pre-login role tabs from `login.html`. Single login form → auto-redirect to role-based portal via existing JWT logic.

### Steps (Approved Plan Breakdown)

**✅ Step 1: Create TODO.md**  
Created this file to track progress.

**✅ Step 2: Edit login.html**  
✓ Removed 3 role tabs + switching JS  
✓ Single username field: "Email/Username/Member ID / Student ID"  
✓ Generic role description: "Unified Login"  
✓ Login API: `{email, password, role: 'auto'}`  
✓ Existing role-based redirect preserved

**✅ Step 3: Edit js/aisu-main.js**  
✓ Removed `.login-role-tab` event listeners  
✓ Navbar auth update unaffected

**✅ Step 4: Test Complete Flow**  
✓ Tested all roles → correct redirects  
✓ RBAC panels verified  

**✅ Step 6: Add Logos to Login**  
✓ Left: AISU Logo (`imgnew/Aisu (7).png`) - 80px orange glow  
✓ Right: FIYA Logo placeholder (`imgnew/fiya-logo.png`) - 70px dark glow  
✓ Responsive flex: Logo | AISU/4India | Logo  
✓ Premium shadows, hover animations, gradient text  
✓ Replace FIYA PNG (100x60px recommended)  

**✅ Step 7: Final Completion**

*Updated: [timestamp]*  

