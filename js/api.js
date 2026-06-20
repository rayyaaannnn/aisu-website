// ============================================================
//  js/api.js — AISU Frontend API Client
//  Supports both Python/Flask (port 5000) and PHP (port 8000)
//  To switch backend: localStorage.setItem('aisu_api_base', 'http://localhost:8000/api')
// ============================================================

const getApiBase = () => {
    const override = localStorage.getItem('aisu_api_base');
    if (override) return override;
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        return 'http://localhost:8000/api';
    }
    return '/backend-php/api';
};
const API_BASE = getApiBase();

// ── Token helpers ────────────────────────────────────────────
function getToken()  { return localStorage.getItem('aisu_token'); }
function setToken(t) { localStorage.setItem('aisu_token', t); }
function clearToken(){ localStorage.removeItem('aisu_token'); localStorage.removeItem('aisu_user'); }
function getUser()   { try { return JSON.parse(localStorage.getItem('aisu_user')); } catch { return null; } }
function setUser(u)  { localStorage.setItem('aisu_user', JSON.stringify(u)); }

// ── Generic fetch wrapper ────────────────────────────────────
async function apiCall(method, path, body = null, isForm = false) {
    const headers = {};
    const token   = getToken();
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const options = { method, headers };

    if (body) {
        if (isForm) {
            // FormData — no Content-Type header (browser sets boundary)
            options.body = body;
        } else {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
    }

    try {
        const res  = await fetch(API_BASE + path, options);
        const json = await res.json();
        if (!res.ok && res.status === 401) {
            // Try refresh
            const refreshed = await refreshToken();
            if (refreshed) return apiCall(method, path, body, isForm);
            clearToken();
            window.location.href = 'login.html';
            return null;
        }
        return json;
    } catch (e) {
        console.error('API Error:', e);
        return { success: false, message: 'Network error. Is the backend running?' };
    }
}

async function refreshToken() {
    const stored = localStorage.getItem('aisu_refresh');
    if (!stored) return false;
    try {
        const res = await fetch(API_BASE + '/auth/refresh', {
            method : 'POST',
            headers: { 'Authorization': `Bearer ${stored}` }
        });
        const json = await res.json();
        if (json.success && json.data?.access_token) {
            setToken(json.data.access_token);
            return true;
        }
    } catch {}
    return false;
}

// ── AUTH ─────────────────────────────────────────────────────
const Auth = {
    async login(email, password) {
        const r = await apiCall('POST', '/auth/login', { email, password });
        if (r?.success) {
            setToken(r.data.access_token);
            localStorage.setItem('aisu_refresh', r.data.refresh_token);
            setUser(r.data.user);
        }
        return r;
    },
    logout() {
        clearToken();
        localStorage.removeItem('aisu_refresh');
        window.location.href = 'login.html';
    },
    async me() { return apiCall('GET', '/auth/me'); },
    async changePassword(old_password, new_password) {
        return apiCall('POST', '/auth/change-password', { old_password, new_password });
    },
    isLoggedIn() { return !!getToken(); },
    getUser,
};

// ── PRIMARY MEMBERSHIP ────────────────────────────────────────
const PrimaryMembers = {
    async apply(formData) {
        return apiCall('POST', '/members/apply', formData, true);
    },
    async list(status, state) {
        let q = [];
        if (status) q.push(`status=${status}`);
        if (state)  q.push(`state=${state}`);
        return apiCall('GET', `/members/${q.length ? '?' + q.join('&') : ''}`);
    },
    async get(id)          { return apiCall('GET', `/members/${id}`); },
    async approve(id, designation) { return apiCall('POST', `/members/${id}/approve`, { designation }); },
    async reject(id, reason)       { return apiCall('POST', `/members/${id}/reject`,  { reason }); },
    async stats()          { return apiCall('GET', '/members/stats/summary'); },
};

// ── STUDENT MEMBERSHIP ────────────────────────────────────────
const StudentMembers = {
    async apply(formData) { return apiCall('POST', '/students/apply', formData, true); },
    async list(status)    {
        const q = status ? `?status=${status}` : '';
        return apiCall('GET', `/students/${q}`);
    },
    async approve(id)     { return apiCall('POST', `/students/${id}/approve`); },
    async reject(id, reason) { return apiCall('POST', `/students/${id}/reject`, { reason }); },
    async stats()         { return apiCall('GET', '/students/stats/summary'); },
};

// ── COMPLAINTS ────────────────────────────────────────────────
const Complaints = {
    async submit(formData)    { return apiCall('POST', '/complaints/submit', formData, true); },
    async track(complaint_id) { return apiCall('GET', `/complaints/track/${complaint_id}`); },
    async list(status)        {
        const q = status ? `?status=${status}` : '';
        return apiCall('GET', `/complaints/${q}`);
    },
    async update(id, updates) { return apiCall('POST', `/complaints/${id}/update`, updates); },
};

// ── CONTACT ───────────────────────────────────────────────────
const Contact = {
    async send(data) { return apiCall('POST', '/contact/send', data); },
    async list()     { return apiCall('GET', '/contact/'); },
};

// ── CERTIFICATES ──────────────────────────────────────────────
const Certs = {
    async verify(cert_id) { return apiCall('GET', `/certs/verify/${cert_id}`); },
    async issue(data)     { return apiCall('POST', '/certs/issue', data); },
    async list()          { return apiCall('GET', '/certs/'); },
    async revoke(id)      { return apiCall('POST', `/certs/${id}/revoke`); },
};

// ── INTERNSHIP ────────────────────────────────────────────────
const Internship = {
    async apply(formData) { return apiCall('POST', '/internship/apply', formData, true); },
    async list()          { return apiCall('GET', '/internship/'); },
    async approve(id)     { return apiCall('POST', `/internship/${id}/approve`); },
    async reject(id, reason) { return apiCall('POST', `/internship/${id}/reject`, { reason }); },
};

// ── AFFILIATION ───────────────────────────────────────────────
const Affiliation = {
    async apply(formData) { return apiCall('POST', '/affiliation/apply', formData, true); },
    async list()          { return apiCall('GET', '/affiliation/'); },
    async approve(id)     { return apiCall('POST', `/affiliation/${id}/approve`); },
    async reject(id, reason) { return apiCall('POST', `/affiliation/${id}/reject`, { reason }); },
};

// ── ADMIN ─────────────────────────────────────────────────────
const Admin = {
    async stats()                   { return apiCall('GET', '/admin/stats'); },
    async listUsers()               { return apiCall('GET', '/admin/users'); },
    async createUser(data)          { return apiCall('POST', '/admin/users/create', data); },
    async updateUser(id, data)      { return apiCall('PUT', `/admin/users/${id}`, data); },
    async deactivateUser(id)        { return apiCall('POST', `/admin/users/${id}/deactivate`); },
    async search(q)                 { return apiCall('GET', `/admin/search?q=${encodeURIComponent(q)}`); },
};

// ── RBAC (Role-Based Access Control) ─────────────────────────
const RBAC = {
    // Get current user's permissions, panels, and designations
    async myPermissions()           { return apiCall('GET', '/rbac/my-permissions'); },
    // Get role hierarchy and designation options
    async roles()                   { return apiCall('GET', '/rbac/roles'); },
    // Promote a member
    async promote(id, data)         { return apiCall('POST', `/rbac/promote/${id}`, data); },
    // Demote a member
    async demote(id, data)          { return apiCall('POST', `/rbac/demote/${id}`, data); },
    // Transfer a member
    async transfer(id, data)        { return apiCall('POST', `/rbac/transfer/${id}`, data); },
    // Add additional responsibility
    async addRole(id, data)         { return apiCall('POST', `/rbac/additional-role/${id}`, data); },
    // Get all promotion history (optional filters: member_id, action, level, limit)
    async history(params = {})      {
        const q = Object.entries(params).filter(([,v]) => v).map(([k,v]) => `${k}=${encodeURIComponent(v)}`).join('&');
        return apiCall('GET', `/rbac/history${q ? '?' + q : ''}`);
    },
    // Get specific member's history
    async memberHistory(memberId)   { return apiCall('GET', `/rbac/history/${memberId}`); },
    // Get team directory (public)
    async teamDirectory(level, state) {
        const q = [];
        if (level && level !== 'all') q.push(`level=${encodeURIComponent(level)}`);
        if (state && state !== 'all') q.push(`state=${encodeURIComponent(state)}`);
        return apiCall('GET', `/rbac/team-directory${q.length ? '?' + q.join('&') : ''}`);
    },
    // Get designations for a role
    async designations(role)        { return apiCall('GET', `/rbac/designations/${role}`); },
    // Sync team data
    async syncTeam()                { return apiCall('POST', '/rbac/sync-team'); },
    // Designation catalog (full)
    async designationCatalog()      { return apiCall('GET', '/rbac/designations-catalog'); },
    // Add a designation to a level
    async addDesignation(level, title) { return apiCall('POST', '/rbac/designations', { level, title }); },
    // Delete a designation from a level
    async deleteDesignation(level, title) { return apiCall('DELETE', '/rbac/designations', { level, title }); },
    // Assign designation to a member (handles approval workflow)
    async assignDesignation(id, data) { return apiCall('POST', `/rbac/assign-designation/${id}`, data); },
    // Get pending multi-designation approvals
    async pendingApprovals()        { return apiCall('GET', '/rbac/pending-approvals'); },
    // Approve a designation request
    async approveDesignation(id)    { return apiCall('POST', `/rbac/approve-designation/${id}`); },
    // Reject a designation request
    async rejectDesignation(id, reason) { return apiCall('POST', `/rbac/reject-designation/${id}`, { reason }); },
    // Suspend a member (with number of days)
    async suspend(id, days, note)   { return apiCall('POST', `/rbac/suspend/${id}`, { days, note }); },
    // Dismiss a member
    async dismiss(id, note)         { return apiCall('POST', `/rbac/dismiss/${id}`, { note }); },
    // Approve resignation
    async approveResignation(id, note) { return apiCall('POST', `/rbac/approve-resignation/${id}`, { note }); },
    // Remove additional charge
    async removeAdditionalRole(id, designation) { return apiCall('POST', `/rbac/remove-additional-role/${id}`, { designation }); },
};

// ── RBAC Frontend Helpers ─────────────────────────────────────
function hasPermission(permission) {
    const user = getUser();
    return user && user.permissions && user.permissions.includes(permission);
}

function getAllowedPanels() {
    const user = getUser();
    return (user && user.panels) || [];
}

function getUserRole() {
    const user = getUser();
    return (user && user.role) || 'user';
}

function getUserDesignation() {
    const user = getUser();
    return (user && user.designation) || '';
}

// ── Helper: build FormData from a <form> element ──────────────
function formToData(formEl) {
    return new FormData(formEl);
}

// ── Helper: show API response as toast/alert ──────────────────
function showApiResult(result, successEl, errorEl, successMsg = null) {
    if (!result) return;
    if (result.success) {
        if (successEl) {
            successEl.textContent = successMsg || result.message;
            successEl.style.display = 'block';
        }
        if (errorEl) errorEl.style.display = 'none';
    } else {
        if (errorEl) {
            errorEl.textContent = result.message || 'An error occurred';
            errorEl.style.display = 'block';
        }
        if (successEl) successEl.style.display = 'none';
    }
}

// Export to window for use in HTML pages
window.Auth           = Auth;
window.PrimaryMembers = PrimaryMembers;
window.StudentMembers = StudentMembers;
window.Complaints     = Complaints;
window.Contact        = Contact;
window.Certs          = Certs;
window.Internship     = Internship;
window.Affiliation    = Affiliation;
window.Admin          = Admin;
window.RBAC           = RBAC;
window.formToData     = formToData;
window.showApiResult  = showApiResult;
window.hasPermission  = hasPermission;
window.getAllowedPanels = getAllowedPanels;
window.getUserRole    = getUserRole;
window.getUserDesignation = getUserDesignation;
