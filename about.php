<?php
include __DIR__ . "/user_sidebar.php";
// Public About page placeholder
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>About | CheckIn</title>
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
      margin: 0;
      padding: 0;
    }
    
    .sidebar-toggle, .toggle-btn {
      display: none !important;
    }
    
    .main-content {
      margin-left: 90px;
      padding: 20px;
      transition: all 0.3s ease;
      min-height: 100vh;
    }
    
    .container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 0 15px;
    }
    
    .about-card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      padding: 40px;
      margin-top: 20px;
    }
    
    .page-title {
      color: var(--primary-color);
      font-weight: 700;
      margin-bottom: 10px;
      font-size: 2.5rem;
    }
    
    .page-subtitle {
      color: #666;
      font-size: 1.2rem;
      margin-bottom: 30px;
    }
    
    /* Button Styles */
    .btn-primary {
      background: var(--primary-color);
      border: none;
      height: 45px;
      font-weight: 600;
      color: white;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0 25px;
    }

    .btn-primary:hover {
      background: var(--primary-hover);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }

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
    }
    
    .btn-back:hover {
      background: var(--primary-hover);
      color: white;
      transform: translateY(-2px);
    }

    .feature-card {
      background: white;
      border-radius: 10px;
      padding: 25px;
      text-align: center;
      border: 2px solid var(--primary-light);
      transition: all 0.3s ease;
      height: 100%;
    }
    
    .feature-card:hover {
      border-color: var(--primary-color);
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(220, 53, 69, 0.1);
    }
    
    .feature-icon {
      font-size: 3rem;
      color: var(--primary-color);
      margin-bottom: 15px;
    }
    
    .feature-title {
      color: var(--primary-color);
      font-weight: 600;
      margin-bottom: 10px;
    }
    
    .stats-section {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
      color: white;
      padding: 40px;
      border-radius: 15px;
      margin: 40px 0;
    }
    
    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 5px;
    }
    
    .stat-label {
      font-size: 1rem;
      opacity: 0.9;
    }
    
    .team-member {
      text-align: center;
      padding: 20px;
    }
    
    .team-avatar {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background: var(--primary-light);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary-color);
      font-size: 2rem;
      margin: 0 auto 15px;
      border: 3px solid var(--primary-color);
    }
    
    .contact-info {
      background: var(--primary-light);
      border-left: 4px solid var(--primary-color);
      padding: 20px;
      border-radius: 8px;
      margin-top: 30px;
    }
    
    .section-title {
      color: var(--primary-color);
      font-weight: 600;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid var(--primary-light);
    }
    
    .highlight {
      color: var(--primary-color);
      font-weight: 600;
    }

    /* Header layout with back button */
    .header-wrapper {
      position: relative;
      margin-bottom: 30px;
    }

    .title-container {
      padding-right: 140px; /* Space for back button on desktop */
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
        padding: 15px;
        padding-top: 20px;
      }
      
      .container {
        padding: 0 10px;
      }
      
      .about-card {
        padding: 25px 20px;
        margin-top: 10px;
      }
      
      .page-title {
        font-size: 2rem;
        text-align: left;
      }
      
      .page-subtitle {
        font-size: 1.1rem;
        text-align: left;
        margin-bottom: 25px;
      }
      
      /* Header adjustments for mobile */
      .header-wrapper {
        position: relative;
        padding-top: 50px; /* Space for the back button */
      }
      
      .title-container {
        padding-right: 0;
      }
      
      .btn-back {
        position: absolute;
        top: 0;
        right: 0;
        margin-bottom: 0;
        padding: 8px 16px;
        font-size: 0.9rem;
      }
      
      /* Introduction section */
      .row.mb-5 .col-md-6 {
        margin-bottom: 30px;
      }
      
      .lead {
        font-size: 1.1rem;
      }
      
      /* Stats section */
      .stats-section {
        padding: 30px 20px;
        margin: 30px 0;
      }
      
      .stats-section .row .col-md-3 {
        margin-bottom: 25px;
      }
      
      .stat-number {
        font-size: 2rem;
      }
      
      .stat-label {
        font-size: 0.9rem;
      }
      
      /* Features section */
      .feature-card {
        padding: 20px 15px;
        margin-bottom: 20px;
      }
      
      .feature-icon {
        font-size: 2.5rem;
      }
      
      .feature-title {
        font-size: 1.2rem;
      }
      
      /* Team section */
      .team-member {
        padding: 15px;
        margin-bottom: 20px;
      }
      
      .team-avatar {
        width: 80px;
        height: 80px;
        font-size: 1.5rem;
      }
      
      /* Contact info */
      .contact-info {
        padding: 15px;
      }
      
      .contact-info .row .col-md-6 {
        margin-bottom: 15px;
      }
      
      /* Buttons */
      .btn-primary, .btn-outline-primary {
        width: 100%;
        margin-bottom: 10px;
        justify-content: center;
      }
      
      .text-center .btn {
        display: block;
        width: 100%;
        margin: 10px 0;
      }
      
      .section-title {
        font-size: 1.5rem;
        text-align: center;
      }
    }
    
    @media (max-width: 576px) {
      .btn-back {
        padding: 6px 12px;
        font-size: 0.8rem;
      }
      
      .page-title {
        font-size: 1.8rem;
      }
      
      .header-wrapper {
        padding-top: 45px;
      }
    }
    
    @media (max-width: 480px) {
      .main-content {
        padding: 10px;
        padding-top: 15px;
      }
      
      .about-card {
        padding: 20px 15px;
        border-radius: 10px;
      }
      
      .page-title {
        font-size: 1.6rem;
      }
      
      .page-subtitle {
        font-size: 1rem;
      }
      
      .btn-back {
        padding: 5px 10px;
        font-size: 0.75rem;
      }
      
      .header-wrapper {
        padding-top: 40px;
      }
      
      .stats-section {
        padding: 25px 15px;
      }
      
      .stat-number {
        font-size: 1.8rem;
      }
      
      .feature-icon {
        font-size: 2rem;
      }
      
      .feature-card {
        padding: 15px 10px;
      }
      
      .team-avatar {
        width: 70px;
        height: 70px;
        font-size: 1.3rem;
      }
      
      .contact-info {
        padding: 12px;
      }
      
      .section-title {
        font-size: 1.3rem;
      }
      
      /* Stack stats in single column on very small screens */
      @media (max-width: 350px) {
        .stats-section .row .col-md-3 {
          flex: 0 0 100%;
          max-width: 100%;
        }
        
        .btn-back {
          padding: 4px 8px;
          font-size: 0.7rem;
        }
        
        .header-wrapper {
          padding-top: 35px;
        }
      }
    }
    
    @media (min-width: 769px) {
      .main-content {
        margin-left: 90px;
      }
      
      .btn-back {
        position: absolute;
        top: 0;
        right: 0;
      }
    }
  </style>
</head>
<body class="bg-light">
  <div class="main-content">
    <div class="container mt-4">
      <!-- Header Section -->
      <div class="header-wrapper">
        <div class="title-container">
          <h1 class="page-title"><i class="fas fa-hotel me-2"></i>About CheckIn</h1>
          <p class="page-subtitle">Your trusted partner for comfortable and convenient motel bookings</p>
        </div>
        <a href="javascript:history.back()" class="btn-back">
          <i class="fas fa-arrow-left"></i>
          Back
        </a>
      </div>

      <!-- About Card -->
      <div class="about-card">
        <!-- Introduction -->
        <div class="row mb-5">
          <div class="col-md-6">
            <h2 class="section-title">Our Story</h2>
            <p class="lead">
              CheckIn was founded with a simple mission: to make motel booking <span class="highlight">seamless, secure, and satisfying</span> for every traveler.
            </p>
            <p>
              Since our inception, we've been dedicated to providing exceptional service and innovative solutions that transform the way people book accommodations. Our platform combines cutting-edge technology with personalized service to ensure every stay is memorable.
            </p>
          </div>
          <div class="col-md-6">
            <h2 class="section-title">Why Choose Us?</h2>
            <ul class="list-unstyled">
              <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Instant booking confirmation</li>
              <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Best price guarantee</li>
              <li class="mb-2"><i class="fas fa-check text-success me-2"></i> 24/7 customer support</li>
              <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Secure payment processing</li>
              <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Real-time availability</li>
              <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Mobile-friendly platform</li>
            </ul>
          </div>
        </div>

        <!-- Stats Section -->
        <div class="stats-section text-center">
          <div class="row">
            <div class="col-md-3 mb-4">
              <div class="stat-number">10,000+</div>
              <div class="stat-label">Happy Customers</div>
            </div>
            <div class="col-md-3 mb-4">
              <div class="stat-number">500+</div>
              <div class="stat-label">Rooms Available</div>
            </div>
            <div class="col-md-3 mb-4">
              <div class="stat-number">24/7</div>
              <div class="stat-label">Customer Support</div>
            </div>
            <div class="col-md-3 mb-4">
              <div class="stat-number">98%</div>
              <div class="stat-label">Satisfaction Rate</div>
            </div>
          </div>
        </div>

        <!-- Features Section -->
        <h2 class="section-title text-center mt-5">What We Offer</h2>
        <div class="row mb-5">
          <div class="col-md-4 mb-4">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="fas fa-bolt"></i>
              </div>
              <h4 class="feature-title">Instant Booking</h4>
              <p>Book your preferred room in seconds with our streamlined booking process and instant confirmation.</p>
            </div>
          </div>
          <div class="col-md-4 mb-4">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="fas fa-shield-alt"></i>
              </div>
              <h4 class="feature-title">Secure Payments</h4>
              <p>Your transactions are protected with bank-level security and multiple payment options including GCash.</p>
            </div>
          </div>
          <div class="col-md-4 mb-4">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="fas fa-star"></i>
              </div>
              <h4 class="feature-title">Verified Reviews</h4>
              <p>Make informed decisions with authentic reviews from guests who have actually stayed with us.</p>
            </div>
          </div>
        </div>

        <!-- Team Section -->
        <h2 class="section-title text-center">Our Commitment</h2>
        <div class="row mb-5">
          <div class="col-md-12">
            <div class="text-center">
              <p class="lead mb-4">
                We're committed to providing exceptional value through:
              </p>
              <div class="row">
                <div class="col-md-6">
                  <div class="team-member">
                    <div class="team-avatar">
                      <i class="fas fa-heart"></i>
                    </div>
                    <h5>Customer First</h5>
                    <p>Your satisfaction is our top priority. We listen, adapt, and improve based on your feedback.</p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="team-member">
                    <div class="team-avatar">
                      <i class="fas fa-rocket"></i>
                    </div>
                    <h5>Innovation</h5>
                    <p>Constantly evolving our platform to provide you with the best booking experience possible.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Contact Information -->
        <div class="contact-info">
          <h4 class="section-title mb-3"><i class="fas fa-headset me-2"></i>Need Help?</h4>
          <div class="row">
            <div class="col-md-6">
              <p><strong>Customer Support:</strong><br>
              Available 24/7 to assist with your booking needs</p>
              <p><strong>Email:</strong> support@checkin.com<br>
              <strong>Phone:</strong> +1 (555) 123-4567</p>
            </div>
            <div class="col-md-6">
              <p><strong>Business Hours:</strong><br>
              Monday - Sunday: 24/7 Operation</p>
              <p><strong>Emergency Contact:</strong><br>
              For urgent matters during your stay</p>
            </div>
          </div>
        </div>

        <!-- Call to Action -->
        <div class="text-center mt-5">
          <h3 class="mb-3">Ready to Book Your Stay?</h3>
          <p class="mb-4">Join thousands of satisfied customers who trust CheckIn for their accommodation needs.</p>
          <div class="d-grid gap-2 d-md-block">
            <a href="dashboard.php" class="btn btn-primary btn-lg me-md-3 mb-2">
              <i class="fas fa-calendar-plus me-2"></i>Book Now
            </a>
            <a href="browse_rooms.php" class="btn btn-outline-primary btn-lg mb-2">
              <i class="fas fa-search me-2"></i>Browse Rooms
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>