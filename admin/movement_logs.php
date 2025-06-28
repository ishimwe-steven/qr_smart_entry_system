<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';

// Fetch all movement logs
$sql = "
  SELECT m.id, m.status, m.entry_time, m.exit_time, 
         s.first_name, s.last_name, s.reg_no,
         l.brand, l.serial_number,
         u.name AS guard_name
  FROM laptop_movements m
  LEFT JOIN laptops l ON m.laptop_id = l.id
  LEFT JOIN students s ON l.student_id = s.id
  LEFT JOIN users u ON m.security_guard_id = u.id
  ORDER BY m.id DESC
";
$result = $conn->query($sql);
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

<div class="d-flex">
  <?php include '../includes/admin_sidebar.php'; ?>

  <div class="flex-grow-1 p-4" style="margin-left:300px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
     <h2>Movement Logs</h2>
     
    </div>
     <div class=""   style="margin-top:95px;">
            
         </div>
<div class="table-responsive" style="width:97%">
    <table id="movementsTable" class="table table-bordered table-striped" style="width:100%;">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Student</th>
          <th>Reg No</th>
          <th>Brand</th>
          <th>Serial</th>
          <th>Status</th>
          <th>Entry Time</th>
          <th>Exit Time</th>
          <th>Security</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
            <td><?= htmlspecialchars($row['reg_no']) ?></td>
            <td><?= htmlspecialchars($row['brand']) ?></td>
            <td><?= htmlspecialchars($row['serial_number']) ?></td>
            <td>
              <span class="badge <?= $row['status'] === 'IN' ? 'bg-success' : 'bg-danger' ?>">
                <?= $row['status'] ?>
              </span>
            </td>
            <td><?= htmlspecialchars($row['entry_time']) ?></td>
            <td><?= htmlspecialchars($row['exit_time']) ?></td>
            <td><?= htmlspecialchars($row['guard_name']) ?></td>
            <td>
              <a href="delete_movement.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this log?')">
                <i class="fas fa-trash"></i> Delete
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
        </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- jQuery + DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
  $(document).ready(function() {
    $('#movementsTable').DataTable({
      pageLength: 30,
      lengthChange: false,
      language: {
        search: "Search records:"
      }
    });
  });
</script>
