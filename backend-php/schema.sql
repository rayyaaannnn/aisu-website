-- =============================================================
-- AISU Backend — SQLite Schema
-- Tables are auto-created on first use by db.php
-- This file serves as documentation and can be used for
-- manual setup if needed: sqlite3 data/aisu.sqlite < schema.sql
-- =============================================================

-- ── Sequences (for ID generation) ────────────────────────────
CREATE TABLE IF NOT EXISTS sequences (
    name     TEXT PRIMARY KEY,
    next_val INTEGER NOT NULL DEFAULT 1
);

-- ── 1. users ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    _id          TEXT PRIMARY KEY,
    name         TEXT NOT NULL DEFAULT '',
    email        TEXT NOT NULL DEFAULT '',
    password     TEXT NOT NULL DEFAULT '',
    role         TEXT NOT NULL DEFAULT 'user',
    level        TEXT DEFAULT '',
    designation  TEXT DEFAULT '',
    state        TEXT DEFAULT '',
    district     TEXT DEFAULT '',
    mobile       TEXT DEFAULT '',
    member_id    TEXT DEFAULT '',
    student_id   TEXT DEFAULT '',
    institution  TEXT DEFAULT '',
    status       TEXT NOT NULL DEFAULT 'active',
    created_at   TEXT DEFAULT NULL,
    updated_at   TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_member_id ON users(member_id);
CREATE INDEX IF NOT EXISTS idx_users_student_id ON users(student_id);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);

-- ── 2. primary_members ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS primary_members (
    _id                 TEXT PRIMARY KEY,
    member_id           TEXT NOT NULL DEFAULT '',
    fullname            TEXT NOT NULL DEFAULT '',
    parent_name         TEXT DEFAULT '',
    dob                 TEXT DEFAULT '',
    age                 TEXT DEFAULT '',
    gender              TEXT DEFAULT '',
    address             TEXT DEFAULT NULL,
    pin                 TEXT DEFAULT '',
    institution         TEXT DEFAULT '',
    state               TEXT DEFAULT '',
    district            TEXT DEFAULT '',
    city                TEXT DEFAULT '',
    mobile              TEXT NOT NULL DEFAULT '',
    email               TEXT NOT NULL DEFAULT '',
    govtid_type         TEXT DEFAULT '',
    govtid_number       TEXT DEFAULT '',
    govtid_file         TEXT DEFAULT '',
    payment_proof       TEXT DEFAULT '',
    photo               TEXT DEFAULT '',
    sign                TEXT DEFAULT '',
    heard_about         TEXT DEFAULT '',
    contribution        TEXT DEFAULT NULL,
    justify_answers     TEXT DEFAULT NULL,
    mode_of_submission  TEXT DEFAULT '',
    razorpay_payment_id TEXT DEFAULT '',
    designation         TEXT DEFAULT '',
    level               TEXT DEFAULT '',
    role_status         TEXT DEFAULT 'pending',
    status              TEXT NOT NULL DEFAULT 'pending',
    approved_by         TEXT DEFAULT '',
    approved_at         TEXT DEFAULT NULL,
    expiry_date         TEXT DEFAULT '',
    rejection_reason    TEXT DEFAULT NULL,
    reports             TEXT DEFAULT NULL,
    additional_roles    TEXT DEFAULT NULL,
    department          TEXT DEFAULT '',
    appointment_date    TEXT DEFAULT NULL,
    appointment_order   TEXT DEFAULT NULL,
    last_promoted_at    TEXT DEFAULT NULL,
    last_promoted_by    TEXT DEFAULT '',
    suspended_until     TEXT DEFAULT NULL,
    suspended_days      INTEGER DEFAULT 0,
    suspended_by        TEXT DEFAULT '',
    suspended_at        TEXT DEFAULT NULL,
    role_note           TEXT DEFAULT NULL,
    created_at          TEXT DEFAULT NULL,
    updated_at          TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_pm_member_id ON primary_members(member_id);
CREATE INDEX IF NOT EXISTS idx_pm_email ON primary_members(email);
CREATE INDEX IF NOT EXISTS idx_pm_mobile ON primary_members(mobile);
CREATE INDEX IF NOT EXISTS idx_pm_state ON primary_members(state);
CREATE INDEX IF NOT EXISTS idx_pm_status ON primary_members(status);
CREATE INDEX IF NOT EXISTS idx_pm_role_status ON primary_members(role_status);
CREATE INDEX IF NOT EXISTS idx_pm_level ON primary_members(level);

-- ── 3. student_members ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS student_members (
    _id                 TEXT PRIMARY KEY,
    student_id          TEXT NOT NULL DEFAULT '',
    fullname            TEXT NOT NULL DEFAULT '',
    parent_name         TEXT DEFAULT '',
    dob                 TEXT DEFAULT '',
    age                 TEXT DEFAULT '',
    gender              TEXT DEFAULT '',
    address             TEXT DEFAULT NULL,
    pin                 TEXT DEFAULT '',
    institution         TEXT DEFAULT '',
    state               TEXT DEFAULT '',
    district            TEXT DEFAULT '',
    city                TEXT DEFAULT '',
    mobile              TEXT NOT NULL DEFAULT '',
    email               TEXT NOT NULL DEFAULT '',
    heard_about         TEXT DEFAULT '',
    payment_proof       TEXT DEFAULT '',
    mode_of_submission  TEXT DEFAULT '',
    razorpay_payment_id TEXT DEFAULT '',
    status              TEXT NOT NULL DEFAULT 'pending',
    approved_by         TEXT DEFAULT '',
    approved_at         TEXT DEFAULT NULL,
    expiry_date         TEXT DEFAULT '',
    rejection_reason    TEXT DEFAULT NULL,
    created_at          TEXT DEFAULT NULL,
    updated_at          TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_sm_student_id ON student_members(student_id);
CREATE INDEX IF NOT EXISTS idx_sm_email ON student_members(email);
CREATE INDEX IF NOT EXISTS idx_sm_state ON student_members(state);
CREATE INDEX IF NOT EXISTS idx_sm_status ON student_members(status);

-- ── 4. complaints ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS complaints (
    _id           TEXT PRIMARY KEY,
    complaint_id  TEXT NOT NULL DEFAULT '',
    user_id       TEXT DEFAULT '',
    name          TEXT NOT NULL DEFAULT '',
    email         TEXT DEFAULT '',
    mobile        TEXT DEFAULT '',
    category      TEXT DEFAULT '',
    subject       TEXT DEFAULT '',
    description   TEXT DEFAULT NULL,
    attachment    TEXT DEFAULT '',
    status        TEXT NOT NULL DEFAULT 'open',
    assigned_to   TEXT DEFAULT '',
    response      TEXT DEFAULT NULL,
    resolved_at   TEXT DEFAULT NULL,
    created_at    TEXT DEFAULT NULL,
    updated_at    TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_complaints_id ON complaints(complaint_id);
CREATE INDEX IF NOT EXISTS idx_complaints_status ON complaints(status);
CREATE INDEX IF NOT EXISTS idx_complaints_email ON complaints(email);

-- ── 5. contacts ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS contacts (
    _id        TEXT PRIMARY KEY,
    name       TEXT NOT NULL DEFAULT '',
    email      TEXT NOT NULL DEFAULT '',
    mobile     TEXT DEFAULT '',
    subject    TEXT DEFAULT '',
    message    TEXT DEFAULT NULL,
    status     TEXT NOT NULL DEFAULT 'unread',
    created_at TEXT DEFAULT NULL,
    updated_at TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_contacts_status ON contacts(status);

-- ── 6. certificates ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS certificates (
    _id              TEXT PRIMARY KEY,
    cert_id          TEXT DEFAULT '',
    cert_number      TEXT NOT NULL DEFAULT '',
    template_id      TEXT DEFAULT '',
    competition_id   TEXT DEFAULT '',
    reg_id           TEXT DEFAULT '',
    recipient_name   TEXT DEFAULT '',
    recipient_email  TEXT DEFAULT '',
    category         TEXT DEFAULT '',
    program_code     TEXT DEFAULT '',
    certificate_type TEXT DEFAULT '',
    issue_date       TEXT DEFAULT NULL,
    status           TEXT NOT NULL DEFAULT 'active',
    file_path        TEXT DEFAULT '',
    metadata         TEXT DEFAULT NULL,
    created_at       TEXT DEFAULT NULL,
    updated_at       TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_certs_number ON certificates(cert_number);
CREATE INDEX IF NOT EXISTS idx_certs_status ON certificates(status);
CREATE INDEX IF NOT EXISTS idx_certs_email ON certificates(recipient_email);

-- ── 7. cert_templates ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS cert_templates (
    _id          TEXT PRIMARY KEY,
    name         TEXT NOT NULL DEFAULT '',
    description  TEXT DEFAULT NULL,
    category     TEXT DEFAULT '',
    filename     TEXT DEFAULT '',
    filepath     TEXT DEFAULT '',
    fields       TEXT DEFAULT NULL,
    orientation  TEXT DEFAULT 'landscape',
    page_size    TEXT DEFAULT 'A4',
    created_at   TEXT DEFAULT NULL,
    updated_at   TEXT DEFAULT NULL
);

-- ── 8. competitions ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS competitions (
    _id          TEXT PRIMARY KEY,
    comp_id      TEXT NOT NULL DEFAULT '',
    title        TEXT NOT NULL DEFAULT '',
    description  TEXT DEFAULT NULL,
    category     TEXT NOT NULL DEFAULT '',
    comp_type    TEXT NOT NULL DEFAULT '',
    last_date    TEXT DEFAULT '',
    event_date   TEXT DEFAULT '',
    group_size   INTEGER DEFAULT 1,
    time_limit   INTEGER DEFAULT 0,
    questions    TEXT DEFAULT NULL,
    instructions TEXT DEFAULT NULL,
    status       TEXT NOT NULL DEFAULT 'open',
    created_by   TEXT DEFAULT '',
    results      TEXT DEFAULT NULL,
    created_at   TEXT DEFAULT NULL,
    updated_at   TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_comps_comp_id ON competitions(comp_id);
CREATE INDEX IF NOT EXISTS idx_comps_status ON competitions(status);
CREATE INDEX IF NOT EXISTS idx_comps_category ON competitions(category);

-- ── 9. competition_registrations ────────────────────────────
CREATE TABLE IF NOT EXISTS competition_registrations (
    _id                  TEXT PRIMARY KEY,
    comp_id              TEXT NOT NULL DEFAULT '',
    comp_title           TEXT DEFAULT '',
    user_id              TEXT DEFAULT '',
    name                 TEXT DEFAULT '',
    email                TEXT DEFAULT '',
    institution          TEXT DEFAULT '',
    state                TEXT DEFAULT '',
    group_members        TEXT DEFAULT NULL,
    submission_file      TEXT DEFAULT '',
    submitted_at         TEXT DEFAULT NULL,
    submission_note      TEXT DEFAULT NULL,
    quiz_started         INTEGER DEFAULT 0,
    quiz_start_time      TEXT DEFAULT NULL,
    quiz_submitted       INTEGER DEFAULT 0,
    quiz_submit_time     TEXT DEFAULT NULL,
    answers              TEXT DEFAULT NULL,
    score                INTEGER DEFAULT 0,
    score_percent        REAL DEFAULT 0.0,
    disqualified         INTEGER DEFAULT 0,
    disqualification_reason TEXT DEFAULT NULL,
    status               TEXT DEFAULT 'registered',
    created_at           TEXT DEFAULT NULL,
    updated_at           TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_cr_comp_id ON competition_registrations(comp_id);
CREATE INDEX IF NOT EXISTS idx_cr_user_id ON competition_registrations(user_id);
CREATE INDEX IF NOT EXISTS idx_cr_email ON competition_registrations(email);

-- ── 10. internships ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS internships (
    _id              TEXT PRIMARY KEY,
    fullname         TEXT NOT NULL DEFAULT '',
    email            TEXT NOT NULL DEFAULT '',
    mobile           TEXT DEFAULT '',
    address          TEXT DEFAULT NULL,
    qualification    TEXT DEFAULT '',
    institution      TEXT DEFAULT '',
    state            TEXT DEFAULT '',
    district         TEXT DEFAULT '',
    duration         TEXT DEFAULT '',
    area_of_interest TEXT DEFAULT '',
    resume           TEXT DEFAULT '',
    status           TEXT NOT NULL DEFAULT 'pending',
    approved_by      TEXT DEFAULT '',
    rejection_reason TEXT DEFAULT NULL,
    created_at       TEXT DEFAULT NULL,
    updated_at       TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_int_email ON internships(email);
CREATE INDEX IF NOT EXISTS idx_int_status ON internships(status);

-- ── 11. affiliations ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS affiliations (
    _id              TEXT PRIMARY KEY,
    org_name         TEXT NOT NULL DEFAULT '',
    org_type         TEXT DEFAULT '',
    address          TEXT DEFAULT NULL,
    city             TEXT DEFAULT '',
    state            TEXT DEFAULT '',
    district         TEXT DEFAULT '',
    pin              TEXT DEFAULT '',
    contact_person   TEXT DEFAULT '',
    designation      TEXT DEFAULT '',
    email            TEXT NOT NULL DEFAULT '',
    mobile           TEXT DEFAULT '',
    website          TEXT DEFAULT '',
    affiliation_type TEXT DEFAULT '',
    document         TEXT DEFAULT '',
    status           TEXT NOT NULL DEFAULT 'pending',
    approved_by      TEXT DEFAULT '',
    rejection_reason TEXT DEFAULT NULL,
    created_at       TEXT DEFAULT NULL,
    updated_at       TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_aff_email ON affiliations(email);
CREATE INDEX IF NOT EXISTS idx_aff_status ON affiliations(status);

-- ── 12. innovations ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS innovations (
    _id               TEXT PRIMARY KEY,
    proposal_id       TEXT NOT NULL DEFAULT '',
    user_id           TEXT DEFAULT '',
    name              TEXT DEFAULT '',
    email             TEXT DEFAULT '',
    mobile            TEXT DEFAULT '',
    institution       TEXT DEFAULT '',
    state             TEXT DEFAULT '',
    district          TEXT DEFAULT '',
    title             TEXT NOT NULL DEFAULT '',
    category          TEXT DEFAULT '',
    description       TEXT DEFAULT NULL,
    problem_statement TEXT DEFAULT NULL,
    proposed_solution TEXT DEFAULT NULL,
    innovation_type   TEXT DEFAULT '',
    domain            TEXT DEFAULT '',
    team_members      TEXT DEFAULT NULL,
    document          TEXT DEFAULT '',
    status            TEXT NOT NULL DEFAULT 'submitted',
    review_notes      TEXT DEFAULT NULL,
    reviewed_by       TEXT DEFAULT '',
    created_at        TEXT DEFAULT NULL,
    updated_at        TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_innov_proposal_id ON innovations(proposal_id);
CREATE INDEX IF NOT EXISTS idx_innov_user_id ON innovations(user_id);
CREATE INDEX IF NOT EXISTS idx_innov_status ON innovations(status);

-- ── 13. newsletter_subscriptions ────────────────────────────
CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
    _id        TEXT PRIMARY KEY,
    name       TEXT DEFAULT '',
    email      TEXT NOT NULL DEFAULT '',
    status     TEXT NOT NULL DEFAULT 'active',
    created_at TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_nl_email ON newsletter_subscriptions(email);

-- ── 14. password_otps ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS password_otps (
    _id        TEXT PRIMARY KEY,
    user_id    TEXT DEFAULT '',
    email      TEXT DEFAULT '',
    otp        TEXT NOT NULL DEFAULT '',
    expires_at TEXT DEFAULT NULL,
    verified   INTEGER DEFAULT 0,
    created_at TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_otp_user_id ON password_otps(user_id);
CREATE INDEX IF NOT EXISTS idx_otp_email ON password_otps(email);

-- ── 15. promotion_history ──────────────────────────────────
CREATE TABLE IF NOT EXISTS promotion_history (
    _id               TEXT PRIMARY KEY,
    member_id         TEXT NOT NULL DEFAULT '',
    member_name       TEXT DEFAULT '',
    action            TEXT NOT NULL DEFAULT '',
    old_role          TEXT DEFAULT '',
    new_role          TEXT DEFAULT '',
    old_level         TEXT DEFAULT '',
    new_level         TEXT DEFAULT '',
    old_designation   TEXT DEFAULT '',
    new_designation   TEXT DEFAULT '',
    old_state         TEXT DEFAULT '',
    new_state         TEXT DEFAULT '',
    old_district      TEXT DEFAULT '',
    new_district      TEXT DEFAULT '',
    promoted_by       TEXT DEFAULT '',
    promoted_by_name  TEXT DEFAULT '',
    note              TEXT DEFAULT NULL,
    effective_date    TEXT DEFAULT NULL,
    created_at        TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_ph_member_id ON promotion_history(member_id);
CREATE INDEX IF NOT EXISTS idx_ph_action ON promotion_history(action);
CREATE INDEX IF NOT EXISTS idx_ph_new_level ON promotion_history(new_level);

-- ── 16. designation_approvals ──────────────────────────────
CREATE TABLE IF NOT EXISTS designation_approvals (
    _id                  TEXT PRIMARY KEY,
    member_id            TEXT DEFAULT '',
    member_name          TEXT DEFAULT '',
    member_internal_id   TEXT DEFAULT '',
    designation          TEXT NOT NULL DEFAULT '',
    level                TEXT DEFAULT '',
    current_designations TEXT DEFAULT NULL,
    designation_number   INTEGER DEFAULT 1,
    requested_by         TEXT DEFAULT '',
    requested_by_name    TEXT DEFAULT '',
    note                 TEXT DEFAULT NULL,
    status               TEXT NOT NULL DEFAULT 'pending',
    approved_by          TEXT DEFAULT '',
    approved_by_name     TEXT DEFAULT '',
    approved_at          TEXT DEFAULT NULL,
    rejected_by          TEXT DEFAULT '',
    rejected_by_name     TEXT DEFAULT '',
    rejected_at          TEXT DEFAULT NULL,
    rejection_reason     TEXT DEFAULT NULL,
    created_at           TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_da_status ON designation_approvals(status);
CREATE INDEX IF NOT EXISTS idx_da_member_id ON designation_approvals(member_id);

-- ── 17. announcements ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS announcements (
    _id        TEXT PRIMARY KEY,
    title      TEXT NOT NULL DEFAULT '',
    type       TEXT DEFAULT 'General',
    content    TEXT DEFAULT NULL,
    link       TEXT DEFAULT '',
    posted_at  TEXT DEFAULT NULL,
    posted_by  TEXT DEFAULT ''
);

-- ── 18. press_releases ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS press_releases (
    _id        TEXT PRIMARY KEY,
    title      TEXT NOT NULL DEFAULT '',
    type       TEXT DEFAULT 'Press Release',
    source     TEXT DEFAULT '',
    content    TEXT DEFAULT NULL,
    event_date TEXT DEFAULT '',
    location   TEXT DEFAULT '',
    pdf_file   TEXT DEFAULT '',
    posted_at  TEXT DEFAULT NULL
);

-- ── 19. gallery ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS gallery (
    _id          TEXT PRIMARY KEY,
    title        TEXT NOT NULL DEFAULT '',
    category     TEXT DEFAULT 'General',
    event_name   TEXT DEFAULT '',
    event_date   TEXT DEFAULT '',
    image        TEXT DEFAULT '',
    uploaded_at  TEXT DEFAULT NULL
);

-- ── 20. quiz_rooms ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS quiz_rooms (
    room_code           TEXT PRIMARY KEY,
    competition_id      TEXT DEFAULT NULL,
    room_name           TEXT DEFAULT 'Quiz Room',
    max_teams           INTEGER DEFAULT 8,
    time_limit_minutes  INTEGER DEFAULT 30,
    status              TEXT DEFAULT 'waiting',
    participants        TEXT DEFAULT NULL,
    teams               TEXT DEFAULT NULL,
    questions           TEXT DEFAULT NULL,
    current_q           INTEGER DEFAULT -1,
    answers             TEXT DEFAULT NULL,
    chat                TEXT DEFAULT NULL,
    created_at          TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_qr_status ON quiz_rooms(status);
