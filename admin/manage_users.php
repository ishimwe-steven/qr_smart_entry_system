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
    <div class="d-flex justify-content-between align-items-center mb-3" style="width:1500px;">
      <h2>Manage Users</h2>
      
    </div>
         <div class=""   style="margin-left: 1380px;">
            <a href="add_user.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add User</a>
         </div>

    <!-- Live Search Input -->
    <input type="text" id="search" class="form-control mb-3" placeholder="Search by name, email, phone, role..." style="max-width:400px;">

    <table class="table table-bordered table-striped" style="width:1500px; margin-top:12px">
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
      <tbody id="user-data">
        <!-- Data will load here -->
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function loadUsers(query = '') {
  $.ajax({
    url: 'search_users.php',
    method: 'POST',
    data: { query: query },
    success: function(data) {
      $('#user-data').html(data);
    }
  });
}

$(document).ready(function(){
  loadUsers(); // initial load

  $('#search').on('input', function(){
    let search = $(this).val();
    loadUsers(search);
  });
});
</script>
