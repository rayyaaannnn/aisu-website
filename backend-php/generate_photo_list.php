<?php
$db = new PDO('sqlite:backend-php/data/aisu.sqlite');
$stmt = $db->query("SELECT fullname, photo, member_id, level, designation, state FROM primary_members WHERE role_status='active' ORDER BY level, fullname");
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

$f = fopen('backend-php/data/photo_filenames.csv', 'w');
fwrite($f, "\xEF\xBB\xBF");
fputcsv($f, ['Sl No','Member ID','Name','Level','Designation','State','Current SVG','NAME YOUR PHOTO AS THIS']);

$i = 0;
foreach ($members as $m) {
    $i++;
    $newJpg = str_replace('.svg', '.jpg', $m['photo']);
    $name = ucwords(strtolower($m['fullname']));
    fputcsv($f, [$i, $m['member_id'], $name, $m['level'], $m['designation'], $m['state'], $m['photo'], $newJpg]);
}
fclose($f);
echo "Generated: $i members -> backend-php/data/photo_filenames.csv\n";
