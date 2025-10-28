<?php
session_start();
require_once "../db_connect.php";
require_once __DIR__ . '/../includes/csrf.php';

// Secure admin authentication before sidebar
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}

$room_id = intval($_GET['room_id'] ?? 0);
if ($room_id <= 0) die('Invalid room id');

$msg = '';

// Check if `is_primary` column exists
$hasIsPrimary = false;
$cols = $conn->query("SHOW COLUMNS FROM room_images LIKE 'is_primary'");
if ($cols && $cols->num_rows) $hasIsPrimary = true;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Make Primary
  if (!empty($_POST['make_primary_id'])) {
    $mid = intval($_POST['make_primary_id']);
    if ($hasIsPrimary) {
      $conn->begin_transaction();
      $conn->query("UPDATE room_images SET is_primary=0 WHERE room_id=" . intval($room_id));
      $conn->query("UPDATE room_images SET is_primary=1 WHERE id=" . $mid);
      $conn->commit();
    }
    header('Location: upload_room_images.php?room_id=' . $room_id);
    exit;
  }

  // Delete Image
  if (!empty($_POST['delete_id'])) {
    $did = intval($_POST['delete_id']);
    $sel = $conn->prepare("SELECT filepath, is_primary FROM room_images WHERE id=? AND room_id=?");
    $sel->bind_param("ii", $did, $room_id);
    $sel->execute();
    $row = $sel->get_result()->fetch_assoc();
    if ($row) {
      if (file_exists(__DIR__ . '/../' . $row['filepath'])) @unlink(__DIR__ . '/../' . $row['filepath']);
      $stmt = $conn->prepare("DELETE FROM room_images WHERE id=? AND room_id=?");
      $stmt->bind_param("ii", $did, $room_id);
      $stmt->execute();
      if ($hasIsPrimary && $row['is_primary']) {
        $conn->query("UPDATE room_images SET is_primary=1 WHERE room_id=" . intval($room_id) . ' ORDER BY id ASC LIMIT 1');
      }
    }
    header('Location: upload_room_images.php?room_id=' . $room_id);
    exit;
  }

  // Upload Images
  if (empty($_FILES['images'])) {
    $msg = 'No files uploaded.';
  } else {
    $uploadsDir = __DIR__ . '/../uploads/rooms/';
    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];

    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
      $name = $_FILES['images']['name'][$i];
      $tmp = $_FILES['images']['tmp_name'][$i];
      $err = $_FILES['images']['error'][$i];
      $size = $_FILES['images']['size'][$i];

      if ($err !== UPLOAD_ERR_OK) { $msg .= "Upload error for $name\n"; continue; }
      $mime = mime_content_type($tmp);
      if (!in_array($mime, $allowed)) { $msg .= "Skipped $name (invalid type)\n"; continue; }
      if ($size > 3 * 1024 * 1024) { $msg .= "Skipped $name (too large)\n"; continue; }

      $ext = pathinfo($name, PATHINFO_EXTENSION);
      $fname = 'room' . $room_id . '-' . time() . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
      $dest = $uploadsDir . $fname;
      if (!move_uploaded_file($tmp, $dest)) { $msg .= "Failed to move $name\n"; continue; }

      $filepath = 'uploads/rooms/' . $fname;
      $stmt = $conn->prepare("INSERT INTO room_images (room_id, filepath, alt_text) VALUES (?, ?, ?)");
      $alt = '';
      $stmt->bind_param("iss", $room_id, $filepath, $alt);
      $stmt->execute();

      if ($hasIsPrimary) {
        $checkPrimary = $conn->prepare("SELECT id FROM room_images WHERE room_id=? AND is_primary=1 LIMIT 1");
        $checkPrimary->bind_param("i", $room_id);
        $checkPrimary->execute();
        $checkPrimary->store_result();
        if ($checkPrimary->num_rows === 0) {
          $lastId = $conn->insert_id;
          $conn->query("UPDATE room_images SET is_primary=1 WHERE id=" . intval($lastId));
        }
      }
    }

    if ($msg === '') $msg = 'Upload complete.';
  }
}

// Fetch room info
$stmt = $conn->prepare("SELECT r.room_number, t.name AS type FROM rooms r JOIN room_types t ON r.room_type_id=t.id WHERE r.id=?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
if (!$room) die('Room not found');

// Fetch existing images
$imgs = $conn->prepare("SELECT * FROM room_images WHERE room_id=? ORDER BY id ASC");
$imgs->bind_param("i", $room_id);
$imgs->execute();
$imgsRes = $imgs->get_result();

include "admin_sidebar.php";
?>

<!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Upload Room Images | Admin - CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; color: #333; }
    .main-content { margin-left: 250px; padding: 20px; }
    @media (max-width: 991.98px) { .main-content { margin-left: 0; } }
    .card { border-radius: 10px; }
    .card-header { border-top-left-radius: 10px !important; border-top-right-radius: 10px !important; }
    .img-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .img-card:hover { transform: translateY(-4px); box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15); }
    .alert-info { white-space: pre-wrap; }
  </style>
</head>
<body>
  <div class="main-content">
    <div class="container-fluid py-4">

```
  <!-- Header -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
    <h3 class="fw-bold text-danger mb-3 mb-md-0">
      🖼️ Upload Images for Room <?= htmlspecialchars($room['room_number']) ?>
      <small class="text-muted">(<?= htmlspecialchars($room['type']) ?>)</small>
    </h3>
    <a href="manage_rooms.php" class="btn btn-outline-danger fw-semibold">← Back</a>
  </div>

  <!-- Messages -->
  <?php if ($msg): ?>
    <div class="alert alert-info"><?= nl2br(htmlspecialchars($msg)) ?></div>
  <?php endif; ?>

  <!-- Upload Form -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-danger text-white fw-bold">Upload New Images</div>
    <div class="card-body bg-white">
      <form method="post" enctype="multipart/form-data">
        <?= csrf_input_field() ?>
        <div class="mb-3">
          <label class="form-label fw-semibold text-danger">Select images (JPG, PNG, WEBP) — max 3MB each</label>
          <input type="file" name="images[]" multiple accept="image/*" class="form-control border-danger">
        </div>
        <button class="btn btn-danger fw-semibold px-4">⬆️ Upload</button>
      </form>
    </div>
  </div>

  <!-- Existing Images -->
  <h5 class="fw-bold text-danger mb-3">📂 Existing Images</h5>
  <div class="row">
    <?php while ($im = $imgsRes->fetch_assoc()): ?>
      <div class="col-6 col-sm-4 col-md-3 mb-4">
        <div class="card border-0 shadow-sm img-card">
         <img src="<?= htmlspecialchars('../'.$im['filepath']) ?>"
     data-src="<?= htmlspecialchars('../'.$im['filepath']) ?>"
     class="card-img-top click-enlarge"
     style="cursor: zoom-in; height: 180px; object-fit: cover; border-bottom: 3px solid <?= $im['is_primary'] ? '#dc3545' : '#dee2e6' ?>;"
     alt="Room Image <?= intval($im['id']) ?>">
          <div class="card-body text-center">
            <?php if ($im['is_primary']): ?>
              <span class="badge bg-danger mb-2">Primary</span>
            <?php endif; ?>
            <div class="d-flex justify-content-center gap-2">
              <?php if (!$im['is_primary']): ?>
                <form method="post">
                  <?= csrf_input_field() ?>
                  <input type="hidden" name="make_primary_id" value="<?= intval($im['id']) ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit">Make Primary</button>
                </form>
              <?php endif; ?>
              <form method="post" onsubmit="return confirm('Delete image?');">
                <?= csrf_input_field() ?>
                <input type="hidden" name="delete_id" value="<?= intval($im['id']) ?>">
                <button class="btn btn-sm btn-outline-secondary" type="submit">Delete</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>

</div>
```

  </div>
</body>
<?php require_once __DIR__ . '/../includes/image_modal.php'; ?>
</html>
