<?php
// ─────────────────────────────────────────────
//  Secure session + Admin Access
// ─────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "../db_connect.php";
require_once __DIR__ . '/../includes/csrf.php';

// Admin-only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}

// ─────────────────────────────────────────────
//  Process POST actions (status updates, receipts)
// ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && !empty($_POST['id'])) {
  verify_csrf();
  $id = intval($_POST['id']);
  $action = $_POST['action'];
  $valid = ['confirmed', 'ongoing', 'checked_out', 'canceled'];

  if (in_array($action, $valid)) {
    $stmt = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
    $stmt->bind_param("si", $action, $id);
    $stmt->execute();

    if ($action === 'canceled' || $action === 'checked_out') {
      $conn->query("UPDATE rooms SET status='available' WHERE id=(SELECT room_id FROM bookings WHERE id=$id)");

      if ($action === 'checked_out') {
        $chk = $conn->prepare("SELECT b.receipt_sent, u.email FROM bookings b JOIN users u ON b.user_id=u.id WHERE b.id=? LIMIT 1");
        $chk->bind_param("i", $id);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();

        if ($row && intval($row['receipt_sent']) === 0) {
          // Use receipt.php for HTML generation
          define('GENERATING_RECEIPT_FOR_EMAIL', true);
          ob_start();
          $_GET['booking_id'] = $id;
          include __DIR__ . '/../receipt.php'; // will produce $pageHtml
          $html = ob_get_clean();

          // Generate PDF file
          $receiptPath = __DIR__ . '/../uploads/receipts';
          if (!file_exists($receiptPath)) @mkdir($receiptPath, 0755, true);
          $pdfFile = $receiptPath . '/receipt_' . $id . '.pdf';
          $pdfCreated = false;

          if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
            try {
              $dompdf = new Dompdf\Dompdf();
              $dompdf->loadHtml($html);
              $dompdf->setPaper('A4', 'portrait');
              $dompdf->render();
              file_put_contents($pdfFile, $dompdf->output());
              $pdfCreated = file_exists($pdfFile);
            } catch (Exception $e) {
              error_log('Dompdf error: ' . $e->getMessage());
            }
          }

          // Send receipt email
          require_once __DIR__ . '/../includes/mail.php';
          $to = $row['email'];
          $subject = 'Your booking receipt (Booking #' . $id . ')';
          $body = $html;
          $sent = $pdfCreated
            ? send_mail_with_attachment($to, $subject, $body, true, $pdfFile)
            : send_mail($to, $subject, $body, true);

          if ($sent) {
            $upd = $conn->prepare("UPDATE bookings SET receipt_sent=1 WHERE id=?");
            $upd->bind_param("i", $id);
            $upd->execute();
          }
        }
        $chk->close();
      }

      header('Location: manage_bookings.php?msg=booking_updated');
      exit;
    } elseif ($action === 'confirmed') {
      $conn->query("UPDATE rooms SET status='occupied' WHERE id=(SELECT room_id FROM bookings WHERE id=$id)");
    }
  }
}

// ─────────────────────────────────────────────
//  Build search filters
// ─────────────────────────────────────────────
$where = [];
$params = [];
$types = '';

if (!empty($_GET['q'])) {
  $where[] = "(u.email LIKE ? OR r.room_number LIKE ?)";
  $params[] = '%' . $_GET['q'] . '%';
  $params[] = '%' . $_GET['q'] . '%';
  $types .= 'ss';
}
if (!empty($_GET['status'])) { $where[] = "b.status = ?"; $params[] = $_GET['status']; $types .= 's'; }
if (!empty($_GET['room'])) { $where[] = "r.id = ?"; $params[] = intval($_GET['room']); $types .= 'i'; }
if (!empty($_GET['from_date'])) { $where[] = "DATE(b.start_time) >= ?"; $params[] = $_GET['from_date']; $types .= 's'; }
if (!empty($_GET['to_date'])) { $where[] = "DATE(b.start_time) <= ?"; $params[] = $_GET['to_date']; $types .= 's'; }

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT b.*, u.email, r.room_number 
        FROM bookings b 
        JOIN users u ON b.user_id=u.id 
        JOIN rooms r ON b.room_id=r.id 
        $where_sql 
        ORDER BY (b.start_time >= NOW()) DESC, b.start_time ASC, b.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

// ─────────────────────────────────────────────
//  Include sidebar
// ─────────────────────────────────────────────
include "admin_sidebar.php";
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Bookings | Admin</title>
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
  @media (min-width: 992px) {
    .page-wrapper {
      margin-left: var(--sidebar-width);
    }
    .page-inner {
      max-width: var(--content-max);
      margin: 0 auto;
    }
  }
  .table thead { background-color: #c62828; color: white; }
  .btn-outline-danger { color: #c62828; border-color: #c62828; }
  .btn-outline-danger:hover { background-color: #c62828; color: white; }
  h3 { color: #c62828; }
</style>
</head>

<body>
  <div class="page-wrapper">
    <div class="page-inner">
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
        <h3 class="fw-bold mb-3 mb-sm-0 text-danger">Manage Bookings</h3>
      </div>

      <?php if (!empty($_GET['msg']) && $_GET['msg']==='booking_updated'): ?>
        <div class="alert alert-success">Booking updated successfully.</div>
      <?php endif; ?>

      <!-- Filter Form -->
      <form method="get" class="row gy-2 gx-2 mb-3">
        <div class="col-12 col-sm-6 col-md-3">
          <input name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" class="form-control" placeholder="Search by email or room #">
        </div>
        <div class="col-6 col-md-2">
          <select name="status" class="form-select">
            <option value="">Any status</option>
            <?php foreach(['reserved','confirmed','ongoing','checked_out','canceled'] as $s): ?>
              <option value="<?=$s?>" <?=(!empty($_GET['status']) && $_GET['status']==$s)? 'selected':''?>><?=ucfirst($s)?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2"><input name="room" value="<?= htmlspecialchars($_GET['room'] ?? '') ?>" class="form-control" placeholder="Room ID"></div>
        <div class="col-6 col-md-2"><input name="from_date" type="date" value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>" class="form-control"></div>
        <div class="col-6 col-md-2"><input name="to_date" type="date" value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>" class="form-control"></div>
        <div class="col-12 col-md-1"><button class="btn btn-danger w-100">Filter</button></div>
      </form>

      <!-- Bookings Table -->
      <div class="card shadow-sm">
        <div class="card-body p-2 p-md-3">
          <div class="table-responsive">
            <table class="table table-bordered table-striped text-center align-middle mb-0">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>User</th>
                  <th>Room</th>
                  <th>Start</th>
                  <th>End</th>
                  <th>Amount</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php while($b = $res->fetch_assoc()): ?>
                  <tr class="<?= $b['status'] === 'canceled' ? 'table-light' : '' ?>">
                    <td><?= $b['id'] ?></td>
                    <td><?= htmlspecialchars($b['email']) ?></td>
                    <td><?= htmlspecialchars($b['room_number']) ?></td>
                    <td><?= htmlspecialchars($b['start_time']) ?></td>
                    <td><?= htmlspecialchars($b['end_time']) ?></td>
                    <td>₱<?= number_format($b['total_amount'],2) ?></td>
                    <td><span class="badge bg-danger text-light"><?= htmlspecialchars($b['status']) ?></span></td>
                    <td>
                      <div class="d-flex flex-wrap gap-1 justify-content-center">
                        <?php foreach(['confirmed','ongoing','checked_out','canceled'] as $st): ?>
                          <form method="post" class="d-inline">
                            <?= csrf_input_field() ?>
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <input type="hidden" name="action" value="<?= $st ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit"><?= ucfirst($st) ?></button>
                          </form>
                        <?php endforeach; ?>
                        <a class="btn btn-sm btn-danger" href="../receipt.php?booking_id=<?= $b['id'] ?>&download=pdf">Receipt</a>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
