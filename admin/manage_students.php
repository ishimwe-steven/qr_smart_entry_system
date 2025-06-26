<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';
?>

<div class="d-flex">
  <?php include '../includes/admin_sidebar.php'; ?>

 
<div class="flex-grow-1 p-4" style="margin-left:300px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>Manage Laptops</h2>
      
    </div>
    <div class=""   style="margin-left: 1205px;">
            <a href="add_student.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add Student</a>
             <a href="add_laptop.php" class="btn btn-success"><i class="fas fa-laptop"></i> Add Laptop</a>
         </div>

    <!-- Live Search Input -->
    <input type="text" id="search" class="form-control mb-3" placeholder="Search by name, reg no, department..." style="max-width:400px;">

    <table class="table table-bordered table-striped" style="width:1500px;">
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
      <tbody id="student-data">
        <!-- Data will load here -->
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function loadStudents(query = '') {
  $.ajax({
    url: 'search_students.php',
    method: 'POST',
    data: { query: query },
    success: function(data) {
      $('#student-data').html(data);
    }
  });
}

$(document).ready(function(){
  loadStudents(); // initial load

  $('#search').on('input', function(){
    let search = $(this).val();
    loadStudents(search);
  });
});
</script>
