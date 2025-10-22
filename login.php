<?php
session_start();
require_once "db_connect.php";
require_once "includes/csrf.php";
// initialize
$msg = "";
$email = '';

// Auto-login via remember cookie (format: userId:token) if session not already active
if (empty($_SESSION['user_id']) && empty($_POST['email']) && !empty($_COOKIE['remember'])) {
  try {
    $parts = explode(':', $_COOKIE['remember'], 2);
    if (count($parts) === 2) {
      $cookieUserId = intval($parts[0]);
      $cookieToken = $parts[1];
      // ensure table exists before querying
      $tbl = $conn->query("SHOW TABLES LIKE 'remember_tokens'");
      if ($tbl && $tbl->num_rows) {
        $rt = $conn->prepare("SELECT user_id, token, expires_at FROM remember_tokens WHERE user_id=? AND token=? LIMIT 1");
        $rt->bind_param("is", $cookieUserId, $cookieToken);
        $rt->execute();
        $row = $rt->get_result()->fetch_assoc();
        if ($row && strtotime($row['expires_at']) > time()) {
          // token valid - restore session
          $u = $conn->prepare("SELECT u.id, r.name FROM users u JOIN roles r ON u.role_id=r.id WHERE u.id=? AND u.is_banned=0 LIMIT 1");
          $u->bind_param("i", $cookieUserId);
          $u->execute();
          $usr = $u->get_result()->fetch_assoc();
          if ($usr) {
            $_SESSION['user_id'] = $usr['id'];
            $_SESSION['role'] = $usr['name'];
            // redirect to dashboard immediately
            header('Location: ' . ($usr['name'] === 'admin' ? 'admin/index.php' : 'dashboard.php'));
            exit;
          }
        }
      }
    }
  } catch (Exception $e) {
    // ignore and continue to normal login
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $msg = "Invalid email.";
  else {
    $stmt = $conn->prepare("SELECT u.id, u.password, r.name, u.email_verified FROM users u JOIN roles r ON u.role_id=r.id WHERE u.email=? AND u.is_banned=0");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
      $user = $res->fetch_assoc();
      if (!empty($user['email_verified']) && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['name'];
        // Remember-me handling: create long-lived token if requested
        if (!empty($_POST['remember'])) {
          try {
            // ensure table exists
            $tbl = $conn->query("SHOW TABLES LIKE 'remember_tokens'");
            if ($tbl && $tbl->num_rows) {
              $token = bin2hex(random_bytes(32));
              $expires = date('Y-m-d H:i:s', time() + 60*60*24*30); // 30 days
              $ins = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())");
              $ins->bind_param("iss", $user['id'], $token, $expires);
              $ins->execute();
              if ($ins->affected_rows) setcookie('remember', $user['id'] . ':' . $token, time()+60*60*24*30, '/', '', false, true);
            }
          } catch (Exception $e) {
            // ignore - do not prevent login if remember fails
          }
        }
        if ($user['name'] === 'admin') header("Location: admin/index.php");
        else header("Location: dashboard.php");
        exit;
      } else {
        if (empty($user['email_verified'])) $msg = "Email not verified. Please check your inbox.";
        else $msg = "Wrong password.";
      }
    } else $msg = "User not found or banned.";
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login | CheckIn</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
  /* Disable browser's built-in password reveal icons */
  input[type="password"]::-webkit-credentials-auto-fill-button,
  input[type="password"]::-webkit-credentials-auto-fill-button:hover,
  input[type="password"]::-webkit-credentials-auto-fill-button:active {
    display: none !important;
    visibility: hidden !important;
    pointer-events: none !important;
    opacity: 0 !important;
  }

  input[type="password"]::-ms-reveal,
  input[type="password"]::-ms-clear {
    display: none !important;
  }

  input[type="password"]::-moz-textbox-edit-icons {
    display: none !important;
  }
  
  * { 
    box-sizing: border-box; 
    margin: 0; 
    padding: 0; 
  }
  
  body {
    font-family: 'Poppins', sans-serif;
    background: #fff;
    overflow-x: hidden;
    line-height: 1.6;
  }

  /* LOGIN SECTION */
  .login-wrapper {
    display: flex;
    flex-wrap: wrap;
    width: 100%;
    min-height: 100vh;
  }

  .login-left {
    flex: 1;
    min-width: 45%;
    background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)),
                url('https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80') center/cover no-repeat;
    color: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    padding: 80px;
  }

  .login-left h1 {
    font-weight: 700;
    font-size: 3rem;
    margin-bottom: 15px;
  }

  .login-left p {
    font-size: 16px;
    line-height: 1.7;
    max-width: 400px;
    opacity: 0.9;
  }

  .social-icons { 
    margin-top: 25px; 
  }
  
  .social-icons i {
    font-size: 20px;
    margin-right: 15px;
    cursor: pointer;
    transition: opacity 0.3s;
  }
  
  .social-icons i:hover { 
    opacity: 0.8; 
  }

  .login-right {
    flex: 1;
    min-width: 55%;
    background: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 60px 80px;
  }

  .login-right h3 {
    font-weight: 700;
    margin-bottom: 25px;
    color: #b21f2d;
  }

  .form-control {
    border-radius: 8px;
    margin-bottom: 15px;
    height: 45px;
    font-size: 15px;
    padding: 10px 15px;
    border: 1px solid #ddd;
  }

  .btn-login {
    background: #dc3545;
    border: none;
    height: 45px;
    font-weight: 600;
    color: white;
    transition: 0.3s;
    border-radius: 8px;
    width: 100%;
    cursor: pointer;
  }

  .btn-login:hover { 
    background: #b32030; 
  }

  .remember-forgot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 8px;
  }

  .remember-forgot a {
    color: #b21f2d;
    text-decoration: none;
  }

  .remember-forgot a:hover { 
    text-decoration: underline; 
  }

  .text-center a {
    color: #b21f2d;
    text-decoration: none;
    font-weight: 500;
  }
  
  .text-center a:hover { 
    text-decoration: underline; 
  }

  /* Password input group styling - UPDATED */
  .password-input-group {
    position: relative;
    margin-bottom: 15px;
  }

  .password-input-group .form-control {
    margin-bottom: 0;
    padding-right: 45px;
  }

  .password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 5px;
    transition: color 0.3s ease;
    z-index: 10;
  }

  .password-toggle:hover {
    color: #dc3545;
  }

  /* Remove built-in browser eye icons */
  input[type="password"]::-webkit-credentials-auto-fill-button,
  input[type="password"]::-webkit-caps-lock-indicator,
  input[type="password"]::-webkit-strong-password-auto-fill-button {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
  }

  /* For Firefox */
  input[type="password"] {
    -moz-appearance: none;
  }

  /* For all browsers - hide reveal password button */
  input[type="password"]::-ms-reveal,
  input[type="password"]::-ms-clear {
    display: none;
  }

  /* Ensure no browser styles interfere */
  .password-input-group input {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
  }

  /* INTRO SECTION - ALTERNATING LAYOUT */
  .intro-section { 
    width: 100%; 
  }
  
  .intro-page {
    display: flex;
    align-items: stretch;
    justify-content: center;
    min-height: 100vh;
    color: #333;
    background: #fff;
  }

  .intro-text {
    flex: 1;
    padding: 80px;
    background: #fff5f6;
    color: #b21f2d;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .intro-text h2 {
    font-weight: 700;
    font-size: 2.2rem;
    margin-bottom: 20px;
  }

  .intro-text p {
    line-height: 1.6;
    font-size: 1rem;
    max-width: 500px;
  }

  .intro-image {
    flex: 1;
    height: auto;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-color: #f8f9fa;
    position: relative;
  }

  .intro-image::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.1);
  }

  /* Alternating layout for desktop */
  .intro-page:nth-child(odd) {
    flex-direction: row; /* Text left, image right */
  }

  .intro-page:nth-child(even) {
    flex-direction: row-reverse; /* Text right, image left */
  }

  footer {
    background: #dc3545;
    color: #fff;
    text-align: center;
    padding: 20px;
    font-size: 14px;
  }

  /* RESPONSIVE STYLES */
  @media (max-width: 1200px) {
    .login-left, .login-right {
      padding: 60px;
    }
    
    .intro-text {
      padding: 60px;
    }
  }

  @media (max-width: 992px) {
    .login-wrapper {
      flex-direction: column;
      height: auto;
    }
    
    .login-left {
      min-height: 40vh;
      padding: 40px;
      text-align: center;
      align-items: center;
      width: 100%;
    }
    
    .login-left h1 { 
      font-size: 2.2rem; 
      margin-bottom: 10px;
    }
    
    .login-left p {
      font-size: 15px;
      max-width: 90%;
    }
    
    .login-right { 
      padding: 40px 30px; 
      width: 100%;
      min-height: 60vh;
    }

    /* INTRO SECTION - MOBILE FIX */
    .intro-page {
      flex-direction: column;
      min-height: auto;
    }
    
    .intro-text {
      padding: 50px 30px;
      text-align: center;
      align-items: center;
      order: 2; /* Text always comes after image on mobile */
    }
    
    .intro-text h2 {
      font-size: 1.8rem;
      margin-bottom: 15px;
    }
    
    .intro-text p {
      font-size: 1rem;
      max-width: 90%;
      margin: 0 auto;
    }
    
    .intro-image {
      width: 100%;
      height: 300px;
      background-position: center;
      background-size: cover;
      order: 1; /* Image always comes first on mobile */
    }

    /* Override alternating layout for mobile */
    .intro-page:nth-child(odd),
    .intro-page:nth-child(even) {
      flex-direction: column;
    }
    
    .intro-page:nth-child(odd) .intro-text,
    .intro-page:nth-child(even) .intro-text {
      order: 2;
    }
    
    .intro-page:nth-child(odd) .intro-image,
    .intro-page:nth-child(even) .intro-image {
      order: 1;
    }
  }

  @media (max-width: 768px) {
    .login-left {
      padding: 30px 20px;
      min-height: 35vh;
    }
    
    .login-left h1 {
      font-size: 1.8rem;
    }
    
    .login-left p {
      font-size: 14px;
      line-height: 1.6;
    }
    
    .login-right {
      padding: 30px 20px;
      min-height: 65vh;
    }
    
    .login-right h3 {
      font-size: 1.5rem;
      margin-bottom: 20px;
    }
    
    .form-control, .btn-login {
      height: 44px;
      font-size: 16px;
    }
    
    .remember-forgot {
      font-size: 13px;
    }

    .intro-text {
      padding: 40px 20px;
    }
    
    .intro-text h2 {
      font-size: 1.5rem;
    }
    
    .intro-text p {
      font-size: 0.95rem;
      line-height: 1.5;
    }
    
    .intro-image {
      height: 250px;
    }
  }

  @media (max-width: 576px) {
    .login-left {
      min-height: 30vh;
      padding: 25px 15px;
    }
    
    .login-left h1 {
      font-size: 1.6rem;
    }
    
    .login-left p {
      font-size: 13px;
      max-width: 100%;
    }
    
    .social-icons {
      margin-top: 15px;
    }
    
    .social-icons i {
      font-size: 18px;
      margin-right: 12px;
    }
    
    .login-right {
      padding: 25px 15px;
      min-height: 70vh;
    }
    
    .login-right h3 {
      font-size: 1.3rem;
    }
    
    .form-control, .btn-login {
      height: 42px;
      font-size: 15px;
    }
    
    .remember-forgot {
      flex-direction: column;
      align-items: flex-start;
      gap: 10px;
    }
    
    .intro-text {
      padding: 30px 15px;
    }
    
    .intro-text h2 {
      font-size: 1.3rem;
    }
    
    .intro-text p {
      font-size: 0.9rem;
    }
    
    .intro-image {
      height: 200px;
      min-height: 200px;
    }
  }

  @media (max-width: 400px) {
    .login-left h1 {
      font-size: 1.4rem;
    }
    
    .login-left p {
      font-size: 12px;
    }
    
    .login-right h3 {
      font-size: 1.2rem;
    }
    
    .form-control, .btn-login {
      height: 40px;
      font-size: 14px;
    }
    
    .intro-image {
      height: 180px;
      min-height: 180px;
    }
  }

  /* Touch-friendly improvements */
  .btn-login, .form-control, .remember-forgot a {
    -webkit-tap-highlight-color: transparent;
  }
  
  .btn-login:active {
    transform: scale(0.98);
  }

  /* Image loading states */
  .intro-image.loading {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
  }

  @keyframes loading {
    0% {
      background-position: 200% 0;
    }
    100% {
      background-position: -200% 0;
    }
  }
</style>

</head>
<body>

  <!-- LOGIN SECTION -->
  <div class="login-wrapper">
    <div class="login-left">
      <h1>CheckIn</h1>
      <p>Welcome to CheckIn — your modern motel booking platform designed for comfort, style, and simplicity. Wherever you go, we make every stay feel like home.</p>
      <div class="social-icons">
  <a href="https://www.facebook.com/kevenjohn.pama" target="_blank" rel="noopener noreferrer" class="text-decoration-none me-3 text-white" aria-label="Facebook">
    <i class="fab fa-facebook-f"></i>
  </a>
  <a href="https://x.com/alphashitsodog" target="_blank" rel="noopener noreferrer" class="text-decoration-none me-3 text-white" aria-label="Twitter">
    <i class="fab fa-twitter"></i>
  </a>
  <a href="https://www.instagram.com/abdulgulaman/" target="_blank" rel="noopener noreferrer" class="text-decoration-none me-3 text-white" aria-label="Instagram">
    <i class="fab fa-instagram"></i>
  </a>
  <a href="https://www.linkedin.com/in/kevin-john-pama-1a227a355/" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-white" aria-label="LinkedIn">
    <i class="fab fa-linkedin-in"></i>
  </a>
</div>
    </div>

    <div class="login-right">
      <h3>Sign In</h3>

      <?php if ($msg): ?>
        <div class="alert alert-danger"><?=$msg?></div>
      <?php endif; ?>

      <?php if (!empty($_GET['msg']) && $_GET['msg'] === 'password_updated'): ?>
        <div class="alert alert-success">Password updated. Please login.</div>
      <?php endif; ?>

      <form method="post">
        <?=csrf_input_field()?>
        <input name="email" id="emailInput" value="<?=htmlspecialchars($email)?>" type="email" class="form-control" placeholder="Email" required>
        
        <!-- Password with custom eye icon - UPDATED -->
        <div class="password-input-group">
          <input name="password" id="passwordInput" type="password" class="form-control" placeholder="Password" required>
          <button type="button" class="password-toggle" onclick="togglePassword('passwordInput')">
            <i class="fas fa-eye"></i>
          </button>
        </div>

        <div class="remember-forgot">
          <div>
            <input type="checkbox" id="remember" name="remember">
            <label for="remember">Remember me</label>
          </div>
          <a href="forgot_password.php">Forgot password?</a>
        </div>

        <button class="btn btn-login mt-2">Login</button>
        <p class="text-center mt-3">No account? <a href="register.php">Register here</a></p>
      </form>

      <script>
        function togglePassword(inputId) {
          const input = document.getElementById(inputId);
          const icon = input.parentElement.querySelector('.password-toggle i');
          
          if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
          } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
          }
        }

        // Restore last used email from localStorage or cookie if available
        (function(){
          try {
            const el = document.getElementById('emailInput');
            if (!el.value) {
              // prefer cookie
              const match = document.cookie.match(/(?:^|; )checkin_last_email=([^;]+)/);
              if (match) el.value = decodeURIComponent(match[1]);
              else {
                const saved = localStorage.getItem('checkin_last_email');
                if (saved) el.value = saved;
              }
            }
            el.addEventListener('change', function(){ 
              localStorage.setItem('checkin_last_email', this.value); 
              document.cookie = 'checkin_last_email=' + encodeURIComponent(this.value) + '; path=/; max-age=' + (60*60*24*365); 
            });
          } catch (e) {}
        })();

        // Additional script to ensure built-in icons stay hidden
        document.addEventListener('DOMContentLoaded', function() {
          const passwordInputs = document.querySelectorAll('input[type="password"]');
          passwordInputs.forEach(input => {
            // Remove any browser-specific attributes that might show icons
            input.setAttribute('autocomplete', 'current-password');
            input.setAttribute('aria-autocomplete', 'list');
          });
        });
      </script>
    </div>
  </div>

  <!-- INTRO SECTION WITH ALTERNATING LAYOUT -->
  <div class="intro-section">
    <!-- 1st intro: Text left, Image right -->
    <div class="intro-page">
      <div class="intro-text">
        <h2>Effortless Booking, Anytime</h2>
        <p>With CheckIn, you can book your stay in seconds. Whether you're planning a weekend getaway or a last-minute trip, our platform ensures a smooth, fast, and secure reservation experience.</p>
      </div>
      <div class="intro-image" style="background-image: url('https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80');"></div>
    </div>

    <!-- 2nd intro: Text right, Image left -->
    <div class="intro-page">
      <div class="intro-text">
        <h2>Stay in Style</h2>
        <p>Each motel on CheckIn is carefully selected to meet our standards of comfort, cleanliness, and quality service — giving you confidence in every stay, wherever you check in.</p>
      </div>
      <div class="intro-image" style="background-image: url('https://images.unsplash.com/photo-1582719508461-905c673771fd?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80');"></div>
    </div>

    <!-- 3rd intro: Text left, Image right -->
    <div class="intro-page">
      <div class="intro-text">
        <h2>Redefining Hospitality</h2>
        <p>CheckIn isn't just about booking rooms — it's about creating experiences. Enjoy personalized offers, quick support, and local insights that make your trips unforgettable.</p>
      </div>
      <div class="intro-image" style="background-image: url('https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80');"></div>
    </div>
  </div>

  <!-- FOOTER -->
  <footer>
    &copy; 2025 CheckIn. All Rights Reserved.
  </footer>

  <script>
    // Image loading optimization
    document.addEventListener('DOMContentLoaded', function() {
      const introImages = document.querySelectorAll('.intro-image');
      
      introImages.forEach(img => {
        // Add loading class initially
        img.classList.add('loading');
        
        // Create a new image to preload
        const bgImage = new Image();
        const bgUrl = img.style.backgroundImage.replace('url("', '').replace('")', '');
        
        bgImage.src = bgUrl;
        bgImage.onload = function() {
          // Remove loading class once image is loaded
          img.classList.remove('loading');
        };
        
        bgImage.onerror = function() {
          // If image fails to load, remove loading class and use fallback
          img.classList.remove('loading');
          console.warn('Failed to load image:', bgUrl);
        };
      });
    });
  </script>

</body>
</html>