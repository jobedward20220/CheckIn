<?php
session_start();
require_once "includes/auth_check.php";
require_once "includes/csrf.php";
require_once "db_connect.php";
require_once __DIR__ . '/includes/name_helper.php';
$user_id = $_SESSION['user_id'];
$msg = "";

// Handle POST before any output (user_sidebar.php sends HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $return_to = $_POST['return_to'] ?? null; // optional URL to go back to
  // Either room_id (review for specific room) or 'hotel' for overall hotel review
  $room_id = isset($_POST['room_id']) && $_POST['room_id'] !== 'hotel' ? intval($_POST['room_id']) : null;
  $is_hotel = isset($_POST['room_id']) && $_POST['room_id'] === 'hotel';
  $rating = intval($_POST['rating'] ?? 0);
  $comment = trim($_POST['comment'] ?? '');
  if (!$is_hotel && !$room_id && ($rating < 1 || $rating > 5)) {
    $msg = "Invalid data.";
  } elseif ($rating < 1 || $rating > 5) {
    $msg = "Invalid rating.";
  } else {
    if ($is_hotel) {
  // check if user has any completed booking at all
  $chk = $conn->prepare("SELECT id FROM bookings WHERE user_id=? AND status='checked_out' LIMIT 1");
  $chk->bind_param("i", $user_id);
  $chk->execute(); 
  $chk->store_result();

  if ($chk->num_rows === 0) {
    // user has never checked out of any booking
    $msg = "You can only leave a motel review after completing a booking.";
  } else {
    // insert overall motel review
    $ins = $conn->prepare("INSERT INTO reviews (user_id, room_type_id, room_id, rating, comment, is_visible) VALUES (?, NULL, NULL, ?, ?, 1)");
    $ins->bind_param("iis", $user_id, $rating, $comment);
    $ins->execute();

    if ($ins->affected_rows) {
      // redirect to same page to show success alert
      header("Location: reviews.php?msg=review_submitted");
      exit;
    } else {
      // insert failed
      header("Location: reviews.php?err=cannot_submit_review");
      exit;
    }
  }
}
 else {
      // room-specific review: verify user completed that room
      $chk = $conn->prepare("SELECT id FROM bookings WHERE user_id=? AND room_id=? AND status='checked_out' LIMIT 1");
      $chk->bind_param("ii", $user_id, $room_id);
      $chk->execute(); $chk->store_result();
      if ($chk->num_rows === 0) {
  // 🚫 No completed booking: redirect back with error
  if ($return_to) {
    header('Location: ' . $return_to . '&err=cannot_submit_review');
    exit;
  }
  header('Location: room_details.php?id=' . intval($room_id) . '&err=cannot_submit_review');
  exit;
} 
      else {
        // determine room_type_id for the room
        $rt = $conn->prepare("SELECT room_type_id FROM rooms WHERE id=?");
        $rt->bind_param("i", $room_id); $rt->execute(); $roomRow = $rt->get_result()->fetch_assoc();
        $room_type_id = $roomRow['room_type_id'] ?? null;
        $ins = $conn->prepare("INSERT INTO reviews (user_id, room_type_id, room_id, rating, comment, is_visible) VALUES (?, ?, ?, ?, ?, 1)");
        $ins->bind_param("iiiis", $user_id, $room_type_id, $room_id, $rating, $comment);
        $ins->execute();
        if ($ins->affected_rows) {
  // ✅ Redirect back to room_details.php and show success message
  if ($return_to) {
    header('Location: '. $return_to .'&review=success');
    exit;
  }
  header('Location: room_details.php?id=' . intval($room_id) . '&review=success');
  exit;
} else {
  // ❌ Redirect back with failure
  if ($return_to) {
    header('Location: '. $return_to .'&err=cannot_submit_review');
    exit;
  }
  header('Location: room_details.php?id=' . intval($room_id) . '&err=cannot_submit_review');
  exit;
}
      }
    }
  }
}

// include sidebar after POST handling so header() can redirect safely
include __DIR__ . "/user_sidebar.php";
// fetch rooms that the user has completed (checked_out)
$roomsDone = $conn->prepare("
  SELECT DISTINCT r.id, r.room_number, t.name AS room_type 
  FROM bookings b
  JOIN rooms r ON b.room_id = r.id 
  JOIN room_types t ON r.room_type_id = t.id 
  WHERE b.user_id = ? 
    AND b.status = 'checked_out' 
    AND r.is_visible = 1
");
$roomsDone->bind_param("i", $user_id);
$roomsDone->execute();
$roomsDoneRes = $roomsDone->get_result();
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$sql = $isAdmin
  ? "SELECT r.*, t.name as room_type FROM reviews r LEFT JOIN room_types t ON r.room_type_id=t.id WHERE r.user_id=? ORDER BY r.created_at DESC"
  : "SELECT r.*, t.name as room_type FROM reviews r LEFT JOIN room_types t ON r.room_type_id=t.id WHERE r.user_id=? AND r.is_visible=1 ORDER BY r.created_at DESC";
$myReviews = $conn->prepare($sql);
$myReviews->bind_param("i", $user_id);
$myReviews->execute();
$myReviewsRes = $myReviews->get_result();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reviews | CheckIn</title>
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
    
    .page-title {
      color: var(--primary-color);
      font-weight: 700;
      margin-bottom: 5px;
      font-size: 2rem;
    }
    
    .page-subtitle {
      color: #666;
      font-size: 1.1rem;
      margin-bottom: 30px;
    }

    h3, h4 { 
      color: var(--primary-color); 
      margin-bottom: 1.5rem; 
      font-weight: 600;
    }
    
    .btn-primary { 
      background: var(--primary-color); 
      border: none; 
      transition: all 0.3s ease;
      font-weight: 600;
      padding: 10px 20px;
    }
    
    .btn-primary:hover { 
      background: var(--primary-hover); 
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }
    
    .review-rating { 
      color: #ffc107; 
      font-weight: bold; 
    }
    
    .table th { 
      background-color: var(--primary-color); 
      color: white; 
      border: none;
      padding: 12px 15px;
    }
    
    .table-hover tbody tr:hover { 
      background-color: var(--primary-light); 
    }
    
    .review-text.clamped { 
      display: -webkit-box; 
      -webkit-line-clamp: 3; 
      -webkit-box-orient: vertical; 
      overflow: hidden; 
    }
    
    .show-more { 
      cursor: pointer; 
      color: var(--primary-color); 
      font-weight: 600; 
      font-size: 0.9rem; 
    }
    
    /* Card styles */
    .card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      margin-bottom: 20px;
    }
    
    .card-header {
      background-color: var(--primary-color);
      color: white;
      border-radius: 12px 12px 0 0 !important;
      padding: 15px 20px;
      font-weight: 600;
    }
    
    /* Review item styles */
    .review-item {
      background: white;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 15px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      border-left: 4px solid var(--primary-color);
    }
    
    /* Mobile-specific styles */
    .mobile-card {
      background: white;
      border-radius: 10px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.08);
      padding: 18px;
      margin-bottom: 15px;
      border-left: 4px solid var(--primary-color);
    }
    
    .mobile-review-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 12px;
    }
    
    .mobile-review-type {
      background: var(--primary-color);
      color: white;
      padding: 5px 10px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    
    .mobile-review-comment {
      margin: 12px 0;
      line-height: 1.5;
      color: #444;
    }
    
    .mobile-review-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.85rem;
      color: #6c757d;
      margin-top: 10px;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    .alert {
      border-radius: 10px;
      padding: 15px;
    }
    
    .alert-success {
      background-color: rgba(40, 167, 69, 0.1);
      border-left: 4px solid #28a745;
      color: #155724;
    }
    
    .alert-danger {
      background-color: rgba(220, 53, 69, 0.1);
      border-left: 4px solid var(--primary-color);
      color: #721c24;
    }
    
    .alert-info {
      background-color: rgba(23, 162, 184, 0.1);
      border-left: 4px solid #17a2b8;
      color: #0c5460;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
        padding: 15px;
        padding-top: 70px;
      }
      
      .container {
        padding: 0 10px;
      }
      
      .page-title {
        font-size: 1.6rem;
        text-align: center;
      }
      
      .page-subtitle {
        font-size: 1rem;
        text-align: center;
        margin-bottom: 25px;
      }
      
      h3, h4 {
        font-size: 1.3rem;
        text-align: center;
      }
      
      .btn-primary {
        width: 100%;
        margin-bottom: 10px;
      }
      
      .review-item {
        padding: 15px;
      }
      
      .mobile-card {
        padding: 15px;
      }
      
      .table-responsive {
        font-size: 0.9rem;
      }
    }
    
    @media (max-width: 576px) {
      .main-content {
        padding: 10px;
        padding-top: 70px;
      }
      
      .page-title {
        font-size: 1.4rem;
      }
      
      .page-subtitle {
        font-size: 0.95rem;
      }
      
      h3, h4 {
        font-size: 1.2rem;
      }
      
      .mobile-review-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
      }
      
      .mobile-review-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
      }
      
      .form-control, .form-select {
        font-size: 16px; /* Prevents zoom on iOS */
      }
    }
    
    @media (max-width: 480px) {
      .page-title {
        font-size: 1.3rem;
      }
      
      .mobile-card {
        padding: 12px;
      }
      
      .mobile-review-comment {
        font-size: 0.9rem;
      }
    }
  </style>
</head>
<body>
  <div class="main-content">
    <div class="container">
      <!-- Header Section -->
      <div class="d-flex justify-content-between align-items-start mb-4">
        <div class="flex-grow-1">
          <h1 class="page-title"><i class="fas fa-star me-2"></i>Reviews</h1>
          <p class="page-subtitle">Share your experience and view your past reviews</p>
        </div>
      </div>

      <!-- Leave a Review Section -->
      <div class="card">
        <div class="card-header">
          <i class="fas fa-edit me-2"></i>Leave a Review
        </div>
        <div class="card-body">
          <?php if(!empty($_GET['msg']) && $_GET['msg']==='review_submitted'): ?>
            <div class="alert alert-success">
              <i class="fas fa-check-circle me-2"></i>Review submitted successfully!
            </div>
          <?php endif; ?>
          <?php if(!empty($_GET['err']) && $_GET['err']==='cannot_submit_review'): ?>
            <div class="alert alert-danger">
              <i class="fas fa-exclamation-circle me-2"></i>You cannot submit a review at this time.
            </div>
          <?php endif; ?>
          <?php if($msg): ?>
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i><?=$msg?>
            </div>
          <?php endif; ?>

          <form method="post">
            <?=csrf_input_field()?>
            <div class="row">
              <div class="col-12 col-md-6 mb-3">
                <label class="form-label fw-bold">What are you reviewing?</label>
                <?php $selRoom = $_POST['room_id'] ?? ''; ?>
                <select name="room_id" class="form-select" required>
                  <option value="">Choose...</option>
                  <option value="hotel" <?= $selRoom === 'hotel' ? 'selected' : '' ?>>Overall Motel Experience</option>
                  <?php while($rd = $roomsDoneRes->fetch_assoc()): ?>
                    <?php $val = intval($rd['id']); ?>
                    <option value="<?=$val?>" <?= (string)$val === (string)$selRoom ? 'selected' : '' ?>>
                      Room <?=htmlspecialchars($rd['room_number'])?> — <?=htmlspecialchars($rd['room_type'])?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="col-12 col-md-6 mb-3">
                <label class="form-label fw-bold">Rating</label>
                <?php $selRating = $_POST['rating'] ?? ''; ?>
                <select name="rating" class="form-select" required>
                  <option value="">Select rating...</option>
                  <?php for($i=5;$i>=1;$i--): ?>
                    <option value="<?=$i?>" <?= (string)$i === (string)$selRating ? 'selected' : '' ?>>
                      <?=str_repeat('★', $i)?><?=str_repeat('☆', 5-$i)?> - <?=$i?> Star<?=$i>1?'s':''?>
                    </option>
                  <?php endfor; ?>
                </select>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Your Review</label>
              <textarea name="comment" class="form-control" rows="4" placeholder="Share your experience..."><?=htmlspecialchars($_POST['comment'] ?? '')?></textarea>
            </div>
            <button class="btn btn-primary px-4 w-100 w-md-auto">
              <i class="fas fa-paper-plane me-2"></i>Submit Review
            </button>
          </form>
        </div>
      </div>

      <!-- Recent Hotel Reviews Section -->
      <div class="card">
        <div class="card-header">
          <i class="fas fa-hotel me-2"></i>Recent Overall Motel Reviews
        </div>
        <div class="card-body">
          <?php
            $hotelList = $conn->query("SELECT rv.*, u.first_name, u.last_name, p.display_name FROM reviews rv JOIN users u ON rv.user_id=u.id LEFT JOIN profiles p ON p.user_id=u.id WHERE rv.room_id IS NULL AND rv.is_visible=1 ORDER BY rv.created_at DESC LIMIT 10");
          ?>
          <?php if ($hotelList && $hotelList->num_rows): ?>
            <!-- Desktop view for hotel reviews -->
            <div class="d-none d-md-block">
              <?php while($hr=$hotelList->fetch_assoc()): ?>
                <div class="review-item">
                  <div class="d-flex justify-content-between">
                    <span class="fw-semibold text-danger">
                      <?=htmlspecialchars(!empty($hr['display_name']) ? $hr['display_name'] : ($hr['first_name'].' '.$hr['last_name']))?>
                    </span>
                    <span class="review-rating"><?=intval($hr['rating'])?>/5 ★</span>
                  </div>
                  <div class="review-text"><?=nl2br(htmlspecialchars($hr['comment']))?></div>
                  <small class="text-muted"><?=date('M j, Y', strtotime($hr['created_at']))?></small>
                </div>
              <?php endwhile; ?>
            </div>
            
            <!-- Mobile view for hotel reviews -->
            <div class="d-md-none">
              <?php 
                // Reset pointer for mobile view
                $hotelList->data_seek(0);
              ?>
              <?php while($hr=$hotelList->fetch_assoc()): ?>
                <div class="mobile-card">
                  <div class="mobile-review-header">
                    <span class="fw-semibold text-danger">
                      <?=htmlspecialchars(!empty($hr['display_name']) ? $hr['display_name'] : ($hr['first_name'].' '.$hr['last_name']))?>
                    </span>
                    <span class="review-rating"><?=intval($hr['rating'])?>/5 ★</span>
                  </div>
                  <div class="mobile-review-comment"><?=nl2br(htmlspecialchars($hr['comment']))?></div>
                  <div class="mobile-review-footer">
                    <small class="text-muted"><?=date('M j, Y', strtotime($hr['created_at']))?></small>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <div class="text-muted text-center py-4">
              <i class="fas fa-comment-slash fa-2x mb-3"></i>
              <p>No overall motel reviews yet.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- My Reviews Section -->
      <div class="card">
        <div class="card-header">
          <i class="fas fa-list me-2"></i>My Reviews
        </div>
        <div class="card-body">
          <!-- Desktop table view -->
          <div class="d-none d-md-block table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Rating</th>
                  <th>Comment</th>
                  <?php if ($isAdmin): ?><th>Visible</th><?php endif; ?>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php while($r=$myReviewsRes->fetch_assoc()): ?>
                <tr>
                  <td><span class="badge bg-danger"><?=htmlspecialchars($r['room_type'] ?? 'Hotel')?></span></td>
                  <td><span class="review-rating"><?=intval($r['rating'])?>/5 ★</span></td>
                  <td><?=nl2br(htmlspecialchars($r['comment']))?></td>
                  <?php if ($isAdmin): ?>
                    <td><span class="badge <?=$r['is_visible'] ? 'bg-success' : 'bg-secondary'?>"><?=$r['is_visible']? 'Yes':'No'?></span></td>
                  <?php endif; ?>
                  <td><small class="text-muted"><?=date('M j, Y', strtotime($r['created_at']))?></small></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
          
          <!-- Mobile card view for reviews -->
          <div class="d-md-none">
            <?php 
              // Reset pointer for mobile view
              $myReviewsRes->data_seek(0);
            ?>
            <?php while($r=$myReviewsRes->fetch_assoc()): ?>
              <div class="mobile-card">
                <div class="mobile-review-header">
                  <span class="mobile-review-type"><?=htmlspecialchars($r['room_type'] ?? 'Hotel')?></span>
                  <span class="review-rating"><?=intval($r['rating'])?>/5 ★</span>
                </div>
                <div class="mobile-review-comment"><?=nl2br(htmlspecialchars($r['comment']))?></div>
                <div class="mobile-review-footer">
                  <small class="text-muted"><?=date('M j, Y', strtotime($r['created_at']))?></small>
                  <?php if ($isAdmin): ?>
                    <span class="badge <?=$r['is_visible'] ? 'bg-success' : 'bg-secondary'?>"><?=$r['is_visible']? 'Visible':'Hidden'?></span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
          
          <?php if ($myReviewsRes->num_rows === 0): ?>
            <div class="text-muted text-center py-4">
              <i class="fas fa-star fa-2x mb-3"></i>
              <p>You haven't submitted any reviews yet.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>