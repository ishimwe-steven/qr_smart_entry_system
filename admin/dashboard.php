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

// Latest issue reports (LIMIT 5)
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

// Daily data (last 7 days)
$dailyData = $conn->query("
  SELECT DATE(created_at) as day, 
         SUM(CASE WHEN status='IN' THEN 1 ELSE 0 END) as entry_count,
         SUM(CASE WHEN status='OUT' THEN 1 ELSE 0 END) as exit_count
  FROM laptop_movements
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
  GROUP BY day
  ORDER BY day ASC
");

$days = [];
$entryDaily = [];
$exitDaily = [];
$totalDaily = [];
$currentInside = 0;

while ($row = $dailyData->fetch_assoc()) {
    $days[] = $row['day'];
    $entryDaily[] = $row['entry_count'];
    $exitDaily[] = $row['exit_count'];
    $currentInside += ($row['entry_count'] - $row['exit_count']);
    $totalDaily[] = $currentInside;
}

// Monthly data (last 6 months)
$monthlyData = $conn->query("
  SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
         SUM(CASE WHEN status='IN' THEN 1 ELSE 0 END) as entry_count,
         SUM(CASE WHEN status='OUT' THEN 1 ELSE 0 END) as exit_count
  FROM laptop_movements
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY month
  ORDER BY month ASC
");

$months = [];
$entryMonthly = [];
$exitMonthly = [];
$totalMonthly = [];
$currentInsideMonth = 0;

while ($row = $monthlyData->fetch_assoc()) {
    $months[] = $row['month'];
    $entryMonthly[] = $row['entry_count'];
    $exitMonthly[] = $row['exit_count'];
    $currentInsideMonth += ($row['entry_count'] - $row['exit_count']);
    $totalMonthly[] = $currentInsideMonth;
}

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background-color: #f4f6f9;
      display: flex;
    }
    /* Sidebar fixed */
    .sidebar {
      width: 280px;
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      z-index: 1000;
    }
    /* Main content */
    .main-content {
      margin-left: 280px;
      padding: 2rem;
      width: calc(100% - 280px);
    }
    .card {
      border: none;
      border-radius: 0.75rem;
      box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,.15);
    }
    .chart-container {
      background: #fff;
      border-radius: 1rem;
      padding: 20px;
      margin-top: 20px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .table-responsive {
      max-height: 300px;
      overflow-y: auto;
    }
    @media (max-width: 768px) {
      .sidebar {
        position: relative;
        width: 100%;
        height: auto;
      }
      .main-content {
        margin-left: 0;
        width: 100%;
        padding: 1rem;
      }
    }
  </style>
</head>
<body>

<?php include '../includes/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="p-4">
    <h2 class="mb-2 fs-1 text-center">Admin Dashboard</h2>
    <div class="p-3 fs-6">
      <h1 class="fs-3">Welcome, <?= htmlspecialchars($_SESSION['name']) ?> (Admin)</h1>
    </div>

    <!-- Cards -->
    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="card bg-primary text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-laptop"></i> Total Laptops</h5>
            <h2><?= $totalLaptops ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-success text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-user-graduate"></i> Total Students</h5>
            <h2><?= $totalStudents ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-info text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-door-open"></i> Laptops Inside</h5>
            <h2><?= $currentlyInside ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-danger text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-door-closed"></i> Laptops Outside</h5>
            <h2><?= $currentlyOutside ?></h2>
          </div>
        </div>
      </div>
    </div>

    <!-- Graphs -->
    <div class="row">
      <div class="col-md-6">
        <div class="chart-container">
          <h5 class="text-center">Laptop Movements (Daily - Last 7 Days)</h5>
          <canvas id="dailyChart"></canvas>
        </div>
      </div>
      <div class="col-md-6" >
        <div class="chart-container" >
          <h5 class="text-center">Laptop Movements (Monthly - Last 6 Months)</h5>
          <canvas id="monthlyChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Recent Issue Reports -->
    <div class="card mt-5">
      <div class="card-header bg-dark text-white">
        <i class="fas fa-exclamation-triangle"></i> Recent Issue Reports from Security
      </div>
      <div class="card-body table-responsive">
        <?php if ($reports->num_rows > 0): ?>
          <table class="table table-bordered table-striped">
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

<script>
const dailyChart = new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($days) ?>,
        datasets: [
            { label: 'Entry', data: <?= json_encode($entryDaily) ?>, backgroundColor: 'orange' },
            { label: 'Exit', data: <?= json_encode($exitDaily) ?>, backgroundColor: 'blue' },
            { label: 'Total Inside', data: <?= json_encode($totalDaily) ?>, backgroundColor: 'green' }
        ]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

const monthlyChart = new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
            { label: 'Entry', data: <?= json_encode($entryMonthly) ?>, backgroundColor: 'orange' },
            { label: 'Exit', data: <?= json_encode($exitMonthly) ?>, backgroundColor: 'blue' },
            { label: 'Total Inside', data: <?= json_encode($totalMonthly) ?>, backgroundColor: 'green' }
        ]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
