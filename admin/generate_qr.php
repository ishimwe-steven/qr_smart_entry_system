<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';
include '../libs/phpqrcode/qrlib.php';

// Validate laptop_id
if (!isset($_GET['laptop_id']) || !is_numeric($_GET['laptop_id'])) {
    echo "<div class='alert alert-danger'>No valid laptop ID specified.</div>";
    exit;
}

$laptop_id = intval($_GET['laptop_id']);

// Get laptop + student info
$sql = "
  SELECT s.first_name, s.last_name, s.reg_no, s.department, s.picture,
         l.id AS laptop_id, l.serial_number, l.brand
  FROM laptops l
  LEFT JOIN students s ON l.student_id = s.id
  WHERE l.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $laptop_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "<div class='alert alert-danger'>Laptop not found!</div>";
    exit;
}

$data = $result->fetch_assoc();

// Build QR content (link with laptop_id)
$content = "http://localhost/qr_smart_entry_system/admin/qr_view.php?laptop_id={$data['laptop_id']}";

// Generate QR code
$qrDir = "../qr_codes/";
if (!is_dir($qrDir)) {
    mkdir($qrDir, 0777, true);
}

// Clean file name parts to prevent issues
$reg_no_clean = preg_replace('/[^A-Za-z0-9]/', '_', $data['reg_no']);
$serial_clean = preg_replace('/[^A-Za-z0-9]/', '_', $data['serial_number']);
$fileName = $qrDir . "qr_{$reg_no_clean}_{$serial_clean}.png";

QRcode::png($content, $fileName, QR_ECLEVEL_H, 8);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Generated QR Code</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f4f6f9;
    }
    .qr-container {
      max-width: 500px;
      margin: 50px auto;
      padding: 30px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      text-align: center;
    }
    img {
      width: 250px;
      height: auto;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

<div class="qr-container">
  <h3 class="mb-3">QR Code for <?= htmlspecialchars($data['first_name'] . ' ' . $data['last_name']) ?> (<?= htmlspecialchars($data['brand']) ?>)</h3>
  <img src="<?= $fileName ?>" alt="QR Code">
  <p class="mb-3">Scan this QR code to view laptop info.</p>
  <button class="btn btn-primary me-2" onclick="window.print()"><i class="fas fa-print"></i> Print QR Code</button>
  <a href="manage_students.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</body>
</html>
