<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';

$message = '';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id']);
    $brand = trim($_POST['brand']);
    $serial_number = trim($_POST['serial_number']);

    // Check if serial number exists
    $stmt_check = $conn->prepare("SELECT id FROM laptops WHERE serial_number = ?");
    $stmt_check->bind_param("s", $serial_number);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $message = "<div class='alert alert-danger'>⚠ This serial number already exists!</div>";
    } else {
        // Insert laptop
        $stmt = $conn->prepare("INSERT INTO laptops (student_id, brand, serial_number) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $student_id, $brand, $serial_number);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>✅ Laptop added successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>❌ Error adding laptop: " . htmlspecialchars($stmt->error) . "</div>";
        }
    }
}

// Fetch students for dropdown
$students = $conn->query("SELECT id, reg_no, first_name, last_name FROM students ORDER BY first_name, last_name");
?>

<div class="d-flex">
  <?php include '../includes/admin_sidebar.php'; ?>

  <div class="flex-grow-1 p-4" style="margin-left:300px;">
    <h2>Add Laptop</h2>

    <?= $message ?>

    <form method="POST" style="max-width:600px;">
      <div class="mb-3">
        <label class="form-label">Select Student</label>
        <select name="student_id" class="form-control" required>
          <option value="">-- Select Student --</option>
          <?php while ($row = $students->fetch_assoc()): ?>
            <option value="<?= $row['id'] ?>">
              <?= htmlspecialchars($row['reg_no']) ?> - <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Laptop Brand</label>
        <input type="text" name="brand" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Serial Number</label>
        <input type="text" name="serial_number" class="form-control" required>
      </div>

      <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Laptop</button>
      <a href="manage_students.php" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
