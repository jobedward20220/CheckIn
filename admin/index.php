<?php
// --- Secure session and admin access ---
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "../db_connect.php";

// Restrict to admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}

// Include sidebar (renders offcanvas + navbar)
include "admin_sidebar.php";

// ─────────────────────────────────────────────
//  Real database queries for room statistics
// ─────────────────────────────────────────────
$available_rooms = $conn->query("SELECT COUNT(*) AS c FROM rooms WHERE status='available' AND is_visible=1")->fetch_assoc()['c'] ?? 0;
$reserved_rooms  = $conn->query("SELECT COUNT(DISTINCT r.id) AS c FROM rooms r JOIN bookings b ON b.room_id=r.id WHERE b.status='reserved' AND r.is_visible=1")->fetch_assoc()['c'] ?? 0;
$ongoing_rooms   = $conn->query("SELECT COUNT(DISTINCT r.id) AS c FROM bookings b JOIN rooms r ON b.room_id=r.id WHERE b.status IN ('confirmed','ongoing') AND r.is_visible=1")->fetch_assoc()['c'] ?? 0;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard | CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root {
      --sidebar-width: 250px;
      --content-max: 1200px;
    }

    body {
      background-color: #fff;
      min-height: 100vh;
    }

    .page-wrapper {
      padding: 1.25rem;
      transition: margin-left .2s ease;
    }

    /* Desktop layout with sidebar */
    @media (min-width: 992px) {
      .page-wrapper {
        margin-left: var(--sidebar-width);
      }
      .page-inner {
        max-width: var(--content-max);
        margin: 0 auto;
      }
    }

    /* Mobile layout (offcanvas active) */
    @media (max-width: 991.98px) {
      .page-wrapper {
        margin-left: 0;
      }
      .page-inner {
        padding-top: 0.25rem;
      }
    }

    /* Card styling */
    .stat-card {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.12);
    }

    .stat-icon {
      font-size: 2.5rem;
      margin-bottom: .4rem;
    }

    .text-break {
      word-break: break-word;
    }
  </style>
</head>

<body>
  <!-- Sidebar is included above -->
  <div class="page-wrapper">
    <div class="page-inner">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold text-danger mb-0">Admin Dashboard</h3>
      </div>

      <!-- Welcome Message -->
      <div class="text-center text-muted mb-4">
        <p class="mb-1">
          Welcome back,
          <strong class="text-danger"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong>!
        </p>
        <small>Use the sidebar to manage rooms, users, and bookings.</small>
      </div>

      <!-- Statistics Cards -->
      <div class="row g-3 g-md-4 mb-4">
        <div class="col-12 col-sm-6 col-lg-4">
          <div class="card stat-card text-center border-success shadow-sm">
            <div class="card-body">
              <div class="stat-icon text-success">🟢</div>
              <h5 class="card-title text-success">Available Rooms</h5>
              <h2 class="fw-bold"><?= $available_rooms ?></h2>
              <p class="text-muted mb-0">Rooms ready for booking</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
          <div class="card stat-card text-center border-warning shadow-sm">
            <div class="card-body">
              <div class="stat-icon text-warning">🟡</div>
              <h5 class="card-title text-warning">Reserved Rooms</h5>
              <h2 class="fw-bold"><?= $reserved_rooms ?></h2>
              <p class="text-muted mb-0">Awaiting guest check-in</p>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
          <div class="card stat-card text-center border-danger shadow-sm">
            <div class="card-body">
              <div class="stat-icon text-danger">🔴</div>
              <h5 class="card-title text-danger">Confirmed / Ongoing</h5>
              <h2 class="fw-bold"><?= $ongoing_rooms ?></h2>
              <p class="text-muted mb-0">Currently occupied rooms</p>
            </div>
          </div>
        </div>
      </div>

      <hr>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
