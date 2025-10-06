<div class="d-flex flex-column flex-shrink-0 p-3 sidebar" style="width: 250px; height: 100vh; position: fixed;">
  <a href="#" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
    <span class="fs-4"><i class="fas fa-lock"></i> Admin</span>
  </a>
  <hr>
  <ul class="nav nav-pills flex-column mb-auto">
    <li><a href="dashboard.php" class="nav-link text-white"><i class="fas fa-home"></i> Dashboard</a></li>
    <li><a href="manage_users.php" class="nav-link text-white"><i class="fas fa-users"></i> Manage Users</a></li>
    
    <!-- Dropdown for Manage Laptops -->
    <li>
      <a class="nav-link text-white d-flex justify-content-between align-items-center" 
         data-bs-toggle="collapse" href="#manageLaptopMenu" role="button" aria-expanded="false" aria-controls="manageLaptopMenu">
        <span><i class="fas fa-laptop"></i> Manage Laptops</span>
        <i class="fas fa-caret-down"></i>
      </a>
      <div class="collapse" id="manageLaptopMenu">
        <ul class="btn-toggle-nav list-unstyled fw-normal small ms-3">
          <li><a href="manage_students.php" class="nav-link text-white"><i class="fas fa-user-graduate"></i> Student Laptops</a></li>
          <li><a href="manage_others.php" class="nav-link text-white"><i class="fas fa-user-tie"></i> Others Laptops</a></li>
        </ul>
      </div>
    </li>

    <li><a href="movement_logs.php" class="nav-link text-white"><i class="fas fa-list"></i> Movement Logs</a></li>
    <li><a href="issue_reports.php" class="nav-link text-white"><i class="fas fa-bug"></i> Issue Reports</a></li>
    <li><a href="reports.php" class="nav-link text-white"><i class="fas fa-chart-bar"></i> Reports</a></li>
    <li><a href="../auth/logout.php" class="nav-link text-white"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
  </ul>
</div>

<!-- Custom CSS -->
<style>
  .sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    background: linear-gradient(180deg, #2c3e50 0%, #1e2a3c 100%);
    padding: 30px 20px;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }
  .sidebar a {
    color: #c2c7d0;
    padding: 12px 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    font-size: 16px;
    border-radius: 0.5rem;
    transition: background 0.3s ease, color 0.3s ease, transform 0.2s ease;
  }
  .sidebar a:hover {
    background: linear-gradient(45deg, #007bff, #00c4ff);
    color: #ffffff;
    transform: translateX(5px);
  }
  .sidebar .fs-4 {
    color: #ffffff;
    font-weight: 700;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  }
  .sidebar hr {
    border-color: #495057;
    margin: 1.5rem 0;
  }
  .sidebar .nav-link {
    padding: 10px 15px;
    margin-bottom: 5px;
  }
  .sidebar .nav-link i {
    width: 20px;
    text-align: center;
  }
  @media (max-width: 768px) {
    .sidebar {
      width: 200px;
      padding: 20px 15px;
    }
    .sidebar a {
      font-size: 14px;
      padding: 10px 12px;
    }
    .sidebar .fs-4 {
      font-size: 1.25rem;
    }
  }
</style>
