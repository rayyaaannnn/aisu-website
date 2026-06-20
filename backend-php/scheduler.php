<?php
// =============================================================
//  scheduler.php — Membership Renewal Checker (PHP cron job)
//
//  Run via cron:  0 6,7 * * * php /path/to/backend-php/scheduler.php
//  Or via Windows Task Scheduler to run daily at 06:00 & 06:30
// =============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/email_service.php';

echo "[" . date('Y-m-d H:i:s') . "] AISU Renewal Scheduler Running...\n";

// ── Check Primary Members (3-year validity) ──────────────────
function checkPrimaryRenewals(): void {
    $members = DB::findAll('primary_members');
    $checked = 0;
    $reminded = 0;

    foreach ($members as $m) {
        if (($m['status'] ?? '') !== 'approved') continue;
        if (empty($m['approved_at'])) continue;

        $days = DB::daysUntilExpiry($m['approved_at'], 3);
        if ($days === null) continue;
        $checked++;

        if ($days <= 0) {
            // Expired — mark inactive
            DB::updateOne('primary_members', $m['_id'], [
                'status'      => 'expired',
                'role_status' => 'expired',
            ]);
            $user = DB::findOne('users', 'email', $m['email'] ?? '');
            if ($user) {
                DB::updateOne('users', $user['_id'], ['status' => 'inactive']);
            }
            echo "  [EXPIRED] {$m['member_id']} — {$m['fullname']}\n";
        } elseif (in_array($days, [30, 7, 1])) {
            // Send reminder
            try {
                send_renewal_reminder($m, $days, 'primary');
                $reminded++;
                echo "  [REMINDER] {$m['member_id']} — {$days} days left\n";
            } catch (Exception $e) {
                echo "  [EMAIL ERROR] {$m['member_id']}: {$e->getMessage()}\n";
            }
        }
    }
    echo "  Primary: checked $checked, reminded $reminded\n";
}

// ── Check Student Members (1-year validity) ──────────────────
function checkStudentRenewals(): void {
    $students = DB::findAll('student_members');
    $checked = 0;
    $reminded = 0;

    foreach ($students as $s) {
        if (($s['status'] ?? '') !== 'approved') continue;
        if (empty($s['approved_at'])) continue;

        $days = DB::daysUntilExpiry($s['approved_at'], 1);
        if ($days === null) continue;
        $checked++;

        if ($days <= 0) {
            DB::updateOne('student_members', $s['_id'], ['status' => 'expired']);
            $user = DB::findOne('users', 'email', $s['email'] ?? '');
            if ($user) {
                DB::updateOne('users', $user['_id'], ['status' => 'inactive']);
            }
            echo "  [EXPIRED] {$s['student_id']} — {$s['fullname']}\n";
        } elseif (in_array($days, [30, 7, 1])) {
            try {
                send_renewal_reminder($s, $days, 'student');
                $reminded++;
                echo "  [REMINDER] {$s['student_id']} — {$days} days left\n";
            } catch (Exception $e) {
                echo "  [EMAIL ERROR] {$s['student_id']}: {$e->getMessage()}\n";
            }
        }
    }
    echo "  Student: checked $checked, reminded $reminded\n";
}

// ── Execute ──────────────────────────────────────────────────
checkPrimaryRenewals();
checkStudentRenewals();

echo "[" . date('Y-m-d H:i:s') . "] Scheduler complete.\n";
