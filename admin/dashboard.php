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

// Fetch latest issue reports (LIMIT 5)
$reports = $conn->query("
  SELECT r.id, r.laptop_id, r.issue_description, r.report_time, r.status,
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
    .container-fluid {
      margin-left: 300px;
      padding: 2rem;
    }
    .card {
      border: none;
      border-radius: 1rem;
      background: rgba(255, 255, 255, 0.95);
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 40px rgba(31, 38, 135, 0.5);
    }
    .card-body {
      padding: 1.5rem;
    }
    .card h5 {
      font-weight: 600;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .card h2 {
      font-size: 2.5rem;
      font-weight: bold;
      margin: 0.5rem 0;
    }
    .card a {
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
    }
    .card a:hover {
      color: #ffd700 !important;
    }
    .bg-primary {
      background: linear-gradient(45deg, #007bff, #00c4ff) !important;
    }
    .bg-success {
      background: linear-gradient(45deg, #28a745, #34c759) !important;
    }
    .bg-info {
      background: linear-gradient(45deg, #17a2b8, #1ac7e0) !important;
    }
    .bg-danger {
      background: linear-gradient(45deg, #dc3545, #ff4d4d) !important;
    }
    .card-header {
      background: linear-gradient(45deg, #2c3e50, #34495e) !important;
      color: #ffffff;
      font-weight: 600;
      border-radius: 1rem 1rem 0 0 !important;
      padding: 1rem 1.5rem;
    }
    h2.fs-1 {
      font-size: 2.5rem;
      font-weight: 700;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    h1.fs-3 {
      font-size: 1.75rem;
      font-weight: 500;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    .table {
      background: #ffffff;
      border-radius: 0.5rem;
      overflow: hidden;
    }
    .table thead {
      background: #e9ecef;
      color: #2c3e50;
    }
    .table tbody tr {
      transition: background 0.3s ease;
    }
    .table tbody tr:hover {
      background: #f1f3f5;
    }
    .badge {
      padding: 0.5rem 1rem;
      border-radius: 0.5rem;
      font-weight: 500;
    }
    .badge.bg-success {
      background: #28a745 !important;
    }
    .badge.bg-danger {
      background: #dc3545 !important;
    }
    .text-muted {
      color: #d1d5db !important;
    }
    @media (max-width: 768px) {
      .container-fluid {
        margin-left: 0;
        padding: 1rem;
      }
      .card {
        width: 100% !important;
      }
    }
  </style>
</head>
<body>

<?php include '../includes/admin_sidebar.php'; ?>

<div class="container-fluid" style="margin-left: 300px; position:fixed;">
  <div class="p-4">
    <h2 class="mb-2 fs-1 text-center">Admin Dashboard</h2>
    <div class="p-3 fs-6">
      <h1 class="fs-3" style="">Welcome, <?= htmlspecialchars($_SESSION['name']) ?> (Admin)</h1>
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

      <div class="col-md-3" style="width:400px">
        <div class="card bg-success text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-user-graduate"></i> Total Students</h5>
            <h2><?= $totalStudents ?></h2>
            <a href="manage_students.php" class="text-white">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
      </div>

      <div class="col-md-3" style="width:400px">
        <div class="card bg-info text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-door-open"></i> Laptops Inside</h5>
            <h2><?= $currentlyInside ?></h2>
            <a href="movement_logs.php" class="text-white">More info <i class="fas fa-arrow-circle-right"></i></a>
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
          <table class="table table-bordered table-striped" style="width:100%;">
            <thead class="table-secondary">
              <tr>
                <th>ID</th>
                <th>Student</th>
                <th>Serial</th>
                <th>Issue</th>
                <th>Reported By</th>
                <th>Status</th>
                <th>Reported At</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $reports->fetch_assoc()): ?>
                <tr>
                  <td><?= $row['id'] ?></td>
                  <td><?= htmlspecialchars($row['student_fname'] . ' ' . $row['student_lname']) ?></td>
                  <td><?= htmlspecialchars($row['serial_number']) ?></td>
                  <td><?= htmlspecialchars($row['issue_description']) ?></td>
                  <td><?= htmlspecialchars($row['guard_name']) ?></td>
                  <td>
                    <span class="badge <?= strtolower($row['status']) === 'solved' ? 'bg-success' : 'bg-danger' ?>">
                      <?= ucfirst($row['status']) ?>
                    </span>
                  </td>
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
