<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';

// Handle add-other form post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_other') {
    $first_name   = trim($_POST['first_name'] ?? '');
    $last_name    = trim($_POST['last_name'] ?? '');
    $national_id  = trim($_POST['national_id'] ?? '') ?: null;
    $role         = trim($_POST['role'] ?? '');
    $department   = trim($_POST['department'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');

    $laptop_brand    = trim($_POST['laptop_brand'] ?? '');
    $laptop_serial   = trim($_POST['laptop_serial'] ?? '');

    // Basic validation
    $errors = [];
    if ($first_name === '') $errors[] = "First name is required.";
    if ($last_name === '') $errors[] = "Last name is required.";
    if ($role === '') $errors[] = "Role is required (Lecturer / Staff / Guest).";

    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Insert into others
            $ins = $conn->prepare("INSERT INTO others (first_name, last_name, national_id, role, department, phone, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$ins) throw new Exception("Prepare failed (others insert): " . $conn->error);
            $ins->bind_param("sssssss", $first_name, $last_name, $national_id, $role, $department, $phone, $email);
            if (!$ins->execute()) throw new Exception("Execute failed (others insert): " . $ins->error);
            $other_id = (int)$conn->insert_id;
            $ins->close();

            // If laptop details provided, insert laptop tied to this other
            if ($laptop_brand !== '' || $laptop_serial !== '') {
                // detect columns in laptops table
                $hasOwnerType = (bool) $conn->query("
                    SELECT COUNT(*) AS c
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'laptops'
                      AND COLUMN_NAME = 'owner_type'
                ")->fetch_assoc()['c'];

                $hasOtherId = (bool) $conn->query("
                    SELECT COUNT(*) AS c
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'laptops'
                      AND COLUMN_NAME = 'other_id'
                ")->fetch_assoc()['c'];

                // Build insert depending on schema
                if ($hasOwnerType && $hasOtherId) {
                    $insL = $conn->prepare("INSERT INTO laptops (owner_type, other_id, student_id, brand, serial_number) VALUES ('other', ?, NULL, ?, ?)");
                    if (!$insL) throw new Exception("Prepare failed (laptops insert with owner_type): " . $conn->error);
                    $insL->bind_param("iss", $other_id, $laptop_brand, $laptop_serial);
                } elseif ($hasOtherId) {
                    // older schema: other_id exists but no owner_type
                    $insL = $conn->prepare("INSERT INTO laptops (other_id, student_id, brand, serial_number) VALUES (?, NULL, ?, ?)");
                    if (!$insL) throw new Exception("Prepare failed (laptops insert with other_id): " . $conn->error);
                    $insL->bind_param("iss", $other_id, $laptop_brand, $laptop_serial);
                } else {
                    // fallback: insert laptop with brand/serial (student_id NULL)
                    // (This may fail if student_id is NOT NULL in your schema)
                    $insL = $conn->prepare("INSERT INTO laptops (student_id, brand, serial_number) VALUES (NULL, ?, ?)");
                    if (!$insL) throw new Exception("Prepare failed (laptops fallback insert): " . $conn->error);
                    $insL->bind_param("ss", $laptop_brand, $laptop_serial);
                }

                if (!$insL->execute()) {
                    // If serial_number has unique constraint and duplicate is attempted, this will throw
                    throw new Exception("Execute failed (laptops insert): " . $insL->error);
                }
                $insL->close();
            }

            $conn->commit();
            $_SESSION['success_message'] = "Other added successfully.";
            header("Location: manage_others.php");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error creating other: " . $e->getMessage();
            header("Location: manage_others.php");
            exit;
        }
    } else {
        // validation errors -> put into session and redirect back
        $_SESSION['error_message'] = implode(' ', $errors);
        header("Location: manage_others.php");
        exit;
    }
}

// Determine whether laptops table has other_id / owner_type (for safe joins)
$hasOwnerType = (bool) $conn->query("
    SELECT COUNT(*) AS c
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'laptops'
      AND COLUMN_NAME = 'owner_type'
")->fetch_assoc()['c'];

$hasOtherId = (bool) $conn->query("
    SELECT COUNT(*) AS c
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'laptops'
      AND COLUMN_NAME = 'other_id'
")->fetch_assoc()['c'];

// Build the list query
if ($hasOtherId) {
    // if owner_type column exists, prefer to ensure we only link laptops with owner_type='other'
    if ($hasOwnerType) {
        $sql = "
            SELECT o.id, o.first_name, o.last_name, o.national_id, o.role, o.department, o.email, o.phone,
                   l.id AS laptop_id, l.brand AS laptop_brand, l.serial_number
            FROM others o
            LEFT JOIN laptops l ON l.other_id = o.id AND l.owner_type = 'other'
            ORDER BY o.id DESC
        ";
    } else {
        $sql = "
            SELECT o.id, o.first_name, o.last_name, o.national_id, o.role, o.department, o.email, o.phone,
                   l.id AS laptop_id, l.brand AS laptop_brand, l.serial_number
            FROM others o
            LEFT JOIN laptops l ON l.other_id = o.id
            ORDER BY o.id DESC
        ";
    }
} else {
    // laptops table doesn't have other_id - we can still list others but without laptop data
    $sql = "
        SELECT o.id, o.first_name, o.last_name, o.national_id, o.role, o.department, o.email, o.phone,
               NULL AS laptop_id, NULL AS laptop_brand, NULL AS serial_number
        FROM others o
        ORDER BY o.id DESC
    ";
}

$result = $conn->query($sql);
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

<style>
body { background-color: #f4f6f9; }
.card { border: none; border-radius: 0.75rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,.15); }
.flex-grow-1 {
  margin-left: 300px;
  padding: 2rem;
}
h2 {
  font-size: 2.5rem;
  font-weight: 700;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
.btn-primary {
  background: linear-gradient(45deg, #007bff, #00c4ff);
  border: none;
  border-radius: 0.5rem;
  padding: 0.75rem 1.5rem;
  font-weight: 500;
  margin-bottom:1rem;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.btn-primary:hover {
  background: linear-gradient(45deg, #0056b3, #0096cc);
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
}
.btn-success {
  background: linear-gradient(45deg, #28a745, #34c759);
  border: none;
  border-radius: 0.5rem;
  padding: 0.75rem 1.5rem;
  font-weight: 500;
  margin-bottom:1rem;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.btn-success:hover {
  background: linear-gradient(45deg, #218838, #2cb050);
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
}
.btn-secondary{
    background: linear-gradient(45deg, #7a9480ff, #86978aff);
  border: none;
  border-radius: 0.5rem;
  padding: 0.75rem 1.5rem;
  font-weight: 500;
  margin-bottom:1rem;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.btn-secondary:hover{
  background: linear-gradient(45deg, #7a9480ff, #86978aff);
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
}
.table-responsive {
  width: 97%;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 1rem;
  box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
  overflow: hidden;
}
.table {
  margin-bottom: 0;
  color: #2c3e50;
}
.table thead.table-dark {
  background: linear-gradient(45deg, #2c3e50, #34495e);
  color: #ffffff;
}
.table tbody tr {  
  transition: background 0.3s ease;
}
.table tbody tr:hover {
  background: #f1f3f5;
}
.btn-warning { background: linear-gradient(45deg, #ffc107, #ffca2c); border: none; }
.btn-danger { background: linear-gradient(45deg, #dc3545, #ff4d4d); border: none; }
@media (max-width: 768px) {
  .flex-grow-1 {
    margin-left: 0;
    padding: 1rem;
  }
  .table-responsive {
    width: 100%;
  }
  h2 { font-size: 2rem; }
}
</style>

<div class="d-flex">
  <?php include '../includes/admin_sidebar.php'; ?>

  <div class="flex-grow-1 p-4" style="margin-left:300px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>Manage Others (Lecturers / Staff / Guests)</h2>

    </div>
    <div class="" style="margin-top:60px;">
        <a href="add_other.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add Other</a>
        <a href="add_laptop_other.php" class="btn btn-success"><i class="fas fa-laptop"></i> Add Laptop</a>

        <!-- New Import Button -->
        <button class="btn btn-info1 custom-white-btn" data-bs-toggle="modal" data-bs-target="#importModal">
          <a class="btn btn-primary">  <i class="fas fa-file-import"></i> Import other (Excel)</a>
        </button>
    </div>
<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="import_others.php" method="POST" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importModalLabel"><i class="fas fa-file-import"></i> Import Others from Excel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <p>Upload an Excel (.xlsx, .xls) or CSV file with these columns:</p>
        <ul>
          <li>First Name</li>
          <li>Last Name</li>
          <li>National ID (optional)</li>
          <li>Role (Lecturer, Staff, Guest)</li>
          <li>Department</li>
          <li>Email</li>
          <li>Phone</li>
          <li>Laptop Brand (optional)</li>
          <li>Laptop Serial (optional)</li>
        </ul>
        <input type="file" name="file" accept=".xlsx,.xls,.csv" class="form-control" required>
      </div>

      <div class="modal-footer">
        <button type="submit" name="import" class="btn btn-success">
          <i class="fas fa-upload"></i> Import
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

   

    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="table-responsive" style="width:97%">
      <table id="othersTable" class="table table-bordered table-striped" style="width:100%;">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>National ID</th>
            <th>Role</th>
            <th>Department</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Laptop Brand</th>
            <th>Laptop Serial</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['id']) ?></td>
              <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
              <td><?= htmlspecialchars($row['national_id']) ?></td>
              <td><?= htmlspecialchars($row['role']) ?></td>
              <td><?= htmlspecialchars($row['department']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['phone']) ?></td>
              <td><?= htmlspecialchars($row['laptop_brand']) ?></td>
              <td><?= htmlspecialchars($row['serial_number']) ?></td>
              <td>
                <a href="edit_other.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning mb-1"><i class="fas fa-edit"></i> Edit</a>
                <a href="delete_other.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fas fa-trash"></i> Delete</a>
                <?php if (!empty($row['laptop_id'])): ?>
                  <a href="generate_qr.php?laptop_id=<?= $row['laptop_id'] ?>" class="btn btn-sm btn-info" target="_blank"><i class="fas fa-qrcode"></i> QR Code</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- DataTables JS + Bootstrap (for modal) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
  $('#othersTable').DataTable({
    pageLength: 15,
    lengthChange: false,
    language: { search: "Search others:" }
  });
});
</script>
