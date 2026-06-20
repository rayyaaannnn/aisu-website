<?php
// =============================================================
//  email_service.php — AISU Email Automation (PHPMailer via SMTP)
// =============================================================

require_once __DIR__ . '/config.php';

// Autoload PHPMailer from Composer
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email via SMTP using PHPMailer.
 * Falls back gracefully if SMTP_PASS is not configured.
 */
function send_email(array $toList, string $subject, string $htmlBody, array $ccList = []): void {
    if (!SMTP_PASS) {
        error_log("[EMAIL] SMTP_PASS not configured. Would send to: " . implode(', ', $toList) . " | Subject: $subject");
        return;
    }

    if (!class_exists(PHPMailer::class)) {
        error_log("[EMAIL] PHPMailer not loaded. Install via: composer require phpmailer/phpmailer");
        return;
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;                     // smtp.gmail.com
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;                     // aisu4india@gmail.com
        $mail->Password   = SMTP_PASS;                     // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS on port 587
        $mail->Port       = SMTP_PORT;                     // 587

        // Send timeout
        $mail->Timeout       = 15;
        $mail->SMTPKeepAlive = false;

        // From
        $mail->setFrom(SMTP_USER, FROM_NAME);

        // Reply-To (same as From)
        $mail->addReplyTo(SMTP_USER, FROM_NAME);

        // To
        foreach ($toList as $addr) {
            $mail->addAddress(trim($addr));
        }

        // CC
        foreach ($ccList as $addr) {
            $mail->addCC(trim($addr));
        }

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</tr>'], "\n", $htmlBody));

        $mail->send();
        error_log("[EMAIL] Sent: $subject → " . implode(', ', $toList));
    } catch (Exception $e) {
        error_log("[EMAIL] FAILED: $subject → " . implode(', ', $toList) . " | Error: " . $mail->ErrorInfo);
    }
}

/**
 * Base HTML email template.
 */
function base_template(string $title, string $content): string {
    $year = date('Y');
    return <<<HTML
<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:30px 10px;">
<table width="600" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
  <tr><td style="background:linear-gradient(135deg,#0d1b2a,#1a2f45);padding:30px;text-align:center;">
    <h1 style="color:#ff6f0f;margin:0;font-size:24px;letter-spacing:2px;">AISU4India</h1>
    <p style="color:rgba(255,255,255,0.7);margin:4px 0 0;font-size:13px;">All India Students Union</p>
  </td></tr>
  <tr><td style="padding:32px 36px;">
    <h2 style="color:#1a2f45;margin:0 0 20px;font-size:20px;">$title</h2>
    $content
  </td></tr>
  <tr><td style="background:#f8f9fa;padding:20px 36px;border-top:1px solid #e9ecef;text-align:center;">
    <p style="color:#6c757d;font-size:12px;margin:0;">
      &copy; $year All India Students Union (AISU) &mdash;
      Dubwaliya Yadavchapra, Chanpatia, West Champaran, Bihar &ndash; 845450<br>
      <a href="https://aisu4india.in" style="color:#ff6f0f;">aisu4india.in</a> |
      aisu4india@gmail.com | +91 80748 53717
    </p>
  </td></tr>
</table></td></tr></table></body></html>
HTML;
}

// ── Email Functions ─────────────────────────────────────────

function send_primary_application_received(array $member): void {
    $name = $member['fullname'] ?? 'Applicant';
    $ref  = strtoupper(substr($member['_id'] ?? '', 0, 8));
    $date = date('d F Y, h:i A');
    $content = <<<HTML
    <p style="color:#495057;">Dear <strong>$name</strong>,</p>
    <p>Thank you for applying for <strong>Primary Membership</strong> with the All India Students Union (AISU).</p>
    <div style="background:#fff8f0;border-left:4px solid #ff6f0f;padding:16px;border-radius:4px;margin:20px 0;">
      <strong>Application Reference:</strong> $ref<br>
      <strong>Submitted On:</strong> $date<br>
      <strong>Status:</strong> Pending Review
    </div>
    <p>Your application will be reviewed by the National Officers. Upon approval, your unique AISU Member ID and login credentials will be sent to this email address.</p>
    <p style="color:#6c757d;font-size:13px;">Please do not reply to this email. For queries, contact us at aisu4india@gmail.com</p>
HTML;
    send_email([$member['email']], 'Primary Membership Application Received — AISU', base_template('Application Received', $content));

    // Notify national team
    $email = $member['email'] ?? '';
    $mobile = $member['mobile'] ?? '';
    $state = $member['state'] ?? '';
    $institution = $member['institution'] ?? '';
    $cc_body = <<<HTML
    <p>A new <strong>Primary Membership</strong> application has been received.</p>
    <table style="border-collapse:collapse;width:100%;">
      <tr><td style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa;"><strong>Name</strong></td><td style="padding:8px;border:1px solid #dee2e6;">$name</td></tr>
      <tr><td style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa;"><strong>Email</strong></td><td style="padding:8px;border:1px solid #dee2e6;">$email</td></tr>
      <tr><td style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa;"><strong>Mobile</strong></td><td style="padding:8px;border:1px solid #dee2e6;">$mobile</td></tr>
      <tr><td style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa;"><strong>State</strong></td><td style="padding:8px;border:1px solid #dee2e6;">$state</td></tr>
      <tr><td style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa;"><strong>Institution</strong></td><td style="padding:8px;border:1px solid #dee2e6;">$institution</td></tr>
    </table>
    <p style="margin-top:20px;">Please log in to the Admin Portal to review and approve/reject this application.</p>
HTML;
    $nationals = [NATIONAL_EMAILS['president'], NATIONAL_EMAILS['vice_president'], NATIONAL_EMAILS['gen_secretary']];
    send_email($nationals, "New Primary Membership Application — $name", base_template('New Application Received', $cc_body));
}

function send_primary_approved(array $member, string $defaultPassword): void {
    $name = $member['fullname'] ?? '';
    $memberId = $member['member_id'] ?? '';
    $email = $member['email'] ?? '';
    $designation = $member['designation'] ?? 'Primary Member';
    $content = <<<HTML
    <p>Dear <strong>$name</strong>,</p>
    <p>Congratulations! Your <strong>Primary Membership</strong> application has been <span style="color:#28a745;font-weight:bold;">APPROVED</span>.</p>
    <div style="background:#f0fff4;border:2px solid #28a745;border-radius:8px;padding:24px;margin:20px 0;text-align:center;">
      <p style="font-size:13px;color:#6c757d;margin:0 0 8px;">Your AISU Member ID</p>
      <p style="font-size:28px;font-weight:bold;color:#ff6f0f;letter-spacing:3px;margin:0;">$memberId</p>
    </div>
    <p><strong>Login Credentials:</strong></p>
    <table style="border-collapse:collapse;width:100%;margin:16px 0;">
      <tr><td style="padding:8px 12px;background:#f8f9fa;border:1px solid #dee2e6;"><strong>Login URL</strong></td><td style="padding:8px 12px;border:1px solid #dee2e6;"><a href="https://aisu4india.in/login.html">aisu4india.in/login.html</a></td></tr>
      <tr><td style="padding:8px 12px;background:#f8f9fa;border:1px solid #dee2e6;"><strong>Email</strong></td><td style="padding:8px 12px;border:1px solid #dee2e6;">$email</td></tr>
      <tr><td style="padding:8px 12px;background:#f8f9fa;border:1px solid #dee2e6;"><strong>Password</strong></td><td style="padding:8px 12px;border:1px solid #dee2e6;">$defaultPassword</td></tr>
      <tr><td style="padding:8px 12px;background:#f8f9fa;border:1px solid #dee2e6;"><strong>Designation</strong></td><td style="padding:8px 12px;border:1px solid #dee2e6;">$designation</td></tr>
    </table>
    <p style="color:#dc3545;font-size:13px;"><strong>Important:</strong> Please change your password after first login. Your membership is valid for 3 years.</p>
HTML;
    send_email([$email], 'Primary Membership Approved — Welcome to AISU!', base_template('Membership Approved', $content));
}

function send_student_application_received(array $student): void {
    $name = $student['fullname'] ?? 'Student';
    $ref  = strtoupper(substr($student['_id'] ?? '', 0, 8));
    $content = <<<HTML
    <p>Dear <strong>$name</strong>,</p>
    <p>Thank you for applying for <strong>Student Membership</strong> with AISU.</p>
    <div style="background:#fff8f0;border-left:4px solid #ff6f0f;padding:16px;border-radius:4px;margin:20px 0;">
      <strong>Reference:</strong> $ref<br>
      <strong>Status:</strong> Under Review
    </div>
    <p>Upon approval, your unique Student ID and login credentials will be emailed to you.</p>
HTML;
    send_email([$student['email']], 'Student Membership Application Received — AISU', base_template('Application Received', $content));
}

function send_student_approved(array $student, string $defaultPassword): void {
    $name = $student['fullname'] ?? '';
    $studentId = $student['student_id'] ?? '';
    $email = $student['email'] ?? '';
    $content = <<<HTML
    <p>Dear <strong>$name</strong>,</p>
    <p>Your <strong>Student Membership</strong> has been <span style="color:#28a745;font-weight:bold;">APPROVED</span>!</p>
    <div style="background:#f0fff4;border:2px solid #28a745;border-radius:8px;padding:24px;margin:20px 0;text-align:center;">
      <p style="font-size:13px;color:#6c757d;margin:0 0 8px;">Your AISU Student ID</p>
      <p style="font-size:26px;font-weight:bold;color:#ff6f0f;letter-spacing:2px;margin:0;">$studentId</p>
    </div>
    <p>Login: <a href="https://aisu4india.in/login.html">aisu4india.in/login.html</a> &mdash; Email: $email &mdash; Password: $defaultPassword</p>
    <p>You have <strong>free access to all competitions for 1 year</strong> from today.</p>
HTML;
    send_email([$email], 'Student Membership Approved — AISU', base_template('Welcome to AISU!', $content));
}

function send_complaint_update(array $complaint, string $actionTaken, string $updaterName): void {
    if (!empty($complaint['is_anonymous'])) return;
    $cmpId = $complaint['complaint_id'] ?? '';
    $category = $complaint['category'] ?? '';
    $status = ucfirst($complaint['status'] ?? '');
    $content = <<<HTML
    <p>Dear Complainant,</p>
    <p>An update has been recorded for your complaint:</p>
    <div style="background:#f8f9fa;border-left:4px solid #ff6f0f;padding:16px;border-radius:4px;margin:20px 0;">
      <strong>Complaint ID:</strong> $cmpId<br>
      <strong>Category:</strong> $category<br>
      <strong>New Status:</strong> $status<br>
      <strong>Action Taken By:</strong> $updaterName<br>
      <strong>Details:</strong> $actionTaken
    </div>
HTML;
    send_email([$complaint['email']], "Complaint Update — $cmpId", base_template('Complaint Status Update', $content));
}

function send_complaint_disposed(array $complaint): void {
    if (!empty($complaint['is_anonymous'])) return;
    $cmpId = $complaint['complaint_id'] ?? '';
    $resolution = $complaint['resolution'] ?? 'Matter resolved.';
    $category = $complaint['category'] ?? '';
    $content = <<<HTML
    <p>Dear Complainant,</p>
    <p>Your complaint has been <span style="color:#28a745;font-weight:bold;">DISPOSED / RESOLVED</span>.</p>
    <div style="background:#f0fff4;border:1px solid #28a745;padding:16px;border-radius:4px;margin:20px 0;">
      <strong>Complaint ID:</strong> $cmpId<br>
      <strong>Resolution:</strong> $resolution<br>
      <strong>Category:</strong> $category
    </div>
    <p>Thank you for bringing this matter to AISU's attention.</p>
HTML;
    send_email([$complaint['email']], "Complaint Resolved — $cmpId", base_template('Complaint Disposed', $content));
}

function send_certificate_issued(string $email, string $name, string $certId, string $certType, string $event = ''): void {
    $content = <<<HTML
    <p>Dear <strong>$name</strong>,</p>
    <p>Your <strong>$certType</strong> certificate has been issued for <strong>$event</strong>.</p>
    <div style="background:#f0fff4;border:2px solid #28a745;border-radius:8px;padding:24px;margin:20px 0;text-align:center;">
      <p style="font-size:13px;color:#6c757d;margin:0 0 8px;">Certificate Number</p>
      <p style="font-size:20px;font-weight:bold;color:#ff6f0f;letter-spacing:2px;margin:0;">$certId</p>
    </div>
    <p>To verify this certificate, visit <a href="https://aisu4india.in/cert-verify.html">our verification page</a>.</p>
HTML;
    send_email([$email], "Certificate Issued — $certType", base_template('Your Certificate is Ready', $content));
}

function send_renewal_reminder(array $member, int $daysLeft, string $memberType = 'primary'): void {
    $name = $member['fullname'] ?? $member['name'] ?? 'Member';
    $validity = $memberType === 'primary' ? '3 years' : '1 year';
    $memberId = $member['member_id'] ?? $member['student_id'] ?? '';
    $type = ucfirst($memberType);
    $content = <<<HTML
    <p>Dear <strong>$name</strong>,</p>
    <p>Your AISU $type Membership expires in <strong>$daysLeft days</strong>.</p>
    <div style="background:#fff3cd;border-left:4px solid #ffc107;padding:16px;border-radius:4px;margin:20px 0;">
      <strong>Member ID:</strong> $memberId<br>
      <strong>Membership Type:</strong> $type<br>
      <strong>Validity Period:</strong> $validity<br>
      <strong>Action Required:</strong> Renew before expiry to avoid service interruption
    </div>
    <p>Please log in to your account to initiate the renewal process.</p>
HTML;
    send_email([$member['email']], "AISU Membership Renewal Due in $daysLeft Days", base_template('Renewal Reminder', $content));
}

/**
 * Notify user that their portal login account has been created.
 * Sent when admin approves a membership application.
 */
function send_account_created(string $email, string $name, string $memberId, string $defaultPassword, string $memberType = 'Primary'): void {
    $loginUrl = 'https://aisu4india.in/login.html';
    $content = <<<HTML
    <p>Dear <strong>$name</strong>,</p>
    <p>Great news! Your <strong>AISU Portal Account</strong> has been created successfully.</p>
    <div style="background:#f0fff4;border:2px solid #28a745;border-radius:8px;padding:24px;margin:20px 0;">
      <p style="font-size:13px;color:#6c757d;margin:0 0 12px;text-align:center;">Your Login Credentials</p>
      <table style="border-collapse:collapse;width:100%;">
        <tr><td style="padding:10px 14px;background:#f8f9fa;border:1px solid #dee2e6;width:35%;"><strong>Login URL</strong></td><td style="padding:10px 14px;border:1px solid #dee2e6;"><a href="$loginUrl" style="color:#ff6f0f;">$loginUrl</a></td></tr>
        <tr><td style="padding:10px 14px;background:#f8f9fa;border:1px solid #dee2e6;"><strong>Email / Username</strong></td><td style="padding:10px 14px;border:1px solid #dee2e6;">$email</td></tr>
        <tr><td style="padding:10px 14px;background:#f8f9fa;border:1px solid #dee2e6;"><strong>Temporary Password</strong></td><td style="padding:10px 14px;border:1px solid #dee2e6;"><code style="background:#fff3e0;padding:4px 10px;border-radius:4px;font-size:15px;font-weight:bold;">$defaultPassword</code></td></tr>
        <tr><td style="padding:10px 14px;background:#f8f9fa;border:1px solid #dee2e6;"><strong>Member ID</strong></td><td style="padding:10px 14px;border:1px solid #dee2e6;font-weight:bold;color:#ff6f0f;letter-spacing:1px;">$memberId</td></tr>
        <tr><td style="padding:10px 14px;background:#f8f9fa;border:1px solid #dee2e6;"><strong>Membership Type</strong></td><td style="padding:10px 14px;border:1px solid #dee2e6;">$memberType Member</td></tr>
      </table>
    </div>
    <div style="background:#fff3cd;border-left:4px solid #ffc107;padding:14px;border-radius:4px;margin:16px 0;">
      <strong>⚠️ Important:</strong> Please change your password immediately after your first login for security purposes.
    </div>
    <p>You can use the <strong>Forgot Password</strong> option on the login page if you need to reset your password at any time.</p>
    <p style="color:#6c757d;font-size:13px;">This is an automated notification. For support, contact aisu4india@gmail.com</p>
HTML;
    send_email([$email], 'Your AISU Portal Account Has Been Created', base_template('Account Created', $content));
}

/**
 * Send OTP for forgot password flow.
 */
function send_forgot_password_otp(string $email, string $name, string $otp): void {
    $content = <<<HTML
    <p>Dear <strong>$name</strong>,</p>
    <p>You requested a password reset for your AISU account. Use the following OTP to verify your identity:</p>
    <div style="background:#f8f9fa;border:2px solid #ff6f0f;border-radius:12px;padding:28px;margin:24px 0;text-align:center;">
      <p style="font-size:12px;color:#6c757d;margin:0 0 10px;text-transform:uppercase;letter-spacing:2px;">One-Time Password</p>
      <p style="font-size:36px;font-weight:bold;color:#ff6f0f;letter-spacing:10px;margin:0;font-family:monospace;">$otp</p>
    </div>
    <p>This OTP is valid for <strong>2 minutes</strong>. Do not share it with anyone.</p>
    <p style="color:#dc3545;font-size:13px;">If you did not request this reset, please ignore this email. Your account is safe.</p>
HTML;
    send_email([$email], 'AISU Password Reset OTP', base_template('Password Reset', $content));
}
