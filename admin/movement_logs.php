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
      <h2>Movement Logs</h2>
    </div>

    <!-- Live Search Input -->
    <input type="text" id="search" class="form-control mb-3" placeholder="Search by reg no, name, serial..." style="max-width:400px;">

    <table class="table table-bordered table-striped" style="width:1500px;">
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
      <tbody id="movement-data">
        <!-- Loaded by AJAX -->
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function loadMovements(query = '') {
  $.ajax({
    url: 'search_movements.php',
    method: 'POST',
    data: { query: query },
    success: function(data) {
      $('#movement-data').html(data);
    }
  });
}

$(document).ready(function(){
  loadMovements();

  $('#search').on('input', function(){
    loadMovements($(this).val());
  });
});
</script>
