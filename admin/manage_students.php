<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

<!-- style-->
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
  .btn-info {
    background: linear-gradient(45deg, #17a2b8, #1ac7e0);
    border: none;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .btn-info:hover {
    background: linear-gradient(45deg, #138496, #17b2d3);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);
  }
  .btn-primary i, .btn-success i, .btn-info i {
    margin-right: 0.5rem;
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
  .btn-warning {
    background: linear-gradient(45deg, #ffc107, #ffca2c);
    border: none;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .btn-warning:hover {
    background: linear-gradient(45deg, #e0a800, #ffca2c);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
  }
  .btn-danger {
    background: linear-gradient(45deg, #dc3545, #ff4d4d);
    border: none;
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
  .dataTables_wrapper .dataTables_filter {
    margin-bottom: 1rem;
    margin-top:1rem;
    margin-right:1rem;
    text-align: left;
  }
  .dataTables_wrapper .dataTables_filter input {
    border-radius: 0.5rem;
    border: 1px solidrgb(0, 0, 0);
    padding: 0.5rem;
    background:#ffffff;
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
      <h2>Manage Laptops</h2>
     
    </div>
     <div class=""   style="margin-top:60px;">
            <a href="add_student.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add Student</a>
             <a href="add_laptop.php" class="btn btn-success"><i class="fas fa-laptop"></i> Add Laptop</a>
         </div>

    <div class="table-responsive" style="width:97%">
      <table id="studentsTable" class="table table-bordered table-striped" style="width:100%;">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Reg No</th>
            <th>Department</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Laptop Brand</th>
            <th>Laptop Serial</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $sql = "
            SELECT s.id, s.first_name, s.last_name, s.reg_no, s.department, s.email, s.phone,
                   l.id AS laptop_id, l.brand AS laptop_brand, l.serial_number
            FROM students s
            LEFT JOIN laptops l ON s.id = l.student_id
            ORDER BY s.id DESC
          ";
          $result = $conn->query($sql);
          while ($row = $result->fetch_assoc()):
          ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
              <td><?= htmlspecialchars($row['reg_no']) ?></td>
              <td><?= htmlspecialchars($row['department']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['phone']) ?></td>
              <td><?= htmlspecialchars($row['laptop_brand']) ?></td>
              <td><?= htmlspecialchars($row['serial_number']) ?></td>
              <td>
                <a href="edit_student.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning mb-1"><i class="fas fa-edit"></i> Edit</a>
                <a href="delete_student.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('Are you sure you want to delete this student?')"><i class="fas fa-trash"></i> Delete</a>
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

<!-- DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
  $(document).ready(function() {
    $('#studentsTable').DataTable({
      pageLength: 15,
      lengthChange: false,
      language: {
        search: "Search students:"
      }
    });
  });
</script>
