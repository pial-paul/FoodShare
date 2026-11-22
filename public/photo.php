<?php
require_once __DIR__ . '/../src/web.php';
require_once __DIR__ . '/../src/photos.php';
$actor = requireUser();
$id = (int)field($_GET, 'id');
$item = rows("SELECT d.image FROM donations d JOIN users u ON u.id=d.donor_id WHERE d.id=? AND
    (?='admin' OR d.donor_id=? OR EXISTS (SELECT 1 FROM requests r WHERE r.donation_id=d.id AND r.receiver_id=?)
     OR (?='receiver' AND d.status='available' AND d.expiry_date>=? AND u.disabled_at IS NULL))",
    [$id, $actor['role'], $actor['id'], $actor['id'], $actor['role'], today()])[0] ?? [];
$name = $item['image'] ?? '';
// Original databases stored img/uploads/<filename>. Only serve direct files in private storage.
$name = preg_replace('~^img/uploads/~', '', $name);
if ($name === '' || strpbrk($name, "/\\:") !== false || $name[0] === '.') {
    http_response_code(404);
    exit;
}
$file = photoDirectory() . '/' . $name;
$mime = is_file($file) ? (new finfo(FILEINFO_MIME_TYPE))->file($file) : null;
if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
    http_response_code(404);
    exit;
}
header('Content-Type: ' . $mime);
readfile($file);
