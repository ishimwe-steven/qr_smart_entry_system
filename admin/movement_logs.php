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
    color: black;
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
  .btn-danger {
    background: linear-gradient(45deg, #dc3545, #ff4d4d);
    border: none;
    border-radius: 0.5rem;
    font-weight: 500;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .btn-danger:hover {
    background: linear-gradient(45deg, #b02a37, #ff4d4d);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
  }
  .btn i {
    margin-right: 0.3rem;
  }
  .badge.bg-success {
    background: linear-gradient(45deg, #28a745, #34c759) !important;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    font-weight: 500;
  }
  .badge.bg-danger {
    background: linear-gradient(45deg, #dc3545, #ff4d4d) !important;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    font-weight: 500;
  }
  .dataTables_wrapper .dataTables_filter {
    margin-bottom: 1rem;
    margin-top:1rem;
    margin-right:1rem;
    text-align: left;
  }
  .dataTables_wrapper .dataTables_filter input {
    border-radius: 0.5rem;
    border: 1px solid #2c3e50;
    padding: 0.5rem;
    background: #ffffff;
    color: black;
    width: 200px;
    transition: border-color 0.3s ease;
  }
  .dataTables_wrapper .dataTables_filter input:focus {
    border-color: #00c4ff;
    outline: none;
    box-shadow: 0 0 5px rgba(0, 196, 255, 0.5);
  }
  .dataTables_wrapper .dataTables_filter label {
    color: #2c3e50;
    font-weight: 500;
  }
  .dataTables_wrapper .dataTables_paginate .paginate_button {
    background: #ffffff;
    border: 1px solid #ced4da;
    border-radius: 0.3rem;
    color: #2c3e50 !important;
    margin: 0 0.2rem;
    transition: background 0.3s ease, color 0.3s ease;
  }
  .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: linear-gradient(45deg, #007bff, #00c4ff);
    color: #ffffff !important;
    border-color: transparent;
  }
  .dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: linear-gradient(45deg, #007bff, #00c4ff);
    color: #ffffff !important;
    border-color: transparent;
  }
  @media (max-width: 768px) {
    .flex-grow-1 {
      margin-left: 0;
      padding: 1rem;
    }
    .table-responsive {
      width: 100%;
    }
    h2 {
      font-size: 2rem;
    }
  }
</style>
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
