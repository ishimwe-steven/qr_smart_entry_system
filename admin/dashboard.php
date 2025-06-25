<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';

// Example: Get counts (update queries as needed)
$totalLaptops = $conn->query("SELECT COUNT(*) AS count FROM laptops")->fetch_assoc()['count'];
$totalStudents = $conn->query("SELECT COUNT(*) AS count FROM students")->fetch_assoc()['count'];
$currentlyInside = $conn->query("SELECT COUNT(*) AS count FROM laptop_movements WHERE status='IN'")->fetch_assoc()['count'];
$currentlyOutside = $conn->query("SELECT COUNT(*) AS count FROM laptop_movements WHERE status='OUT'")->fetch_assoc()['count'];

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
        body {
            background-color: #f4f6f9;
        }
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,.15);
        }
    </style>
</head>
<body>

<div class="container-fluid" style="margin-left: 300px;position:fixed;">
  <div class="p-4">
<h2 class="mb-2 fs-1 text-center">Admin Dashboard</h2>
     <div class=" p-3 fs-6">
    <h1 class="fs-3">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (Admin)</h1>
    
  </div>
    <div class="row g-4">
      
      <div class="col-md-3" style="width:400px">
        <div class="card bg-primary text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-laptop"></i> Total Laptops</h5>
            <h2><?= $totalLaptops ?></h2>
            <a href="manage_laptops.php" class="text-white">More info <i class="fas fa-arrow-circle-right"></i></a>
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
  </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
