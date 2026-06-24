<?php
// =============================================================
//  AISU Website - Deployment Setup Script
//  Run once after uploading files to cPanel.
//  Usage: php deploy/setup.php
// =============================================================

echo "\n";
echo "  AISU Website - Setup Script\n";
echo "  ===========================\n\n";

// 1. PHP Version
$phpVer = phpversion();
echo "[1/5] PHP Version: $phpVer\n";
if (version_compare($phpVer, '8.0', '<')) {
    echo "  WARNING: PHP 8.0+ recommended\n";
} else {
    echo "  OK\n";
}

// 2. PHP Extensions
$required = ['pdo', 'pdo_sqlite', 'mbstring', 'openssl', 'json', 'fileinfo', 'gd'];
echo "[2/5] Checking PHP Extensions...\n";
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "  OK $ext\n";
    } else {
        echo "  MISSING $ext (contact hosting provider)\n";
    }
}

// 3. Directory Permissions
$baseDir = __DIR__ . '/../backend-php';
$dirs = [
    "$baseDir/data",
    "$baseDir/uploads",
    "$baseDir/uploads/govtid",
    "$baseDir/uploads/payment",
    "$baseDir/uploads/photo",
    "$baseDir/uploads/sign",
    "$baseDir/uploads/complaint",
    "$baseDir/uploads/cert_templates",
    "$baseDir/uploads/certificates",
    "$baseDir/uploads/resume",
    "$baseDir/uploads/submissions",
    "$baseDir/uploads/innovations",
    "$baseDir/uploads/press",
    "$baseDir/uploads/gallery",
];
echo "[3/5] Checking Directory Permissions...\n";
foreach ($dirs as $d) {
    if (!is_dir($d)) {
        @mkdir($d, 0755, true);
        echo "  Created: " . basename($d) . "\n";
    } elseif (is_writable($d)) {
        echo "  OK " . basename($d) . "\n";
    } else {
        echo "  NOT WRITABLE: " . basename($d) . " (set chmod 755)\n";
    }
}

// 4. Database
echo "[4/5] Checking Database...\n";
$dbPath = "$baseDir/data/aisu.sqlite";
if (file_exists($dbPath)) {
    echo "  OK SQLite database exists\n";
    if (is_writable($dbPath)) {
        echo "  OK Database is writable\n";
    } else {
        echo "  NOT WRITABLE (set chmod 644)\n";
    }
} else {
    echo "  Database not found. Run: php backend-php/migrate.php\n";
}

// 5. Configuration
echo "[5/5] Checking Configuration...\n";
$envFile = "$baseDir/.env";
if (file_exists($envFile)) {
    echo "  OK .env file found\n";
} else {
    $example = "$baseDir/.env.example";
    if (file_exists($example)) {
        copy($example, $envFile);
        echo "  Copied .env.example to .env\n";
        echo "  IMPORTANT: Edit backend-php/.env with your real credentials!\n";
    } else {
        echo "  No .env or .env.example found\n";
    }
}

// Check .htaccess
if (file_exists(__DIR__ . '/../.htaccess')) {
    echo "\n  OK .htaccess found in root\n";
}

echo "\n  Setup complete!\n\n";
echo "  Next steps:\n";
echo "  1. Edit backend-php/.env with SMTP, Razorpay, and JWT credentials\n";
echo "  2. Run: php backend-php/migrate.php (creates database tables)\n";
echo "  3. Verify your domain loads correctly\n";
echo "  4. For quiz rooms: deploy quiz-server/ separately (Node.js)\n\n";