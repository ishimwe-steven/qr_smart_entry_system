<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'security') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';

$info = null;

// Handle confirm action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_action'])) {
    $action = $_POST['confirm_action'];
    $laptop_id = intval($_POST['laptop_id']);
    $guard_id = $_SESSION['user_id'];
    $report_text = isset($_POST['report_issue']) ? trim($_POST['report_issue']) : '';

    if ($action === 'IN') {
        $stmt = $conn->prepare("
            INSERT INTO laptop_movements (laptop_id, security_guard_id, status, entry_time)
            VALUES (?, ?, 'IN', NOW())
        ");
        $stmt->bind_param("ii", $laptop_id, $guard_id);
    } elseif ($action === 'OUT') {
        $stmt = $conn->prepare("
            INSERT INTO laptop_movements (laptop_id, security_guard_id, status, exit_time)
            VALUES (?, ?, 'OUT', NOW())
        ");
        $stmt->bind_param("ii", $laptop_id, $guard_id);
    } elseif ($action === 'REPORT') {
        $stmt = $conn->prepare("INSERT INTO issue_reports (laptop_id, issue_description, reported_by) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $laptop_id, $report_text, $guard_id);
    } else {
        $_SESSION['error_message'] = "Invalid action.";
        header("Location: dashboard.php");
        exit;
    }

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Action '$action' recorded successfully.";
    } else {
        $_SESSION['error_message'] = "Error recording action!";
    }

    header("Location: dashboard.php");
    exit;
}

// Handle QR scan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_qr'])) {
    $input = trim($_POST['scan_qr']);
    if (preg_match('/laptop_id=(\d+)/', $input, $matches)) {
        $laptop_id = intval($matches[1]);
    } else {
        $laptop_id = intval($input);
    }

    $stmt = $conn->prepare("
        SELECT s.id AS student_id, s.first_name, s.last_name, s.reg_no, s.department, s.picture,
               l.id AS laptop_id, l.brand, l.serial_number
        FROM laptops l
        LEFT JOIN students s ON l.student_id = s.id
        WHERE l.id = ?
    ");
    $stmt->bind_param("i", $laptop_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $info = $res->fetch_assoc();

    if ($info) {
        $stmt2 = $conn->prepare("SELECT status FROM laptop_movements WHERE laptop_id = ? ORDER BY id DESC LIMIT 1");
        $stmt2->bind_param("i", $info['laptop_id']);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $last = $res2->fetch_assoc();
        $info['last_status'] = $last['status'] ?? 'OUT';
    } else {
        $_SESSION['error_message'] = "Laptop not found!";
        header("Location: dashboard.php");
        exit;
    }
}

// Count helpers
function getLaptopStatusCount($conn, $status) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS count
        FROM laptop_movements m
        LEFT JOIN (
            SELECT laptop_id, MAX(id) AS last_id
            FROM laptop_movements
            GROUP BY laptop_id
        ) t ON m.laptop_id = t.laptop_id AND m.id = t.last_id
        WHERE m.status = ?
    ");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['count'];
}

$inside_count = getLaptopStatusCount($conn, 'IN');
$today_in_count = $conn->query("SELECT COUNT(*) AS count FROM laptop_movements WHERE DATE(entry_time)=CURDATE() AND status='IN'")->fetch_assoc()['count'];
$today_out_count = $conn->query("SELECT COUNT(*) AS count FROM laptop_movements WHERE DATE(exit_time)=CURDATE() AND status='OUT'")->fetch_assoc()['count'];

$logs = $conn->query("
    SELECT m.*, s.first_name, s.last_name, s.reg_no, l.brand, l.serial_number
    FROM laptop_movements m
    LEFT JOIN laptops l ON m.laptop_id = l.id
    LEFT JOIN students s ON l.student_id = s.id
    WHERE DATE(m.entry_time) = CURDATE() OR DATE(m.exit_time) = CURDATE()
    ORDER BY m.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Security Dashboard - Smart Entry System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <style>
   body { background-color: #f4f6f9; }
    .card { border: none; border-radius: 0.75rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,.15); }
    .d-flex {
    
    }
    .flex-grow-1 {
      margin-left: 250px; /* Adjusted to match assumed sidebar width */
      padding: 2rem;
      width: 100%;
    }
    h2 {
      font-size: 2.5rem;
      font-weight: 700;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
      
      margin-bottom: 1.5rem;
    }
    h4 {
      font-size: 1.5rem;
      font-weight: 600;
    
      margin-top: 1.5rem;
      margin-bottom: 1rem;
    }
    .form-label {
      color: #2c3e50;
      font-weight: 500;
    }
    .form-control, .form-select {
    
      color: #ffffff;
      border: 1px solid #2c3e50;
      border-radius: 0.5rem;
      padding: 0.5rem;
      transition: border-color 0.3s ease;
    }
    .form-control:focus, .form-select:focus {
      border-color: #00c4ff;
      outline: none;
      box-shadow: 0 0 5px rgba(0, 196, 255, 0.5);
    }
    .form-control::placeholder {
      color: #a0aec0;
    }
    .btn btn-outline-secondary{
      background:red;
    }
    .btn-success {
      background: linear-gradient(45deg, #28a745, #34c759);
      border: none;
      border-radius: 0.5rem;
      font-weight: 500;
      padding: 0.5rem 1rem;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .btn-success:hover {
      background: linear-gradient(45deg, #218838, #2cb050);
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
    }
    .btn-danger {
      background: linear-gradient(45deg, #dc3545, #ff4d4d);
      border: none;
      border-radius: 0.5rem;
      font-weight: 500;
      padding: 0.5rem 1rem;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .btn-danger:hover {
      background: linear-gradient(45deg, #c82333, #e03e3e);
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
    }
    .btn-warning {
      background: linear-gradient(45deg, #ffc107, #ffd84d);
      border: none;
      border-radius: 0.5rem;
      font-weight: 500;
      padding: 0.5rem 1rem;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .btn-warning:hover {
      background: linear-gradient(45deg, #e0a800, #f0c107);
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
    }
    .btn-outline-secondary {
      border-color: #6c757d;
      color: white;
      border-radius: 0.5rem;
      padding: 0.5rem 0.75rem;
      background:linear-gradient(45deg, #6c757d, #8a959f);
      margin-left:26rem;
      margin-top:-4.2rem;
    }
    .btn-outline-secondary:hover {
      background: linear-gradient(45deg, #6c757d, #8a959f);
      color: #ffffff;
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
    }
    .btn-outline-danger {
      border-color: #dc3545;
      color: #dc3545;
      border-radius: 0.5rem;
      padding: 0.25rem 0.75rem;
      transition: background 0.3s ease, color 0.3s ease, transform 0.3s ease;
    }
    .btn-outline-danger:hover {
      background: linear-gradient(45deg, #dc3545, #ff4d4d);
      color: #ffffff;
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
    }
    .btn i {
      margin-right: 0.5rem;
    }
    .alert-success {
      background: linear-gradient(45deg, #28a745, #34c759);
      border: none;
      border-radius: 0.5rem;
      color: #ffffff;
      font-weight: 500;
      padding: 1rem;
      margin-bottom: 1rem;
    }
    .alert-danger {
      background: linear-gradient(45deg, #dc3545, #ff4d4d);
      border: none;
      border-radius: 0.5rem;
      color: #ffffff;
      font-weight: 500;
      padding: 1rem;
      margin-bottom: 1rem;
    }
    .card {
      background: rgba(255, 255, 255, 0.95);
      border: none;
      border-radius: 1rem;
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
      margin-bottom: 2rem;
    }
    .card-body {
      padding: 1.5rem;
    }
    .card.bg-success, .card.bg-info, .card.bg-danger {
     
      
    }
    .card.bg-success h5, .card.bg-info h5, .card.bg-danger h5 {
      color: white;
      font-weight: 600;
    }
    .card.bg-success h2, .card.bg-info h2, .card.bg-danger h2 {
      color:white;
      font-weight: 700;
    }
    .card.bg-success i, .card.bg-info i, .card.bg-danger i {
      margin-right: 0.5rem;
    }
    .img-thumbnail {
      border-radius: 0.5rem;
      border: 1px solid #2c3e50;
    }
    .badge.bg-success {
      background: linear-gradient(45deg, #28a745, #34c759) !important;
    }
    .badge.bg-danger {
      background: linear-gradient(45deg, #dc3545, #ff4d4d) !important;
    }
    .badge.bg-warning {
      background: linear-gradient(45deg, #ffc107, #ffd84d) !important;
    }
    .table {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 0.5rem;
      overflow: hidden;
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
    }
    .table-dark {
      background: #2c3e50;
      color: #ffffff;
    }
    .table-dark th {
      font-weight: 600;
    }
    .table-striped tbody tr:nth-of-type(odd) {
      background: rgba(44, 62, 80, 0.05);
    }
    .table-bordered {
      border: 1px solid #2c3e50;
    }
    .table-bordered th, .table-bordered td {
      border: 1px solid #2c3e50;
    }
    .table tbody tr:hover {
      background: rgba(0, 123, 255, 0.1);
    }
    form {
      max-width: 400px;
      margin-bottom: 1.5rem;
    }
    @media (max-width: 768px) {
      .flex-grow-1 {
        margin-left: 0;
        padding: 1rem;
      }
      form {
        max-width: 100%;
      }
      h2 {
        font-size: 2rem;
      }
      h4 {
        font-size: 1.25rem;
      }
      .card {
        margin-bottom: 1rem;
      }
      .table {
        font-size: 0.9rem;
      }
      .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
      }
    }
  </style>
</head>
<body>
<div class="d-flex">
  <?php include '../includes/security_sidebar.php'; ?>

  <div class="flex-grow-1 p-4">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h2>
    <a href="../auth/logout.php" class="btn btn-sm btn-outline-danger mb-3"><i class="fas fa-sign-out-alt"></i> Logout</a>

    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <form method="POST">
      <label for="scan_qr" class="form-label">Scan or paste QR code content:</label>
      <input type="text" name="scan_qr" id="scan_qr" class="form-control" placeholder="Paste QR link or laptop ID" autofocus>
    </form>

    <?php if ($info): ?>
      <div class="card mb-4">
        <div class="card-body">
          <div class="mb-2">
            <img src="../Uploads/<?= htmlspecialchars($info['picture']) ?>" width="120" class="img-thumbnail">
          </div>
          <p><strong>Name:</strong> <?= htmlspecialchars($info['first_name'] . ' ' . $info['last_name']) ?></p>
          <p><strong>Reg No:</strong> <?= htmlspecialchars($info['reg_no']) ?></p>
          <p><strong>Department:</strong> <?= htmlspecialchars($info['department']) ?></p>
          <p><strong>Laptop:</strong> <?= htmlspecialchars($info['brand']) ?> (<?= htmlspecialchars($info['serial_number']) ?>)</p>
          <p><strong>Last Status:</strong>
            <span class="badge <?= $info['last_status'] === 'IN' ? 'bg-success' : ($info['last_status'] === 'OUT' ? 'bg-danger' : 'bg-warning') ?>">
              <?= $info['last_status'] ?>
            </span>
          </p>

          <form method="POST">
            <input type="hidden" name="laptop_id" value="<?= $info['laptop_id'] ?>">
            <textarea name="report_issue" class="form-control mb-2" rows="2" placeholder="Describe any issue (optional)..."></textarea>
            <button name="confirm_action" value="IN" class="btn btn-success me-1" <?= $info['last_status'] === 'IN' ? 'disabled' : '' ?>>Confirm Entry</button>
            <button name="confirm_action" value="OUT" class="btn btn-danger me-1" <?= $info['last_status'] === 'OUT' ? 'disabled' : '' ?>>Confirm Exit</button>
            <button name="confirm_action" value="REPORT" class="btn btn-warning me-1">Report Issue</button>
            <a href="dashboard.php" class="btn btn-outline-secondary ">Cancel</a>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <div class="card bg-success shadow">
          <div class="card-body">
            <h5><i class="fas fa-laptop"></i> Laptops Inside Now</h5>
            <h2><?= $inside_count ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-info shadow">
          <div class="card-body">
            <h5><i class="fas fa-door-open"></i> Entries Today</h5>
            <h2><?= $today_in_count ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-danger shadow">
          <div class="card-body">
            <h5><i class="fas fa-door-closed"></i> Exits Today</h5>
            <h2><?= $today_out_count ?></h2>
          </div>
        </div>
      </div>
    </div>

    <h4>Today's Movement Logs</h4>
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Student</th>
            <th>Reg No</th>
            <th>Brand</th>
            <th>Serial</th>
            <th>Status</th>
            <th>Time</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $logs->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
            <td><?= htmlspecialchars($row['reg_no']) ?></td>
            <td><?= htmlspecialchars($row['brand']) ?></td>
            <td><?= htmlspecialchars($row['serial_number']) ?></td>
            <td>
              <span class="badge <?= $row['status'] === 'IN' ? 'bg-success' : ($row['status'] === 'OUT' ? 'bg-danger' : 'bg-warning') ?>">
                <?= $row['status'] ?>
              </span>
            </td>
            <td>
              <?= $row['status'] === 'IN'
                ? htmlspecialchars($row['entry_time'])
                : ($row['status'] === 'OUT' ? htmlspecialchars($row['exit_time']) : '-') ?>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js">
  
</script>
</body>
</html>