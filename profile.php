<?php
session_start();
require_once "includes/auth_check.php";
require_once "includes/csrf.php";
require_once "db_connect.php";
include __DIR__ . "/user_sidebar.php";
$user_id = $_SESSION['user_id'];
$msg = "";

// fetch user & profile
$stmt = $conn->prepare("SELECT u.first_name, u.middle_name, u.last_name, u.email, p.phone, p.address, p.avatar, p.display_name
  FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $phone = trim($_POST['phone'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $display_name = trim($_POST['display_name'] ?? '');
  $first = trim($_POST['first_name'] ?? '');
  $middle = trim($_POST['middle_name'] ?? '');
  $last = trim($_POST['last_name'] ?? '');

  // file upload handling
  $updated = false;
  if (!empty($_FILES['avatar']['name'])) {
    $allowed = ['image/jpeg','image/png','image/webp'];
    if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
      $msg = "File upload error.";
    } elseif (!in_array(mime_content_type($_FILES['avatar']['tmp_name']), $allowed)) {
      $msg = "Only JPG/PNG/WEBP allowed.";
    } elseif ($_FILES['avatar']['size'] > 2*1024*1024) {
      $msg = "Max file size 2MB.";
    } else {
      $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
      $targetDir = __DIR__ . "/uploads/avatars";
      if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
      $filename = 'avatar_'.$user_id.'_'.time().'.'.$ext;
      $dest = $targetDir.'/'.$filename;
      if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
          $avatarPath = 'uploads/avatars/'.$filename;
          $u = $conn->prepare("UPDATE profiles SET phone=?, address=?, avatar=?, display_name=? WHERE user_id=?");
          $u->bind_param("ssssi", $phone, $address, $avatarPath, $display_name, $user_id);
          $u->execute();
          // update names on users table
          $uu = $conn->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=? WHERE id=?");
          $uu->bind_param("sssi", $first, $middle, $last, $user_id); $uu->execute();
        $updated = true;
        $msg = "Profile updated successfully!";
      } else $msg = "Failed to save uploaded file.";
    }
  } else {
    // update without avatar, but only if changed
    if ($phone !== ($user['phone'] ?? '') || $address !== ($user['address'] ?? '') || $display_name !== ($user['display_name'] ?? '') || $first !== ($user['first_name'] ?? '') || $middle !== ($user['middle_name'] ?? '') || $last !== ($user['last_name'] ?? '')) {
      $u = $conn->prepare("UPDATE profiles SET phone=?, address=?, display_name=? WHERE user_id=?");
      $u->bind_param("sssi", $phone, $address, $display_name, $user_id);
      $u->execute();
      // update names in users table
      $uu = $conn->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=? WHERE id=?");
      $uu->bind_param("sssi", $first, $middle, $last, $user_id); $uu->execute();
      $updated = true;
      $msg = "Profile updated successfully!";
    } else {
      $msg = "No changes detected.";
    }
  }
  // refresh user data
  if ($updated) {
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profile | CheckIn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
  /* === Responsive Profile Styles === */
  :root{
    --primary-color: #dc3545;
    --primary-hover: #c82333;
    --primary-light: rgba(220,53,69,0.08);
  }

  body{
    background-color:#f8f9fa;
    font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin:0;
    -webkit-font-smoothing:antialiased;
  }

  /* container offset for desktop when sidebar is present */
  .container{
    max-width:1400px;
    margin-left:200px;
    padding:20px;
    box-sizing:border-box;
  }
.sidebar-toggle, .toggle-btn {
    display: none !important;
}
  .profile-card{
    background:#fff;
    border-radius:12px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
    padding:24px;
    margin-top:12px;
  }

  .page-title{ color:var(--primary-color); font-weight:700; margin-bottom:6px; }
  .page-subtitle{ color:#666; margin-bottom:16px; }

  .btn-primary{
    background:var(--primary-color);
    border:none;
    height:44px;
    font-weight:600;
    color:#fff;
    transition:all .18s;
    padding:0 18px;
  }
  .btn-primary:hover{ background:var(--primary-hover); transform:translateY(-2px); }
  .btn-secondary{ 
    background:#6c757d; 
    border:none; 
    color:#fff; 
    height:44px; 
    padding:0 18px;
    display: flex;
    align-items: center;
    justify-content: center;
}

  /* Small back button in top right */
  .btn-back{ 
    background:var(--primary-color); 
    color:#fff; 
    padding:6px 12px; 
    border-radius:6px; 
    display:inline-flex; 
    align-items:center;
    gap:6px;
    font-size:0.8rem;
    font-weight:500;
    text-decoration:none;
    transition:all .18s;
    position:absolute;
    top:20px;
    right:20px;
  }
  .btn-back:hover{ background:var(--primary-hover); color:#fff; transform:translateY(-1px); }

  .form-control:focus, .form-select:focus{ border-color:var(--primary-color); box-shadow:0 0 0 .12rem rgba(220,53,69,0.12); }

  .alert-info{ border-left:4px solid var(--primary-color); background:var(--primary-light); }

  .avatar-container{ position:relative; display:inline-block; }
  .avatar-upload{ position:absolute; bottom:8px; right:8px; background:var(--primary-color); color:#fff; border-radius:50%; width:34px; height:34px; display:flex; align-items:center; justify-content:center; cursor:pointer; }
  .avatar-upload:hover{ transform:scale(1.05); background:var(--primary-hover); }

  .avatar-preview, .avatar-placeholder{ width:140px; height:140px; border-radius:50%; object-fit:cover; border:3px solid var(--primary-color); display:inline-block; }
  .avatar-placeholder{ display:flex; align-items:center; justify-content:center; font-size:42px; background:var(--primary-light); color:var(--primary-color); }

  .form-label{ font-weight:600; color:#333; margin-bottom:6px; }
  .section-divider{ border-top:2px solid rgba(0,0,0,0.03); margin:20px 0; }

  /* Header container with relative positioning for absolute back button */
  .header-container {
    position: relative;
    padding-right: 120px; /* Space for the back button */
    margin-bottom: 25px;
  }

  /* ===========================
     Responsive adjustments
     =========================== */
  @media (max-width: 991.98px){
    .container{ margin-left:140px; padding:18px; }
    .avatar-preview, .avatar-placeholder{ width:120px; height:120px; }
  }

  @media (max-width: 767.98px){
    /* remove left offset on small screens (sidebar collapses) */
    .container{ margin-left:0; padding:12px; }
    .profile-card{ padding:16px; }
    .avatar-preview, .avatar-placeholder{ width:110px; height:110px; }
    .btn-primary, .btn-secondary {
    display: flex;
    align-items: center;
    justify-content: center;
}
    .d-flex.gap-2.mt-4{ flex-direction:column; gap:10px; }
    
    /* Mobile header adjustments */
    .header-container {
      padding-right: 100px;
      margin-bottom: 20px;
    }
    .btn-back {
      top: 15px;
      right: 15px;
      padding: 5px 10px;
      font-size: 0.75rem;
    }
  }

  @media (max-width: 480px){
    .avatar-preview, .avatar-placeholder{ width:96px; height:96px; }
    .page-title{ font-size:1.1rem; }
    .page-subtitle{ font-size:0.95rem; }
    .col-md-3, .col-md-9{ flex:0 0 100%; max-width:100%; }
    
    /* Extra small screens */
    .header-container {
      padding-right: 90px;
    }
    .btn-back {
      top: 12px;
      right: 12px;
      padding: 4px 8px;
      font-size: 0.7rem;
    }
  }
   </style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="col-lg-9">
      <!-- Header Section with absolute positioned back button -->
      <div class="header-container">
        <div>
          <h1 class="page-title"><i class="fas fa-user-edit me-2"></i>Edit Profile</h1>
          <p class="page-subtitle">Update your personal information and profile settings</p>
        </div>
        <?php $ret = $_GET['return_to'] ?? 'dashboard.php'; ?>
        <a href="<?=htmlspecialchars($ret)?>" class="btn-back">
          <i class="fas fa-arrow-left"></i>
          Back
        </a>
      </div>

      <!-- Profile Card -->
      <div class="profile-card">
    <?php if($msg): ?>
      <div class="alert alert-info d-flex align-items-center">
        <i class="fas fa-info-circle me-2"></i>
        <?=$msg?>
      </div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data">
      <?=csrf_input_field()?>
      
      <!-- Avatar Section -->
      <div class="row mb-4">
        <div class="col-md-3 text-center">
          <div class="avatar-container">
            <?php if(!empty($user['avatar'])): ?>
              <img src="<?=htmlspecialchars($user['avatar'])?>" class="avatar-preview">
            <?php else: ?>
              <div class="avatar-placeholder">👤</div>
            <?php endif; ?>
            <label for="avatar-upload" class="avatar-upload" tabindex="0" role="button" aria-label="Upload avatar">
              <i class="fas fa-camera"></i>
            </label>
            <input type="file" name="avatar" id="avatar-upload" class="d-none" accept="image/*">
          </div>
          <small class="text-muted mt-2 d-block">Click camera icon to upload</small>
        </div>
        
        <div class="col-md-9">
          <!-- Email (Disabled) -->
          <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input disabled class="form-control" value="<?=htmlspecialchars($user['email'])?>">
            <small class="text-muted">Email cannot be changed</small>
          </div>
          
          <!-- Display Name -->
          <div class="mb-3">
            <label class="form-label">Display Name</label>
            <input name="display_name" class="form-control" value="<?=htmlspecialchars($user['display_name'] ?? '')?>" placeholder="How your name will appear to others">
            <small class="text-muted">Optional - leave blank to use your real name</small>
          </div>
        </div>
      </div>

      <div class="section-divider"></div>

      <!-- Personal Information -->
      <h5 class="mb-3" style="color: var(--primary-color);"><i class="fas fa-user me-2"></i>Personal Information</h5>
      <div class="row mb-3">
        <div class="col-md-4">
          <label class="form-label">First Name</label>
          <input name="first_name" class="form-control" value="<?=htmlspecialchars($user['first_name'])?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Middle Name</label>
          <input name="middle_name" class="form-control" value="<?=htmlspecialchars($user['middle_name'])?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Last Name</label>
          <input name="last_name" class="form-control" value="<?=htmlspecialchars($user['last_name'])?>" required>
        </div>
      </div>

      <!-- Contact Information -->
      <h5 class="mb-3 mt-4" style="color: var(--primary-color);"><i class="fas fa-phone me-2"></i>Contact Information</h5>
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Phone Number</label>
          <input name="phone" class="form-control" value="<?=htmlspecialchars($user['phone'])?>" placeholder="Your contact number">
        </div>
        <div class="col-md-6">
          <label class="form-label">Address</label>
          <input name="address" class="form-control" value="<?=htmlspecialchars($user['address'])?>" placeholder="Your current address">
        </div>
      </div>

      <!-- Avatar Upload note (use camera icon) -->
      <h5 class="mb-3 mt-4" style="color: var(--primary-color);"><i class="fas fa-image me-2"></i>Profile Picture</h5>
      <div class="mb-4">
        <small class="text-muted">Click the camera icon on your avatar to upload a new image. JPG, PNG, or WEBP. Max 2MB.</small>
      </div>

      <!-- Action Buttons -->
      <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary">
          <i class="fas fa-save me-2"></i>Save Changes
        </button>
        <?php $ret = $_GET['return_to'] ?? 'dashboard.php'; ?>
        <a href="<?=htmlspecialchars($ret)?>" class="btn btn-secondary">
          <i class="fas fa-times me-2"></i>Cancel
        </a>
      </div>
    </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Preview avatar before upload
  document.getElementById('avatar-upload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        const avatarPreview = document.querySelector('.avatar-preview') || document.querySelector('.avatar-placeholder');
        if (avatarPreview) {
          if (avatarPreview.classList.contains('avatar-placeholder')) {
            avatarPreview.outerHTML = `<img src="${e.target.result}" class="avatar-preview">`;
          } else {
            avatarPreview.src = e.target.result;
          }
        }
      }
      reader.readAsDataURL(file);
    }
  });
  // allow keyboard activation of the camera label (Enter / Space)
  const avatarLabel = document.querySelector('.avatar-upload');
  if (avatarLabel) {
    avatarLabel.addEventListener('keydown', function(ev) {
      if (ev.key === 'Enter' || ev.key === ' ' || ev.key === 'Spacebar') {
        ev.preventDefault();
        document.getElementById('avatar-upload').click();
      }
    });
  }
</script>
</body>
</html>