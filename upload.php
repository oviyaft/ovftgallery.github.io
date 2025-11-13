<?php require_once 'auth.php'; ?>
<?php
header('Content-Type: application/json');

$uploadDir = 'images/';
$allowed   = ['jpg','jpeg','png','gif','webp'];
$maxSize   = 10 * 1024 * 1024;           // 10 MB per file
$uploaded  = [];

// ------------------------------------------------------------------
// 1. Increase PHP limits (once per request)
ini_set('upload_max_filesize', '200M');   // total POST size
ini_set('post_max_size',       '200M');
ini_set('max_file_uploads',    '200');    // max files per upload
// ------------------------------------------------------------------

if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
if (!is_writable($uploadDir)) {
    echo json_encode(['success'=>false,'error'=>'Upload directory not writable']);
    exit;
}

if (empty($_FILES)) {
    echo json_encode(['success'=>false,'error'=>'No files received']);
    exit;
}

foreach ($_FILES as $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) continue;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) continue;
    if ($file['size'] > $maxSize || $file['size'] === 0) continue;

    $newName = uniqid('pin_') . '.' . $ext;
    $dest    = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $uploaded[] = $dest;               // <-- keep plain path (no ?t=…)
    }
}

echo json_encode([
    'success' => !empty($uploaded),
    'paths'   => $uploaded,
    'error'   => empty($uploaded) ? 'No valid images uploaded' : null
]);
?>