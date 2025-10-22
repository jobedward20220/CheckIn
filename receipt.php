<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/includes/auth_check.php";
require_once __DIR__ . "/db_connect.php";
$booking_id = intval($_GET['booking_id'] ?? 0);
if ($booking_id <= 0) die('Invalid booking id');
// fetch booking
$stmt = $conn->prepare("SELECT b.*, r.room_number, t.name as room_type, t.hourly_rate, u.first_name, u.middle_name, u.last_name FROM bookings b JOIN rooms r ON b.room_id=r.id JOIN room_types t ON r.room_type_id=t.id JOIN users u ON b.user_id=u.id WHERE b.id=? LIMIT 1");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$bk = $stmt->get_result()->fetch_assoc();
if (!$bk) die('Booking not found');
// verify current user owns booking or is admin
if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $bk['user_id']) die('Unauthorized');

// Build formatted values (escape for HTML)
$fullName = htmlspecialchars($bk['first_name'] . (!empty($bk['middle_name']) ? ' '.$bk['middle_name'] : '') . ' ' . $bk['last_name']);
$roomNumber = htmlspecialchars($bk['room_number']);
$roomType = htmlspecialchars($bk['room_type']);
$hours = intval($bk['hours']);
$total = number_format($bk['total_amount'], 2);
$payment = htmlspecialchars($bk['payment_method']);
$status = htmlspecialchars($bk['status']);
$created = htmlspecialchars($bk['created_at']);
$bookingRef = htmlspecialchars($bk['id']);

// Generate receipt number with proper format
$receiptNumber = 'CI' . str_pad($bookingRef, 6, '0', STR_PAD_LEFT);

// Primary receipt HTML (for browser rendering)
$pageHtml = '<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Official Receipt — Redwood Motel #'.$receiptNumber.'</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  body { 
    background: #f8f9fa; 
    font-family: "Courier New", Courier, monospace;
    color: #000;
    line-height: 1.2;
  }
  .receipt-container { 
    max-width: 380px; 
    margin: 20px auto; 
    background: white;
    border: 2px solid #000;
    border-radius: 0;
    padding: 20px;
    position: relative;
  }
  .receipt-header {
    text-align: center;
    border-bottom: 3px double #000;
    padding-bottom: 15px;
    margin-bottom: 15px;
  }
  .hotel-name {
    font-size: 24px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0;
  }
  .hotel-address {
    font-size: 12px;
    margin: 5px 0;
    color: #333;
  }
  .hotel-contact {
    font-size: 11px;
    margin: 3px 0;
    color: #333;
  }
  .receipt-title {
    font-size: 16px;
    font-weight: 900;
    text-transform: uppercase;
    text-align: center;
    margin: 10px 0;
    border-top: 1px dashed #000;
    border-bottom: 1px dashed #000;
    padding: 5px 0;
  }
  .receipt-info {
    font-size: 12px;
    margin-bottom: 15px;
  }
  .receipt-info strong {
    font-weight: 900;
  }
  .line-items {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    margin: 15px 0;
  }
  .line-items th {
    border-bottom: 2px solid #000;
    padding: 5px 3px;
    text-align: left;
    font-weight: 900;
    text-transform: uppercase;
  }
  .line-items td {
    padding: 4px 3px;
    border-bottom: 1px dotted #ccc;
    vertical-align: top;
  }
  .line-items .item-desc {
    width: 50%;
  }
  .line-items .item-rate,
  .line-items .item-qty,
  .line-items .item-amount {
    width: 16%;
    text-align: right;
  }
  .total-section {
    border-top: 2px solid #000;
    margin-top: 10px;
    padding-top: 10px;
  }
  .total-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin: 3px 0;
  }
  .grand-total {
    font-weight: 900;
    font-size: 15px;
    border-top: 3px double #000;
    border-bottom: 3px double #000;
    padding: 8px 0;
    margin: 10px 0;
  }
  .payment-method {
    font-size: 12px;
    margin: 15px 0;
    padding: 10px;
    border: 1px solid #000;
    background: #f9f9f9;
  }
  .thank-you {
    text-align: center;
    font-size: 11px;
    margin: 15px 0;
    padding: 10px;
    border-top: 1px dashed #000;
  }
  .footer-note {
    font-size: 10px;
    text-align: center;
    color: #666;
    margin-top: 20px;
  }
  .stamp-area {
    text-align: center;
    margin: 20px 0;
    padding: 15px;
    border: 1px dashed #000;
  }
  .stamp {
    font-family: Arial, sans-serif;
    font-size: 14px;
    font-weight: 900;
    color: #dc3545;
    border: 2px solid #dc3545;
    padding: 5px 15px;
    display: inline-block;
    transform: rotate(-5deg);
  }
  .barcode-area {
    text-align: center;
    margin: 15px 0;
    padding: 10px;
  }
  .barcode {
    font-family: "Libre Barcode 128", monospace;
    font-size: 36px;
    letter-spacing: 2px;
  }
  .actions {
    text-align: center;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #ccc;
  }
  .btn-receipt {
    background: #000;
    color: white;
    border: 2px solid #000;
    border-radius: 0;
    padding: 8px 20px;
    font-weight: 900;
    text-transform: uppercase;
    font-size: 12px;
    margin: 0 5px;
  }
  .btn-receipt:hover {
    background: #333;
    border-color: #333;
    color: white;
  }
  .btn-receipt-outline {
    background: white;
    color: #000;
    border: 2px solid #000;
    border-radius: 0;
    padding: 8px 20px;
    font-weight: 900;
    text-transform: uppercase;
    font-size: 12px;
    margin: 0 5px;
  }
  .btn-receipt-outline:hover {
    background: #000;
    color: white;
  }
    .item-qty {
    margin-right: 10px;
}
  @media print {
    body { background: white; }
    .actions { display: none; }
    .receipt-container { 
      margin: 0; 
      border: none;
      box-shadow: none;
      max-width: 100%;
    }
  }
  @media (max-width: 400px) {
    .receipt-container {
      margin: 10px;
      padding: 15px;
    }
  }
</style>
</head>
<body>
  <div class="receipt-container">
    <!-- Receipt Header -->
    <div class="receipt-header">
      <h1 class="hotel-name">CHECKIN MOTEL</h1>
      <div class="hotel-address">Sudlon, Maguikay, Mandaue City 6014 Cebu City Central Visayas</div>
      <div class="hotel-contact">Tel: (02) 8123-4567 | Email: info@checkinmotel.com</div>
      <div class="hotel-contact">VAT Reg TIN: 123-456-789-000</div>
    </div>

    <!-- Receipt Title -->
    <div class="receipt-title">OFFICIAL RECEIPT</div>

    <!-- Receipt Information -->
    <div class="receipt-info">
      <div><strong>Receipt No:</strong> '.$receiptNumber.'</div>
      <div><strong>Date Issued:</strong> '.date('M j, Y g:i A', strtotime($created)).'</div>
      <div><strong>Booking Ref:</strong> #'.$bookingRef.'</div>
      <div><strong>Status:</strong> '.strtoupper($status).'</div>
    </div>

    <!-- Guest Information -->
    <div class="receipt-info">
      <div><strong>Guest Name:</strong> '.$fullName.'</div>
      <div><strong>Room:</strong> '.$roomNumber.' - '.$roomType.'</div>
      <div><strong>Duration:</strong> '.$hours.' hour'.($hours > 1 ? 's' : '').'</div>';
      
if (!empty($bk['check_in']) && !empty($bk['check_out'])) {
    $pageHtml .= '<div><strong>Check-in:</strong> '.date('M j, Y g:i A', strtotime($bk['check_in'])).'</div>
                  <div><strong>Check-out:</strong> '.date('M j, Y g:i A', strtotime($bk['check_out'])).'</div>';
}

$pageHtml .= '</div>

    <!-- Line Items -->
    <table class="line-items">
      <thead>
        <tr>
          <th class="item-desc">Description</th>
          <th class="item-rate">Rate</th>
          <th class="item-qty">Hr</th>
          <th class="item-amount">Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="item-desc">Room Accommodation<br><small>'.$roomType.' - '.$hours.' hour'.($hours > 1 ? 's' : '').'</small></td>
          <td class="item-rate">'.number_format($bk['hourly_rate'],2).'</td>
          <td class="item-qty">'.$hours.'</td>
          <td class="item-amount">'.number_format(($bk['hourly_rate'] * $hours),2).'</td>
        </tr>';

// Include any other line items if present
$lineTotal = ($bk['hourly_rate'] * $hours);
if (!empty($bk['extra_charges'])) {
    $extras = json_decode($bk['extra_charges'], true);
    if (is_array($extras)) {
        foreach ($extras as $label => $amt) {
            $pageHtml .= '<tr>
                <td class="item-desc">'.htmlspecialchars($label).'</td>
                <td class="item-rate">'.number_format($amt,2).'</td>
                <td class="item-qty">1</td>
                <td class="item-amount">'.number_format($amt,2).'</td>
            </tr>';
            $lineTotal += floatval($amt);
        }
    }
}

$pageHtml .= '</tbody>
    </table>

    <!-- Totals Section -->
    <div class="total-section">
      <div class="total-row">
        <span>Subtotal:</span>
        <span>'.number_format($lineTotal,2).'</span>
      </div>';

// Taxes and fees
$feesTotal = 0;
if (!empty($bk['tax']) || !empty($bk['service_fee'])) {
    $tax = floatval($bk['tax'] ?? 0);
    $service = floatval($bk['service_fee'] ?? 0);
    if ($tax) {
        $feesTotal += $tax;
        $pageHtml .= '<div class="total-row">
            <span>VAT (12%):</span>
            <span>'.number_format($tax,2).'</span>
          </div>';
    }
    if ($service) {
        $feesTotal += $service;
        $pageHtml .= '<div class="total-row">
            <span>Service Fee:</span>
            <span>'.number_format($service,2).'</span>
          </div>';
    }
}

$grandTotal = floatval(str_replace(',', '', $total));
$pageHtml .= '
      <div class="total-row grand-total">
        <span>TOTAL AMOUNT:</span>
        <span>'.number_format($grandTotal,2).'</span>
      </div>
    </div>

    <!-- Payment Method -->
    <div class="payment-method">
      <div><strong>Payment Method:</strong> '.strtoupper($payment).'</div>
      <div><strong>Amount Paid:</strong> '.number_format($grandTotal,2).'</div>
      <div><strong>Change:</strong> 0.00</div>
    </div>

    <!-- Stamp Area -->
    <div class="stamp-area">
      <div class="stamp">PAID</div>
      <div style="margin-top: 10px; font-size: 11px;">'.date('M j, Y').'</div>
    </div>

    <!-- Barcode Area -->
    <div class="barcode-area">
      <div class="barcode">*'.$receiptNumber.'*</div>
      <div style="font-size: 10px; margin-top: 5px;">'.$receiptNumber.'</div>
    </div>

    <!-- Thank You Message -->
    <div class="thank-you">
      <strong>THANK YOU FOR CHOOSING CHECKIN MOTEL!</strong><br>
      <span>This receipt is your official proof of payment.</span><br>
      <span>For inquiries: (02) 8123-4567</span>
    </div>

    <!-- Footer Note -->
    <div class="footer-note">
      Receipt generated electronically • Valid without signature<br>
      '.date('F j, Y \a\t g:i A').'
    </div>

    <!-- Action Buttons -->
    <div class="actions">
      <button onclick="window.print()" class="btn-receipt">
        <i class="fas fa-print me-1"></i>Print Receipt
      </button>
      <a href="?booking_id='.$bookingRef.'&download=pdf" class="btn-receipt-outline">
        <i class="fas fa-download me-1"></i>Save PDF
      </a>
    </div>
  </div>
</body>
</html>';

// If Dompdf is installed via Composer and PDF explicitly requested, use it to generate a PDF
$wantPdf = isset($_GET['download']) && $_GET['download'] === 'pdf';
$wantHtmlDownload = isset($_GET['download']) && $_GET['download'] === 'html';
if ($wantPdf && file_exists(__DIR__.'/vendor/autoload.php')) {
    require_once __DIR__.'/vendor/autoload.php';
    try {
        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($pageHtml);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('receipt_'.$receiptNumber.'.pdf');
        exit;
    } catch (Exception $e) {
        error_log('Dompdf error: '.$e->getMessage());
        // fallback to render HTML page
    }
}

// If user requested raw HTML download
if ($wantHtmlDownload) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="receipt_'.$receiptNumber.'.html"');
    echo $pageHtml;
    exit;
}

// Default: render page in browser
echo $pageHtml;
?>