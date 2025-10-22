<?php
// --- Session & Access Control ---
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Bootstrap CSS -->

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  :root {
    --admin-red: #8B0000;
    --admin-red-dark: #a40000;
  }

  .sidebar {
    background-color: var(--admin-red);
    color: #fff;
  }

  .sidebar .offcanvas-header {
    border-bottom: 1px solid rgba(255,255,255,0.1);
  }

  .sidebar .nav-link {
    color: #f8f9fa !important;
    border-radius: 6px;
    margin-bottom: 0.4rem;
    transition: background-color 0.2s ease;
  }

  .sidebar .nav-link.active,
  .sidebar .nav-link:hover {
    background-color: var(--admin-red-dark);
  }


  .content { padding: 1.5rem; }

  /* Desktop layout: fixed sidebar */
  @media (min-width: 992px) {
    .offcanvas-lg.sidebar {
      visibility: visible !important;
      transform: none !important;
      position: fixed !important;
      top: 0;
      left: 0;
      width: 250px;
      height: 100vh;
      background-color: var(--admin-red);
      padding-top: 1rem;
    }

    .offcanvas-lg.sidebar .offcanvas-body {
      display: flex;
      flex-direction: column;
      height: calc(100vh - 56px);
      padding-top: 0.5rem;
    }

    .content {
      margin-left: 260px;
      padding: 2rem;
    }

    .navbar-admin-toggle { display: none; }
  }

  /* Mobile layout */
  @media (max-width: 991.98px) {
    .content { margin-left: 0; padding: 1rem; }
  }
</style>

<!-- Mobile Navbar Toggle -->

<nav class="navbar navbar-dark bg-danger sticky-top d-lg-none navbar-admin-toggle">
  <div class="container-fluid">
    <button class="btn btn-light text-danger fw-semibold" type="button"
      data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar">
      ☰ Menu
    </button>
    <span class="navbar-text text-white fw-bold">CheckIn Admin</span>
  </div>
</nav>

<!-- Sidebar -->

<div class="offcanvas-lg offcanvas-start sidebar text-bg-danger" tabindex="-1"
  id="adminSidebar" aria-labelledby="adminSidebarLabel">
  <div class="offcanvas-header">
    <h4 id="adminSidebarLabel" class="fw-bold mb-0">CheckIn Admin</h4>
    <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>

  <div class="offcanvas-body d-flex flex-column">
    <ul class="nav flex-column mb-3">
      <li><a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">🏠 Dashboard</a></li>
      <li><a href="manage_rooms.php" class="nav-link <?= $current_page == 'manage_rooms.php' ? 'active' : '' ?>">🛏 Manage Rooms</a></li>
      <li><a href="manage_bookings.php" class="nav-link <?= $current_page == 'manage_bookings.php' ? 'active' : '' ?>">📘 Manage Bookings</a></li>
      <li><a href="manage_users.php" class="nav-link <?= $current_page == 'manage_users.php' ? 'active' : '' ?>">👤 Manage Users</a></li>
      <li><a href="manage_reviews.php" class="nav-link <?= $current_page == 'manage_reviews.php' ? 'active' : '' ?>">💬 Manage Reviews</a></li>
      <li><a href="manage_roomtypes.php" class="nav-link <?= $current_page == 'manage_roomtypes.php' ? 'active' : '' ?>">🏨 Room Types</a></li>
    </ul>


<div class="mt-auto mb-3">
  <a href="../logout.php" class="btn logout-btn w-100 fw-semibold text-white">Logout</a>
</div>

  </div>
</div>

<!-- Bootstrap JS -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.getElementById('adminSidebar');
  const closeButtons = sidebar.querySelectorAll('[data-bs-dismiss="offcanvas"]');
  closeButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const bsOffcanvas = bootstrap.Offcanvas.getInstance(sidebar);
      if (bsOffcanvas) bsOffcanvas.hide();
    });
  });
});
</script>
