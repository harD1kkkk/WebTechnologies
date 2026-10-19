<?php
function create_task_image(array $file): ?array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Не вдалося завантажити файл. Спробуйте ще раз.');
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Некоректний файл завантаження.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Максимальний розмір фото — 5 МБ.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $types = [
        'image/jpeg' => ['extension' => 'jpg', 'loader' => 'imagecreatefromjpeg'],
        'image/png' => ['extension' => 'png', 'loader' => 'imagecreatefrompng'],
        'image/webp' => ['extension' => 'webp', 'loader' => 'imagecreatefromwebp']
    ];

    if (!isset($types[$mime])) {
        throw new RuntimeException('Дозволено завантажувати лише JPG, PNG або WebP.');
    }

    if (!function_exists('imagecreatetruecolor') || !function_exists($types[$mime]['loader'])) {
        throw new RuntimeException('Для створення мініатюр увімкніть розширення GD у PHP/XAMPP.');
    }

    $dimensions = @getimagesize($file['tmp_name']);
    if (!$dimensions || $dimensions[0] > 4000 || $dimensions[1] > 4000) {
        throw new RuntimeException('Зображення пошкоджене або має надто великі розміри.');
    }

    $loader = $types[$mime]['loader'];
    $source = @$loader($file['tmp_name']);
    if (!$source) {
        throw new RuntimeException('Не вдалося прочитати зображення.');
    }

    $root = dirname(__DIR__);
    $originalDir = $root . '/uploads/tasks/original';
    $thumbDir = $root . '/uploads/tasks/thumbs';
    if (!is_dir($originalDir) && !mkdir($originalDir, 0775, true) && !is_dir($originalDir)) {
        imagedestroy($source);
        throw new RuntimeException('Не вдалося створити папку для фото.');
    }
    if (!is_dir($thumbDir) && !mkdir($thumbDir, 0775, true) && !is_dir($thumbDir)) {
        imagedestroy($source);
        throw new RuntimeException('Не вдалося створити папку для мініатюр.');
    }

    $filename = bin2hex(random_bytes(12));
    $extension = $types[$mime]['extension'];
    $originalRelative = 'uploads/tasks/original/' . $filename . '.' . $extension;
    $thumbRelative = 'uploads/tasks/thumbs/' . $filename . '.jpg';
    $originalPath = $root . '/' . $originalRelative;
    $thumbPath = $root . '/' . $thumbRelative;

    if (!move_uploaded_file($file['tmp_name'], $originalPath)) {
        imagedestroy($source);
        throw new RuntimeException('Не вдалося зберегти оригінал фотографії.');
    }

    $width = imagesx($source);
    $height = imagesy($source);
    $scale = min(350 / $width, 450 / $height, 1);
    $newWidth = max(1, (int)round($width * $scale));
    $newHeight = max(1, (int)round($height * $scale));

    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    $white = imagecolorallocate($thumb, 255, 255, 255);
    imagefill($thumb, 0, 0, $white);
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    $saved = imagejpeg($thumb, $thumbPath, 82);

    imagedestroy($source);
    imagedestroy($thumb);

    if (!$saved) {
        @unlink($originalPath);
        throw new RuntimeException('Не вдалося створити зменшену копію фотографії.');
    }

    return ['image_path' => $originalRelative, 'thumbnail_path' => $thumbRelative];
}

function delete_task_images(?string $imagePath, ?string $thumbnailPath): void
{
    $root = dirname(__DIR__);
    foreach ([$imagePath, $thumbnailPath] as $relativePath) {
        if (!$relativePath || !str_starts_with($relativePath, 'uploads/tasks/')) {
            continue;
        }
        $fullPath = $root . '/' . $relativePath;
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
