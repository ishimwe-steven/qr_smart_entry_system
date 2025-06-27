<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';

// Counts
$totalLaptops = $conn->query("SELECT COUNT(*) AS count FROM laptops")->fetch_assoc()['count'];
$totalStudents = $conn->query("SELECT COUNT(*) AS count FROM students")->fetch_assoc()['count'];
$currentlyInside = $conn->query("SELECT COUNT(*) AS count FROM laptop_movements WHERE status='IN'")->fetch_assoc()['count'];
$currentlyOutside = $conn->query("SELECT COUNT(*) AS count FROM laptop_movements WHERE status='OUT'")->fetch_assoc()['count'];

// Fetch latest issue reports
$reports = $conn->query("
  SELECT r.id, r.laptop_id, r.issue_description, r.report_time,
         s.first_name AS student_fname, s.last_name AS student_lname,
         l.serial_number,
         u.name AS guard_name
  FROM issue_reports r
  LEFT JOIN laptops l ON r.laptop_id = l.id
  LEFT JOIN students s ON l.student_id = s.id
  LEFT JOIN users u ON r.reported_by = u.id
  ORDER BY r.report_time DESC
  LIMIT 5
");

include '../includes/header.php';
include '../includes/admin_sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body { background-color: #f4f6f9; }
    .card { border: none; border-radius: 0.75rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,.15); }
  </style>
</head>
<body>

<div class="container-fluid" style="margin-left: 300px; position:fixed;">
  <div class="p-4">
    <h2 class="mb-2 fs-1 text-center">Admin Dashboard</h2>
    <div class="p-3 fs-6">
      <h1 class="fs-3">Welcome, <?= htmlspecialchars($_SESSION['name']) ?> (Admin)</h1>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-3" style="width:400px">
        <div class="card bg-primary text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-laptop"></i> Total Laptops</h5>
            <h2><?= $totalLaptops ?></h2>
            <a href="manage_students.php" class="text-white">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
      </div>


       <div class="col-md-3"style="width:400px">
        <div class="card bg-success text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-user-graduate"></i> Total Students</h5>
            <h2><?= $totalStudents ?></h2>
            <a href="manage_students.php" class="text-white">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
      </div>

      <div class="col-md-3" style="width:400px">
        <div class="card bg-warning text-dark shadow">
          <div class="card-body">
            <h5><i class="fas fa-door-open"></i> Laptops Inside</h5>
            <h2><?= $currentlyInside ?></h2>
            <a href="movement_logs.php" class="text-dark">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
      </div>

      <div class="col-md-3" style="width:400px">
        <div class="card bg-danger text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-door-closed"></i> Laptops Outside</h5>
            <h2><?= $currentlyOutside ?></h2>
            <a href="movement_logs.php" class="text-white">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Issue Reports -->
    <div class="card mt-5" style="width:102rem">
      <div class="card-header bg-dark text-white">
        <i class="fas fa-exclamation-triangle"></i> Recent Issue Reports from Security
      </div>
      <div class="card-body">
        <?php if ($reports->num_rows > 0): ?>
          <table class="table table-bordered table-striped" style="width:1500px;">
            <thead class="table-secondary">
              <tr>
                <th>ID</th>
                <th>Student</th>
                <th>Serial</th>
                <th>Issue</th>
                <th>Reported By</th>
                <th>Reported At</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $reports->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($row['student_fname'] . ' ' . $row['student_lname']) ?></td>
                  <td><?= $row['id'] ?></td>
                  <td><?= htmlspecialchars($row['serial_number']) ?></td>
                  <td><?= htmlspecialchars($row['issue_description']) ?></td>
                  <td><?= htmlspecialchars($row['guard_name']) ?></td>
                  <td><?= htmlspecialchars($row['report_time']) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-muted">No issues reported recently.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
