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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<!-- Custom CSS for left-aligned search -->
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
    
  }
  .btn-primary {
    background: linear-gradient(45deg, #007bff, #00c4ff);
    border: none;
    border-radius: 0.5rem;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    margin-bottom:10px;
  }
  .btn-primary:hover {
    background: linear-gradient(45deg, #0056b3, #0096cc);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
  }
  .btn-primary i {
    margin-right: 0.5rem;
  }
  .table-responsive.dataTables_filter {
    color:black;
    padding:100px
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
    border: 2px solid #ced4da;
    padding: 0.5rem;
    background: #ffffff;
    color: #2c3e50;
    width: 200px;
  }
  .dataTables_wrapper .dataTables_filter label {
    color: black;
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
            <a href="add_user.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add User</a>
         </div>
         

    <div class="table-responsive" style="width:97%">
      <table id="usersTable" class="table table-bordered table-striped" style="width:100%;">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $sql = "SELECT * FROM users ORDER BY id DESC";
          $result = $conn->query($sql);
          while ($row = $result->fetch_assoc()):
          ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['phone']) ?></td>
              <td><?= ucfirst(htmlspecialchars($row['role'])) ?></td>
              <td>
                <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</a>
                <a href="delete_user.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')"><i class="fas fa-trash"></i> Delete</a>
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
    $('#usersTable').DataTable({
      pageLength: 15,
      lengthChange: false,
      language: {
        search: "Search users:"
      }
    });
  });
</script>
