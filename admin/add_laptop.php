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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
    body { background-color: #f4f6f9; }
    .card { border: none; border-radius: 0.75rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,.15); }
    .d-flex {
      
    }
    .flex-grow-1 {
      margin-left: 300px;
      padding: 2rem;
      
    }
    h2 {
      font-size: 2.5rem;
      font-weight: 700;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    
      margin-bottom: 2rem;
    }
    .form-label {
      color: #2c3e50;
      font-weight: 500;
    }
    .form-control, .form-select {
     color: #a0aec0;
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
    .btn-success {
      background: linear-gradient(45deg, #28a745, #34c759);
      border: none;
      border-radius: 0.5rem;
      font-weight: 500;
      padding: 0.75rem 1.5rem;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .btn-success:hover {
      background: linear-gradient(45deg, #218838, #2cb050);
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
    }
    .btn-secondary {
      background: linear-gradient(45deg, #6c757d, #82909d);
      border: none;
      border-radius: 0.5rem;
      font-weight: 500;
      padding: 0.75rem 1.5rem;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .btn-secondary:hover {
      background: linear-gradient(45deg, #5a6268, #6f7b86);
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
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
    form {
      max-width: 600px;
      margin: 110px auto 0 auto;
      background: rgba(255, 255, 255, 0.95);
      padding: 2rem;
      border-radius: 1rem;
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
    }
    @media (max-width: 768px) {
      .flex-grow-1 {
        margin-left: 0;
        padding: 1rem;
      }
      form {
        max-width: 100%;
        margin: 60px auto 0 auto;
        padding: 1rem;
      }
      h2 {
        font-size: 2rem;
      }
    }
</style>

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
        <input type="text" name="brand" placeholder="Enter Brand"class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Serial Number</label>
        <input type="text" name="serial_number"placeholder="Enter Serial Number" class="form-control" required>
      </div>

      <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Laptop</button>
      <a href="manage_students.php" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
