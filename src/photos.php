<?php
declare(strict_types=1);

function photoDirectory(): string
{
    return getenv('FOODSHARE_UPLOAD_DIR') ?: dirname(__DIR__) . '/storage/photos';
}

function storePhoto(array $file): ?string
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK || $file['size'] > 5000000 || !is_uploaded_file($file['tmp_name'])) {
        throw new DomainException('Choose a JPG or PNG under 5 MB and try again.');
    }
    $info = @getimagesize($file['tmp_name']);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($types[$mime]) || ($info['mime'] ?? '') !== $mime || $info[0] * $info[1] > 16000000) {
        throw new DomainException('Choose a JPG or PNG up to 16 megapixels.');
    }
    $dir = photoDirectory();
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Photo directory unavailable.');
    }
    $name = bin2hex(random_bytes(20)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Photo storage unavailable.');
    }
    return $name;
}
