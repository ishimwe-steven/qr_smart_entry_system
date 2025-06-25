<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'security') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    echo "<div class='alert alert-danger'>Invalid or missing student ID!</div>";
    exit;
}

// Get student + laptop info
$sql = "
  SELECT s.id AS student_id, s.first_name, s.last_name, s.reg_no, s.department, s.picture,
         l.id AS laptop_id, l.brand, l.serial_number
  FROM students s
  LEFT JOIN laptops l ON s.id = l.student_id
  WHERE s.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "<div class='alert alert-danger'>Student not found!</div>";
    exit;
}

$data = $result->fetch_assoc();

// Check last movement status
$sql_last = "
  SELECT status FROM laptop_movements
  WHERE laptop_id = ?
  ORDER BY id DESC LIMIT 1
";
$stmt2 = $conn->prepare($sql_last);
$stmt2->bind_param("i", $data['laptop_id']);
$stmt2->execute();
$res_last = $stmt2->get_result()->fetch_assoc();
$last_status = $res_last['status'] ?? 'OUT'; // Default assume outside if no record

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    if ($action === 'IN' && $last_status === 'IN') {
        $msg = "<div class='alert alert-warning'>Laptop already inside!</div>";
    } elseif ($action === 'OUT' && $last_status === 'OUT') {
        $msg = "<div class='alert alert-warning'>Laptop already outside!</div>";
    } else {
        $stmt3 = $conn->prepare("INSERT INTO laptop_movements (laptop_id, security_guard_id, status, entry_time) VALUES (?, ?, ?, NOW())");
        $stmt3->bind_param("iis", $data['laptop_id'], $_SESSION['user_id'], $action);
        if ($stmt3->execute()) {
            $msg = "<div class='alert alert-success'>Movement recorded successfully!</div>";
            $last_status = $action; // Update status for button state
        } else {
            $msg = "<div class='alert alert-danger'>Error recording movement!</div>";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>
<div class="d-flex">
  <?php include '../includes/security_sidebar.php'; ?>

  <div class="flex-grow-1 p-4" style="margin-left:300px; max-width:800px;">
    <h2>Verify Laptop</h2>
    <?= $msg ?? '' ?>

    <div class="card mb-4">
      <div class="card-body">
        <div class="mb-3 text-center">
          <img src="../uploads/<?= htmlspecialchars($data['picture']) ?>" alt="Student Picture" style="width:150px;" class="img-thumbnail">
        </div>
        <p><strong>Name:</strong> <?= htmlspecialchars($data['first_name'] . ' ' . $data['last_name']) ?></p>
        <p><strong>Reg No:</strong> <?= htmlspecialchars($data['reg_no']) ?></p>
        <p><strong>Department:</strong> <?= htmlspecialchars($data['department']) ?></p>
        <p><strong>Laptop:</strong> <?= htmlspecialchars($data['brand']) ?> (<?= htmlspecialchars($data['serial_number']) ?>)</p>
        <p><strong>Last Status:</strong> 
          <span class="badge <?= $last_status === 'IN' ? 'bg-success' : 'bg-danger' ?>">
            <?= $last_status ?>
          </span>
        </p>

        <form method="POST" class="mt-3">
          <button name="action" value="IN" class="btn btn-success me-2" <?= $last_status === 'IN' ? 'disabled' : '' ?>>
            <i class="fas fa-door-open"></i> Confirm Entry
          </button>
          <button name="action" value="OUT" class="btn btn-danger" <?= $last_status === 'OUT' ? 'disabled' : '' ?>>
            <i class="fas fa-door-closed"></i> Confirm Exit
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
