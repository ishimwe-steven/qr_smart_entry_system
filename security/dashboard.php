<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'security') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';

$info = null;

// Handle confirm action (entry/exit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_action'])) {
    $action = $_POST['confirm_action'];
    $laptop_id = intval($_POST['laptop_id']);
    $guard_id = $_SESSION['user_id'];

    if ($action === 'IN') {
        $stmt = $conn->prepare("
            INSERT INTO laptop_movements (laptop_id, security_guard_id, status, entry_time)
            VALUES (?, ?, 'IN', NOW())
        ");
    } else {
        $stmt = $conn->prepare("
            INSERT INTO laptop_movements (laptop_id, security_guard_id, status, exit_time)
            VALUES (?, ?, 'OUT', NOW())
        ");
    }

    $stmt->bind_param("ii", $laptop_id, $guard_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Movement recorded: $action";
    } else {
        $_SESSION['error_message'] = "Error recording movement!";
    }

    header("Location: dashboard.php");
    exit;
}

// Handle QR scan input
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_qr'])) {
    $input = trim($_POST['scan_qr']);
    if (preg_match('/id=(\d+)/', $input, $matches)) {
        $student_id = intval($matches[1]);
    } else {
        $student_id = intval($input);
    }

    $stmt = $conn->prepare("
        SELECT s.id AS student_id, s.first_name, s.last_name, s.reg_no, s.department, s.picture,
               l.id AS laptop_id, l.brand, l.serial_number
        FROM students s
        LEFT JOIN laptops l ON s.id = l.student_id
        WHERE s.id = ?
    ");
    $stmt->bind_param("i", $student_id);
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
        $_SESSION['error_message'] = "Student not found!";
        header("Location: dashboard.php");
        exit;
    }
}

// Counts
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

<div class="d-flex">
  <?php include '../includes/security_sidebar.php'; ?>

  <div class="flex-grow-1 p-4" style="margin-left:300px;">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h2>
    <a href="../auth/logout.php" class="btn btn-sm btn-outline-danger mb-3"><i class="fas fa-sign-out-alt"></i> Logout</a>

    <?php
    if (isset($_SESSION['success_message'])) {
        echo "<div class='alert alert-success'>" . $_SESSION['success_message'] . "</div>";
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo "<div class='alert alert-danger'>" . $_SESSION['error_message'] . "</div>";
        unset($_SESSION['error_message']);
    }
    ?>

    <form method="POST" class="mb-3">
      <label for="scan_qr" class="form-label">Scan or paste QR code content:</label>
      <input type="text" name="scan_qr" id="scan_qr" class="form-control" placeholder="Paste QR link or ID" style="width:400px;" autofocus>
    </form>

    <?php if ($info): ?>
      <div class="card mb-4">
        <div class="card-body">
          <div class="mb-2">
            <img src="../uploads/<?= htmlspecialchars($info['picture']) ?>" width="120" class="img-thumbnail">
          </div>
          <p><strong>Name:</strong> <?= htmlspecialchars($info['first_name'] . ' ' . $info['last_name']) ?></p>
          <p><strong>Reg No:</strong> <?= htmlspecialchars($info['reg_no']) ?></p>
          <p><strong>Department:</strong> <?= htmlspecialchars($info['department']) ?></p>
          <p><strong>Laptop:</strong> <?= htmlspecialchars($info['brand']) ?> (<?= htmlspecialchars($info['serial_number']) ?>)</p>
          <p><strong>Last Status:</strong> <span class="badge <?= $info['last_status'] === 'IN' ? 'bg-success' : 'bg-danger' ?>"><?= $info['last_status'] ?></span></p>

          <form method="POST" class="mt-2">
            <input type="hidden" name="laptop_id" value="<?= $info['laptop_id'] ?>">
            <button name="confirm_action" value="IN" class="btn btn-success" <?= $info['last_status'] === 'IN' ? 'disabled' : '' ?>>
              Confirm Entry
            </button>
            <button name="confirm_action" value="OUT" class="btn btn-danger" <?= $info['last_status'] === 'OUT' ? 'disabled' : '' ?>>
              Confirm Exit
            </button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <div class="card bg-success text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-laptop"></i> Laptops Inside Now</h5>
            <h2><?= $inside_count ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-info text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-door-open"></i> Entries Today</h5>
            <h2><?= $today_in_count ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-danger text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-door-closed"></i> Exits Today</h5>
            <h2><?= $today_out_count ?></h2>
          </div>
        </div>
      </div>
    </div>

    <h4>Today's Movement Logs</h4>
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
          <td><span class="badge <?= $row['status'] === 'IN' ? 'bg-success' : 'bg-danger' ?>"><?= $row['status'] ?></span></td>
          <td>
            <?= $row['status'] === 'IN'
              ? htmlspecialchars($row['entry_time'])
              : htmlspecialchars($row['exit_time']) ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
