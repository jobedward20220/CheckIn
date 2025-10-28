<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}
?>

<!-- ✅ Retractable Sidebar (Red Theme, refined text size) -->
<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
/>

<!-- Toggle Button for Mobile -->
<button class="sidebar-toggle" id="sidebarToggle">
  <i class="fas fa-bars"></i>
</button>

<!-- Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="uc-sidebar" id="ucSidebar">
  <div class="uc-header">
    <h2><i class="fas fa-hotel"></i> CI</h2>
  </div>

  <ul class="uc-menu">
    <li>
      <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
      </a>
    </li>
    <li>
      <a href="browse_rooms.php" class="<?= basename($_SERVER['PHP_SELF']) == 'browse_rooms.php' ? 'active' : '' ?>">
        <i class="fas fa-door-open"></i>
        <span>Browse</span>
      </a>
    </li>
    <li>
      <a href="bookings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : '' ?>">
        <i class="fas fa-calendar-check"></i>
        <span>Bookings</span>
      </a>
    </li>
    <li>
      <a href="reviews.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : '' ?>">
        <i class="fas fa-star"></i>
        <span>Reviews</span>
      </a>
    </li>
    <li>
      <a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
        <i class="fas fa-cog"></i>
        <span>Settings</span>
      </a>
    </li>

    <!-- ✅ Logout at the bottom -->
    <li class="logout">
      <a href="logout.php">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
      </a>
    </li>
  </ul>
</aside>

<style>
  /* ==============================
     RETRACTABLE RED SIDEBAR
     ============================== */

  .uc-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 90px;
    background: linear-gradient(180deg, #dc3545 0%, #b71c1c 100%);
    color: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 15px;
    box-shadow: 3px 0 8px rgba(0, 0, 0, 0.15);
    z-index: 3000;
    transition: transform 0.3s ease-in-out;
  }

  .uc-header {
    width: 100%;
    text-align: center;
    margin-bottom: 15px;
  }

  .uc-header h2 {
    font-size: 1.1rem;
    color: #fff;
    font-weight: 700;
    margin: 0;
  }

  /* ✅ Menu fix to push logout down */
  .uc-menu {
    list-style: none;
    padding: 0;
    margin: 0;
    width: 100%;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  .uc-menu li {
    width: 100%;
  }

  .uc-menu a {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: rgba(255, 255, 255, 0.9);
    padding: 10px 0;
    font-size: 0.75rem;
    letter-spacing: 0.3px;
    transition: background 0.3s ease, color 0.3s ease;
  }

  .uc-menu a i {
    font-size: 1.2rem;
    margin-bottom: 3px;
  }

  .uc-menu a:hover,
  .uc-menu a.active {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
  }

  /* ✅ Logout stays at bottom */
  /* Adjust logout button position - not exactly at the bottom */
.uc-menu .logout {
    margin-top: auto;
    margin-bottom: 60px; /* Adds space from the bottom */
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}

/* Optional: Add some padding to the menu container */
.uc-menu {
    padding-bottom: 10px;
}
  /* Adjust page content */
  main,
  .content-wrapper,
  .container,
  .main-content {
    margin-left: 90px;
    transition: margin-left 0.3s ease;
  }

  /* ==============================
     MOBILE TOGGLE BUTTON & OVERLAY
     ============================== */
  .sidebar-toggle {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 4000;
    background: #dc3545;
    border: none;
    color: #fff;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    font-size: 1.2rem;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    cursor: pointer;
    transition: all 0.3s ease;
    opacity: 1;
    visibility: visible;
  }

  .sidebar-toggle.hidden {
    opacity: 0;
    visibility: hidden;
  }

  .sidebar-toggle:hover {
    background: #c82333;
    transform: scale(1.05);
  }

  .sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 2999;
    backdrop-filter: blur(2px);
  }

  .sidebar-overlay.active {
    display: block;
  }

  /* ==============================
     RESPONSIVE BEHAVIOR
     ============================== */
  @media (max-width: 768px) {
    .uc-sidebar {
      width: 90px;
      transform: translateX(-100%);
    }

    .uc-sidebar.active {
      transform: translateX(0);
    }

    .sidebar-toggle {
      display: block;
    }

    main,
    .content-wrapper,
    .container,
    .main-content {
      margin-left: 0 !important;
    }

    .uc-menu a span {
      display: block;
    }
  }

  @media (max-width: 480px) {
    .uc-sidebar {
      width: 85px;
    }

    .sidebar-toggle {
      width: 42px;
      height: 42px;
      font-size: 1.1rem;
    }

    .uc-menu a {
      padding: 12px 0;
      font-size: 0.7rem;
    }

    .uc-menu a i {
      font-size: 1.1rem;
    }
  }

  /* Smooth transitions for all interactive elements */
  .uc-sidebar,
  .sidebar-toggle,
  .sidebar-overlay,
  main,
  .content-wrapper,
  .container,
  .main-content {
    transition: all 0.3s ease;
  }
</style>

<script>
/*
  Lightweight, robust sidebar toggle:
  - Works if the toggle button is defined in sidebar include OR elsewhere (dashboard)
  - Uses event delegation so order of includes doesn't matter
  - Gracefully handles duplicate toggle buttons
*/
(function(){
  const sidebarId = 'ucSidebar';
  const overlayId = 'sidebarOverlay';
  const toggleSelector = '.sidebar-toggle, #sidebarToggle';

  function getSidebar(){ return document.getElementById(sidebarId); }
  function getOverlay(){ return document.getElementById(overlayId); }

  function setHamburgerVisibility(show){
    document.querySelectorAll('.sidebar-toggle').forEach(btn=>{
      btn.style.display = show ? '' : 'none';
    });
  }

  function openSidebar(){
    const s = getSidebar(), o = getOverlay();
    if(!s || !o) return;
    s.classList.add('active');
    o.classList.add('active');
    document.body.style.overflow = 'hidden';
    setHamburgerVisibility(false);
  }

  function closeSidebar(){
    const s = getSidebar(), o = getOverlay();
    if(!s || !o) return;
    s.classList.remove('active');
    o.classList.remove('active');
    document.body.style.overflow = '';
    // only show hamburger on small screens
    if (window.innerWidth <= 768) setHamburgerVisibility(true);
  }

  // Toggle when any matching button is clicked (delegated)
  document.addEventListener('click', function(e){
    const toggle = e.target.closest(toggleSelector);
    if(toggle){
      const s = getSidebar();
      if(!s) return;
      if (s.classList.contains('active')) closeSidebar(); else openSidebar();
      return;
    }
    // clicking overlay closes sidebar
    if (e.target.closest('#' + overlayId)){
      closeSidebar();
    }
  }, { passive: true });

  // Escape key closes
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeSidebar();
  });

  // Resize: ensure correct visibility/state
  let resizeTimer;
  window.addEventListener('resize', function(){
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function(){
      const s = getSidebar();
      if(!s) return;
      if (window.innerWidth > 768){
        // on larger screens hide mobile hamburger and ensure sidebar is visible (not forced mobile slide-in)
        setHamburgerVisibility(false);
        // remove mobile-specific overlay/active state
        s.classList.remove('active');
        getOverlay()?.classList.remove('active');
        document.body.style.overflow = '';
      } else {
        // small screens: show hamburger if sidebar closed
        if (!s.classList.contains('active')) setHamburgerVisibility(true);
      }
    }, 120);
  }, { passive: true });

  // initial setup after DOM ready
  function init(){
    const s = getSidebar();
    if (!s) return;
    if (window.innerWidth > 768) {
      setHamburgerVisibility(false);
      s.classList.remove('active');
      getOverlay()?.classList.remove('active');
    } else {
      // show hamburger on small screens if sidebar not open
      if (!s.classList.contains('active')) setHamburgerVisibility(true);
    }
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>