<?php
// --- Secure session and access control ---
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "../db_connect.php";

// Restrict to admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}

// Get room type details
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM room_types WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

if (!$room) {
  die("Room type not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name']);
  $desc = trim($_POST['description']);
  $rate = floatval($_POST['hourly_rate']);
  if ($rate < 0) $rate = 0;

  $update = $conn->prepare("UPDATE room_types SET name=?, description=?, hourly_rate=? WHERE id=?");
  $update->bind_param("ssdi", $name, $desc, $rate, $id);

  if ($update->execute()) {
    header("Location: manage_roomtypes.php");
    exit;
  } else {
    $error = "Failed to update. Please try again.";
  }
}

// Include admin sidebar after header-safe operations
include "admin_sidebar.php";
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Room Type | Admin - CheckIn</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  body {
    background-color: #f8f9fa;
  }

  .main-content {
    margin-left: 260px;
    padding: 20px;
  }

  @media (max-width: 991.98px) {
    .main-content {
      margin-left: 0;
      padding: 1rem;
    }
  }

  .card {
    border-radius: 10px;
  }

  .card-header {
    border-top-left-radius: 10px !important;
    border-top-right-radius: 10px !important;
  }

  .form-control:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
  }

  .btn-danger {
    border-radius: 6px;
  }

  .container-fluid h3 {
    display: flex;
    align-items: center;
    gap: 0.4rem;
  }
</style>
</head>

<body>
  <div class="main-content">
    <div class="container-fluid py-4">
      <!-- Page Header -->
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <h3 class="fw-bold text-danger mb-3 mb-md-0">🛏️ Edit Room Type</h3>
        <a href="manage_roomtypes.php" class="btn btn-outline-danger fw-semibold">← Back</a>
      </div>

      <!-- Error Message -->
      <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Card Form -->
      <div class="card shadow-sm border-0">
        <div class="card-header bg-danger text-white fw-bold">
          Room Type Details
        </div>

        <div class="card-body bg-white p-4">
          <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
              <label class="form-label fw-semibold text-danger">Room Name</label>
              <input type="text" name="name" class="form-control border-danger" 
                     value="<?= htmlspecialchars($room['name']) ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold text-danger">Description</label>
              <!-- Description is intentionally read-only in the admin UI per policy. The hidden input preserves the value so DB remains unchanged when the form posts. -->
              <div class="form-control border-danger" style="height:auto; white-space:pre-wrap; background:#f8f9fa; color:#333;"><?= htmlspecialchars($room['description']) ?></div>
              <input type="hidden" name="description" value="<?= htmlspecialchars($room['description']) ?>">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold text-danger">Hourly Rate (₱)</label>
              <input type="number" name="hourly_rate" step="0.01" 
                     class="form-control border-danger" 
                     value="<?= htmlspecialchars($room['hourly_rate']) ?>" required>
            </div>

            <div class="d-grid d-md-flex justify-content-md-end mt-4">
              <button type="submit" class="btn btn-danger fw-semibold w-100 w-md-auto px-4">
                💾 Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
