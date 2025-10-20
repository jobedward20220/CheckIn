<?php
session_start();
require_once "db_connect.php";
require_once "includes/csrf.php";
$msg = "";

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
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
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
      <p>Welcome to CheckIn — your modern hotel booking platform designed for comfort, style, and simplicity. Wherever you go, we make every stay feel like home.</p>
      <div class="social-icons">
        <i class="fab fa-facebook-f"></i>
        <i class="fab fa-twitter"></i>
        <i class="fab fa-instagram"></i>
        <i class="fab fa-linkedin-in"></i>
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
        <input name="email" type="email" class="form-control" placeholder="Email" required>
        <input name="password" type="password" class="form-control" placeholder="Password" required>

        <div class="remember-forgot">
          <div>
            <input type="checkbox" id="remember">
            <label for="remember">Remember me</label>
          </div>
          <a href="forgot_password.php">Forgot password?</a>
        </div>

        <button class="btn btn-login mt-2">Login</button>
        <p class="text-center mt-3">No account? <a href="register.php">Register here</a></p>
      </form>
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