<style>
    body {
        background-color: #f4f6f9;
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
    }

    .sidebar {
        width: 220px;
        min-height: 100vh;
        background-color: #343a40;
        padding-top: 30px;
        position: fixed;
    }

    .sidebar a {
        color: #c2c7d0;
        padding: 12px 20px;
        display: block;
        text-decoration: none;
        font-size: 16px;
    }

    .sidebar a:hover {
        background-color: #495057;
        color: #fff;
    }

    .sidebar h4 {
        color: white;
        text-align: center;
        margin-bottom: 30px;
    }

    .main-content {
        margin-left: 220px;
        padding: 30px;
    }
</style>
<div class="d-flex flex-column flex-shrink-0 p-3 bg-dark text-white" style="width: 250px; height: 100vh; position: fixed;">
  <a href="#" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
    <span class="fs-4"><i class="fas fa-lock"></i> Admin</span>
  </a>
  <hr>
  <ul class="nav nav-pills flex-column mb-auto">
    <li><a href="dashboard.php" class="nav-link text-white"><i class="fas fa-home"></i> Dashboard</a></li>
    <li><a href="manage_users.php" class="nav-link text-white"><i class="fas fa-users"></i> Manage Users</a></li>
    <li><a href="manage_students.php" class="nav-link text-white"><i class="fas fa-user-graduate"></i> Manage Students</a></li>
    <li><a href="movement_logs.php" class="nav-link text-white"><i class="fas fa-list"></i> Movement Logs</a></li>
    <li><a href="reports.php" class="nav-link text-white"><i class="fas fa-chart-bar"></i> Reports</a></li>
    <li><a href="../auth/logout.php" class="nav-link text-white"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
  </ul>
</div>
