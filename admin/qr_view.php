<?php
session_start();
include '../includes/db.php';

$id = intval($_GET['id']);

// Fetch student + laptop info
$sql = "
  SELECT s.first_name, s.last_name, s.reg_no, s.department, s.picture,
         l.serial_number, l.brand
  FROM students s
  LEFT JOIN laptops l ON s.id = l.student_id
  WHERE s.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "<div style='color: red; font-weight: bold;'>Student not found!</div>";
    exit;
}

$data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student QR Info</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="card shadow" style="max-width: 600px; margin: auto;">
    <div class="card-body text-center">
      <h3 class="card-title mb-3">Student Information</h3>

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
