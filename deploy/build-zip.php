<?php
// =============================================================
//  AISU Website - Build Deployment Zip
//  Run: php deploy/build-zip.php
//  Creates aisu-deploy.zip with only production files
// =============================================================

$excludePatterns = [
    '.git',
    '.gitignore',
    '.env',
    '.env.example',
    'node_modules',
    'deploy',
    'backend-php/vendor',
    'backend-php/.env',
    'backend-php/data/aisu.sqlite',
    'backend-php/php',
    'backend-php/php.ini',
    'backend-php/php.ini-production',
    'backend-php/composer.lock',
    'TurboVPN_setup.exe',
    'nul',
    'press_files',
    'designation_system_summary',
];

echo "Building AISU deployment zip...
";

if (!class_exists('ZipArchive')) {
    die("Error: ZipArchive extension is required.
");
}

$zip = new ZipArchive();
$filename = __DIR__ . '/../aisu-deploy.zip';
$rootDir = realpath(__DIR__ . '/..');

if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Error: Could not create zip file
");
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$count = 0;
foreach ($files as $file) {
    $localPath = substr($file->getPathname(), strlen($rootDir) + 1);
    $localPath = str_replace('', '/', $localPath);

    $skip = false;
    foreach ($excludePatterns as $pattern) {
        if (strpos($localPath, $pattern) === 0) {
            $skip = true;
            break;
        }
    }

    if ($skip) continue;

    $zip->addFile($file->getPathname(), $localPath);
    $count++;
}

$zip->close();

$sizeMB = round(filesize($filename) / 1024 / 1024, 2);
echo "Created aisu-deploy.zip with " . $count . " files (" . $sizeMB . " MB)
";
