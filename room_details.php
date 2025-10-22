<?php
session_start();
require_once "includes/auth_check.php";
require_once "db_connect.php";
require_once "includes/csrf.php";
include __DIR__ . "/user_sidebar.php";
$room_id = intval($_GET['id'] ?? 0);
if ($room_id <= 0) die('Invalid room id');
$stmt = $conn->prepare("SELECT r.*, t.name as type, t.hourly_rate, t.description AS type_description FROM rooms r JOIN room_types t ON r.room_type_id=t.id WHERE r.id=?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
if (!$room) die('Room not found');

// images
$imgs = $conn->prepare("SELECT * FROM room_images WHERE room_id=? ORDER BY id ASC");
$imgs->bind_param("i", $room_id);
$imgs->execute();
$imgsRes = $imgs->get_result();

// reviews for this room
$ratingFilter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
$reviewsSql = "SELECT rv.*, u.first_name, u.last_name, p.display_name FROM reviews rv JOIN users u ON rv.user_id=u.id LEFT JOIN profiles p ON p.user_id=u.id WHERE rv.room_id=? AND rv.is_visible=1";
if ($ratingFilter >= 1 && $ratingFilter <= 5) {
  $reviewsSql .= " AND rv.rating = ?";
  $reviews = $conn->prepare($reviewsSql . " ORDER BY rv.created_at DESC");
  $reviews->bind_param("ii", $room_id, $ratingFilter);
} else {
  $reviews = $conn->prepare($reviewsSql . " ORDER BY rv.created_at DESC");
  $reviews->bind_param("i", $room_id);
}
$reviews->execute();
$reviewsRes = $reviews->get_result();

// overall hotel reviews
$overall = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as count_reviews FROM reviews WHERE is_visible=1")->fetch_assoc();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Room Details</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  :root{
    --primary-color: #dc3545;
    --primary-hover: #c82333;
    --primary-light: rgba(220,53,69,0.08);
    --sidebar-width: 240px;
  }

  html,body{
    height:100%;
    margin:0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background:#fff;
    color:#333;
    -webkit-font-smoothing:antialiased;
  }

  /* container offset when sidebar present on desktop */
  .container{
    max-width:1140px;
    margin-left: 120px;
    padding:18px;
    box-sizing:border-box;
  }

  .page-header{
    background:var(--primary-color);
    color:#fff;
    padding:18px;
    border-radius:8px;
    margin-bottom:18px;
    box-shadow:0 3px 8px rgba(0,0,0,0.06);
  }

  .btn-primary{ background:var(--primary-color); border:none; transition:all .16s; color:#fff; }
  .btn-primary:hover{ background:var(--primary-hover); }
  .btn-secondary{ background:#fff; color:var(--primary-color); border:1px solid var(--primary-color); }
  .btn-success{ background:var(--primary-color); border:none; color:#fff; }

  /* Smaller back button */
  .btn-back {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
    padding: 6px 12px;
    font-size: 0.85rem;
    border-radius: 6px;
    transition: all 0.2s ease;
    text-decoration: none;
  }

  .btn-back:hover {
    background: rgba(255,255,255,0.3);
    color: white;
    border-color: rgba(255,255,255,0.5);
    transform: translateY(-1px);
  }

  .card{ border:none; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.06); margin-bottom:1rem; }
  .card-header{ background:var(--primary-color); color:#fff; font-weight:600; border-radius:8px 8px 0 0; padding:.65rem 1rem; }
  .form-label{ font-weight:600; }

  .alert-warning{ background:#fff3f3; border:1px solid var(--primary-color); color:var(--primary-color); }

  img.img-fluid{ border-radius:8px; transition:transform .18s ease; width:100%; height:auto; }
  img.img-fluid:hover{ transform:scale(1.03); }

  .gallery-row { display:flex; gap:12px; flex-wrap:wrap; }
  .gallery-row .col-md-4 { flex:1 1 calc(33.333% - 12px); min-width:140px; }

  .break-word{ overflow-wrap:anywhere; word-break:break-word; white-space:pre-wrap; }

  /* small helpers */
  .muted-small{ color:#6c757d; font-size:.95rem; }

  /* Hide hamburger toggle button */
  .sidebar-toggle, .toggle-btn {
    display: none !important;
  }

  /* ===== Responsive rules ===== */
  @media (max-width: 991.98px){
    .container{ margin-left:140px; padding:16px; }
    .gallery-row .col-md-4{ flex:1 1 calc(33.333% - 12px); }
  }

  @media (max-width: 767.98px){
    /* remove left offset on small screens (sidebar collapses) */
    .container{ margin-left:0; padding:12px; }
    .page-header{ padding:14px; }
    .gallery-row{ gap:8px; }
    .gallery-row .col-md-4{ flex:1 1 48%; }
    .col-md-8, .col-md-4{ flex:0 0 100%; max-width:100%; }
    .card{ margin-bottom:14px; }
    .card .card-header{ font-size:1rem; }
    .btn, .form-select, .form-control, .btn-primary, .btn-success { width:100% !important; }
    .card .card-body p { font-size: .98rem; }
    
    /* Smaller back button on mobile */
    .btn-back {
      padding: 5px 10px;
      font-size: 0.8rem;
    }
  }

  @media (max-width: 480px){
    .gallery-row .col-md-4{ flex:1 1 100%; }
    .page-header h3 { font-size:1.05rem; }
    .card { padding: 12px; }
    input[type="datetime-local"], .form-select, textarea { font-size: 0.95rem; }
    
    /* Even smaller back button on very small screens */
    .btn-back {
      padding: 4px 8px;
      font-size: 0.75rem;
    }
  }

  /* ensure booking inputs are readable on small screens */
  input[type="datetime-local"] { width:100%; }
</style>
<script>
// [Keep all JS logic intact — same as your original script]
function parseLocalDatetimeLocal(v) {
  if (!v) return NaN;
  const parts = v.split('T');
  if (parts.length !== 2) return NaN;
  const date = parts[0].split('-').map(x => parseInt(x, 10));
  const time = parts[1].split(':').map(x => parseInt(x, 10));
  if (date.length < 3 || time.length < 2) return NaN;
  return new Date(date[0], date[1] - 1, date[2], time[0], time[1], 0, 0).getTime();
}

function calcEstimateFromHours(rateId, hoursId, totalId) {
  const rateEl = document.getElementById(rateId);
  const hoursEl = document.getElementById(hoursId);
  const totalEl = document.getElementById(totalId);
  if (!rateEl || !hoursEl || !totalEl) return;
  const rate = parseFloat(rateEl.value);
  const hours = parseInt(hoursEl.value);
  if (!isNaN(rate) && !isNaN(hours)) {
    const rateCents = Math.round(rate * 100);
    let totalCents = rateCents * hours;
    totalEl.value = (totalCents / 100).toFixed(2);
  } else totalEl.value = '';
}

function calcEstimateFromTimes(rateId, startId, endId, totalId) {
  const rate = parseFloat(document.getElementById(rateId).value);
  const start = document.getElementById(startId).value;
  const end = document.getElementById(endId).value;
  if (!start || !end || isNaN(rate)) { document.getElementById(totalId).value = ''; return; }
  const sMs = parseLocalDatetimeLocal(start);
  const eMs = parseLocalDatetimeLocal(end);
  if (isNaN(sMs) || isNaN(eMs) || eMs <= sMs) { document.getElementById(totalId).value = ''; return; }
  const hours = Math.ceil((eMs - sMs) / (1000 * 60 * 60));
  const rateCents = Math.round(rate * 100);
  let totalCents = rateCents * hours;
  document.getElementById(totalId).value = (totalCents / 100).toFixed(2);
}

// Add function to normalize datetime to hour
function normalizeDatetimeToHour(elementId) {
  const element = document.getElementById(elementId);
  if (element && element.value) {
    const datetime = element.value;
    if (datetime.includes('T')) {
      const [date, time] = datetime.split('T');
      const [hours] = time.split(':');
      element.value = `${date}T${hours.padStart(2, '0')}:00`;
    }
  }
}
</script>
</head>
<body>
<div class="container mt-4 mb-5">

  <?php
  $back = 'browse_rooms.php';
  if (isset($_GET['from']) && $_GET['from'] === 'bookings') $back = 'bookings.php';
  ?>
  <div class="page-header d-flex justify-content-between align-items-center">
    <h3 class="m-0">Room Details</h3>
    <a href="<?= htmlspecialchars($back) ?>" class="btn-back fw-bold">← Back</a>
  </div>

  <?php if (!empty($_SESSION['booking_error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['booking_error']) ?></div>
    <?php unset($_SESSION['booking_error']); ?>
  <?php endif; ?>

  <h4 class="fw-bold text-danger mb-3">
    Room <?= htmlspecialchars($room['room_number']) ?> — <?= htmlspecialchars($room['type']) ?>
  </h4>
  <div class="alert alert-warning">
    Note: Rates may vary and are subject to change. Final price confirmed at booking.
  </div>

  <div class="row">
    <div class="col-md-8">
      <div class="card p-3">
        <?php if ($imgsRes->num_rows): ?>
          <div class="row gallery-row">
            <?php while ($im = $imgsRes->fetch_assoc()): ?>
              <div class="col-md-4 mb-3">
                <img src="<?= htmlspecialchars($im['filepath']) ?>" class="img-fluid" style="height:160px;object-fit:cover;">
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div style="height:200px;background:#f8f8f8;display:flex;align-items:center;justify-content:center;color:#777;border-radius:8px;">
            No images available
          </div>
        <?php endif; ?>

        <p class="mt-3"><?= nl2br(htmlspecialchars($room['description'] ?? ($room['type_description'] ?? 'No description available.'))) ?></p>
        <p><strong>Rate:</strong> ₱<?= number_format($room['hourly_rate'], 2) ?>/hr</p>
      </div>

      <div class="card">
        <div class="card-header">Reviews for this room</div>
        <div class="card-body">
          <form method="get" class="mb-3">
            <input type="hidden" name="id" value="<?= intval($room_id) ?>">
            <label class="form-label">Filter by rating:</label>
            <select name="rating" class="form-select w-auto d-inline-block ms-2" onchange="this.form.submit();">
              <option value="">All</option>
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>" <?= ($ratingFilter == $i) ? 'selected' : '' ?>><?= $i ?>/5</option>
              <?php endfor; ?>
            </select>
          </form>

          <?php if ($reviewsRes->num_rows): ?>
            <?php while ($rv = $reviewsRes->fetch_assoc()): ?>
              <div class="border rounded p-3 mb-2">
                <?php require_once __DIR__ . '/includes/name_helper.php'; ?>
                <strong><?= htmlspecialchars(!empty($rv['display_name']) ? $rv['display_name'] : ($rv['first_name'].' '.$rv['last_name'])) ?></strong>
                <span class="text-muted"> — <?= htmlspecialchars($rv['created_at']) ?></span>
                <div>Rating: <span class="text-danger fw-bold"><?= intval($rv['rating']) ?>/5</span></div>
                <div class="break-word"><?= nl2br(htmlspecialchars($rv['comment'])) ?></div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-muted">No reviews yet for this room.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card">
        <div class="card-header">Motel Overview</div>
        <div class="card-body">
          <p><strong>Average rating:</strong> <?= $overall['avg_rating'] ? number_format($overall['avg_rating'], 2) : 'N/A' ?></p>
          <p><strong>Total reviews:</strong> <?= $overall['count_reviews'] ?></p>
          <a href="reviews.php" class="btn btn-outline-danger btn-sm mt-2">See all overall reviews</a>
        </div>
      </div>

      <div class="card">
        <div class="card-header">Book / Estimate</div>
        <div class="card-body">
          <?php if ($room['status'] === 'available'): ?>
            <form method="post" action="book_room.php" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
              <?= csrf_input_field() ?>
              <input type="hidden" name="room_id" value="<?= $room_id ?>">
              <input type="hidden" id="rate_detail" value="<?= $room['hourly_rate'] ?>">

              <label class="form-label">Start (date & time)</label>
              <input name="start_time" id="start_detail" type="datetime-local" class="form-control mb-2" required onchange="normalizeDatetimeToHour('start_detail'); calcEstimateFromTimes('rate_detail','start_detail','end_detail','total_detail')">

              <label class="form-label">End (date & time)</label>
              <input name="end_time" id="end_detail" type="datetime-local" class="form-control mb-2" required onchange="normalizeDatetimeToHour('end_detail'); calcEstimateFromTimes('rate_detail','start_detail','end_detail','total_detail')">

              <label class="form-label">Estimate (₱)</label>
              <input id="total_detail" name="total_est" readonly class="form-control mb-2">

              <label class="form-label">Payment</label>
              <select name="payment" class="form-select mb-3" required>
                <option value="cash">Cash</option>
                <option value="online">Online</option>
              </select>

              <button class="btn btn-success w-100">Book Now</button>
            </form>
          <?php else: ?>
            <div class="alert alert-warning">This room is not available for booking.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header">Leave a Review</div>
        <div class="card-body">
          <?php if (!empty($_GET['err']) && $_GET['err'] === 'cannot_submit_review'): ?>
            <div class="alert alert-danger">You can only review this room after completing a booking.</div>
          <?php endif; ?>
          <form method="post" action="reviews.php">
            <?php require_once __DIR__.'/includes/csrf.php'; echo csrf_input_field(); ?>
            <input type="hidden" name="room_type_id" value="<?= intval($room['room_type_id']) ?>">
            <input type="hidden" name="room_id" value="<?= $room_id ?>">
            <?php $ret = 'room_details.php?id='.$room_id; if(isset($_GET['from']) && $_GET['from']==='bookings') $ret .= '&from=bookings'; ?>
            <input type="hidden" name="return_to" value="<?= htmlspecialchars($ret) ?>">

            <label class="form-label">Rating</label>
            <select name="rating" class="form-select mb-2">
              <option value="5">5</option>
              <option value="4">4</option>
              <option value="3">3</option>
              <option value="2">2</option>
              <option value="1">1</option>
            </select>

            <label class="form-label">Comment</label>
            <textarea name="comment" class="form-control mb-3"></textarea>

            <button class="btn btn-primary w-100">Submit Review</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/image_modal.php'; ?>
</body>
</html>