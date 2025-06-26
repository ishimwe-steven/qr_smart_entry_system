<?php
session_start();
include '../includes/db.php';

$laptop_id = intval($_GET['laptop_id']);

// Fetch laptop + student info
$sql = "
  SELECT s.first_name, s.last_name, s.reg_no, s.department, s.picture,
         l.serial_number, l.brand
  FROM laptops l
  LEFT JOIN students s ON l.student_id = s.id
  WHERE l.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $laptop_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "<div style='color: red; font-weight: bold;'>Laptop not found!</div>";
    exit;
}

$data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Laptop QR Info</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="card shadow" style="max-width: 600px; margin: auto;">
    <div class="card-body text-center">
      <h3 class="card-title mb-3">Laptop Information</h3>

      <?php if (!empty($data['picture'])): ?>
        <img src="../uploads/<?= htmlspecialchars($data['picture']) ?>" class="img-thumbnail mb-3" style="width: 150px; height: auto;">
      <?php else: ?>
        <p><em>No picture available</em></p>
      <?php endif; ?>

      <p><strong>Name:</strong> <?= htmlspecialchars($data['first_name'] . ' ' . $data['last_name']) ?></p>
      <p><strong>Reg No:</strong> <?= htmlspecialchars($data['reg_no']) ?></p>
      <p><strong>Department:</strong> <?= htmlspecialchars($data['department']) ?></p>
      <p><strong>Laptop Brand:</strong> <?= htmlspecialchars($data['brand']) ?></p>
      <p><strong>Laptop Serial:</strong> <?= htmlspecialchars($data['serial_number']) ?></p>

      <button class="btn btn-primary mt-3" onclick="window.print()">Print Info</button>
    </div>
  </div>
</div>

</body>
</html>
