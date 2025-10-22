<?php
session_start();
require_once "includes/auth_check.php";
include __DIR__ . "/user_sidebar.php";
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings | CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #dc3545;
      --primary-hover: #c82333;
      --primary-light: rgba(220, 53, 69, 0.1);
    }
    
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .container {
      max-width: 1100px;
      padding-left: 20px;
      padding-right: 20px;
    }
    
    .settings-card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      padding: 30px;
      margin-top: 20px;
    }
    
    .page-title {
      color: var(--primary-color);
      font-weight: 700;
      margin-bottom: 5px;
      font-size: 2.2rem;
    }
    
    .page-subtitle {
      color: #666;
      font-size: 1.2rem;
      margin-bottom: 30px;
    }
    
    /* Button Styles */
    .btn-back {
      background: var(--primary-color);
      border: none;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      margin-bottom: 30px;
    }
    
    .btn-back:hover {
      background: var(--primary-hover);
      color: white;
      transform: translateY(-2px);
    }
    
    .btn-settings {
      background: white;
      border: 2px solid var(--primary-color);
      color: var(--primary-color);
      padding: 18px 20px;
      border-radius: 10px;
      font-weight: 600;
      text-decoration: none;
      display: flex;
      align-items: flex-start;
      gap: 15px;
      transition: all 0.3s ease;
      margin-bottom: 15px;
      width: 100%;
      text-align: left;
    }
    
    .btn-settings:hover {
      background: var(--primary-color);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }
    
    .btn-settings i {
      font-size: 1.3rem;
      width: 28px;
      text-align: center;
      margin-top: 2px;
      flex-shrink: 0;
    }
    
    .settings-section {
      margin-bottom: 35px;
    }
    
    .section-title {
      color: var(--primary-color);
      font-weight: 600;
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 2px solid var(--primary-light);
      font-size: 1.4rem;
    }
    
    .info-text {
      background: var(--primary-light);
      border-left: 4px solid var(--primary-color);
      padding: 20px;
      border-radius: 8px;
      margin-top: 35px;
      font-size: 1.05rem;
    }
    
    /* Additional styles for better spacing in wider layout */
    .btn-settings div {
      flex: 1;
      min-width: 0; /* Allow text to wrap properly */
    }
    
    .btn-settings .fw-bold {
      font-size: 1.1rem;
      margin-bottom: 4px;
      line-height: 1.3;
    }
    
    .btn-settings small {
      font-size: 0.95rem;
      line-height: 1.4;
      display: block;
    }
    
    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
      .container {
        padding-left: 15px;
        padding-right: 15px;
        max-width: 100%;
      }
      
      .settings-card {
        padding: 20px 15px;
        margin-top: 10px;
        border-radius: 12px;
      }
      
      .page-title {
        font-size: 1.8rem;
        text-align: center;
        margin-bottom: 10px;
      }
      
      .page-subtitle {
        font-size: 1.1rem;
        text-align: center;
        margin-bottom: 25px;
      }
      
      .btn-settings {
        padding: 15px;
        gap: 12px;
        margin-bottom: 12px;
        align-items: center;
      }
      
      .btn-settings i {
        font-size: 1.2rem;
        width: 24px;
        margin-top: 0;
      }
      
      .btn-settings .fw-bold {
        font-size: 1rem;
      }
      
      .btn-settings small {
        font-size: 0.9rem;
      }
      
      .section-title {
        font-size: 1.3rem;
        text-align: center;
        margin-bottom: 15px;
      }
      
      .settings-section {
        margin-bottom: 25px;
      }
      
      .info-text {
        padding: 15px;
        margin-top: 25px;
        font-size: 1rem;
      }
      
      .d-flex.justify-content-between.align-items-start {
        flex-direction: column;
        text-align: center;
      }
    }
    
    @media (max-width: 576px) {
      .container {
        padding-left: 12px;
        padding-right: 12px;
      }
      
      .settings-card {
        padding: 15px 12px;
        border-radius: 10px;
      }
      
      .page-title {
        font-size: 1.6rem;
      }
      
      .page-subtitle {
        font-size: 1rem;
      }
      
      .btn-settings {
        padding: 12px;
        gap: 10px;
        flex-direction: column;
        text-align: center;
        align-items: center;
      }
      
      .btn-settings i {
        font-size: 1.4rem;
        margin-bottom: 5px;
      }
      
      .btn-settings div {
        text-align: center;
      }
      
      .btn-settings .fw-bold {
        font-size: 1rem;
        margin-bottom: 2px;
      }
      
      .btn-settings small {
        font-size: 0.85rem;
      }
      
      .section-title {
        font-size: 1.2rem;
        padding-bottom: 8px;
      }
      
      .info-text {
        padding: 12px;
        font-size: 0.95rem;
      }
    }
    
    @media (max-width: 400px) {
      .page-title {
        font-size: 1.4rem;
      }
      
      .page-subtitle {
        font-size: 0.95rem;
      }
      
      .btn-settings {
        padding: 10px;
      }
      
      .btn-settings .fw-bold {
        font-size: 0.95rem;
      }
      
      .btn-settings small {
        font-size: 0.8rem;
      }
    }
    
    /* Ensure proper spacing on very small devices */
    @media (max-width: 360px) {
      .container {
        padding-left: 10px;
        padding-right: 10px;
      }
      
      .settings-card {
        padding: 12px 10px;
      }
    }
  </style>
</head>
<body class="bg-light">
<div class="container mt-4">
  <!-- Header Section -->
  <div class="d-flex justify-content-between align-items-start mb-4">
    <div class="w-100">
      <h1 class="page-title"><i class="fas fa-cog me-2"></i>Settings</h1>
      <p class="page-subtitle">Manage your account preferences and profile settings</p>
    </div>
  </div>

  <!-- Settings Card -->
  <div class="settings-card">
    <!-- Profile Settings Section -->
    <div class="settings-section">
      <h3 class="section-title"><i class="fas fa-user me-2"></i>Profile Settings</h3>
      <a href="profile.php?return_to=settings.php" class="btn-settings">
        <i class="fas fa-user-edit"></i>
        <div>
          <div class="fw-bold">Edit Profile</div>
          <small class="opacity-75">Update your personal information and display name</small>
        </div>
      </a>
      
      <a href="change_password.php?return_to=settings.php" class="btn-settings">
        <i class="fas fa-key"></i>
        <div>
          <div class="fw-bold">Change Password</div>
          <small class="opacity-75">Update your account password for security</small>
        </div>
      </a>
    </div>

    <!-- Application Settings Section -->
    <div class="settings-section">
      <h3 class="section-title"><i class="fas fa-info-circle me-2"></i>About & Information</h3>
      <a href="about.php" class="btn-settings">
        <i class="fas fa-info-circle"></i>
        <div>
          <div class="fw-bold">About CheckIn</div>
          <small class="opacity-75">Learn more about our motel booking system</small>
        </div>
      </a>
    </div>

    <!-- Additional Settings (Placeholder for future features) -->
    <div class="settings-section">
      <h3 class="section-title"><i class="fas fa-bell me-2"></i>Preferences</h3>
      <div class="btn-settings" style="cursor: not-allowed; opacity: 0.6;">
        <i class="fas fa-bell"></i>
        <div>
          <div class="fw-bold">Notification Settings</div>
          <small class="opacity-75">Manage your email and push notifications (Coming Soon)</small>
        </div>
      </div>
      
      <div class="btn-settings" style="cursor: not-allowed; opacity: 0.6;">
        <i class="fas fa-moon"></i>
        <div>
          <div class="fw-bold">Appearance</div>
          <small class="opacity-75">Customize theme and display settings (Coming Soon)</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Information Text -->
  <div class="info-text">
    <p class="mb-0"><strong>Need help?</strong> Contact our support team if you need assistance with any settings or have questions about your account.</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>