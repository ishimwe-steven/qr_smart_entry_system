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
  .sidebar h4 {
    color: #ffffff;
    text-align: center;
    margin-bottom: 1.5rem;
    font-weight: 700;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  }
  .sidebar h4 i {
    margin-right: 0.5rem;
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
  .sidebar a i {
    width: 20px;
    text-align: center;
  }
  @media (max-width: 768px) {
    .sidebar {
      width: 200px;
      padding: 20px 15px;
    }
    .sidebar h4 {
      font-size: 1.25rem;
    }
    .sidebar a {
      font-size: 14px;
      padding: 10px 12px;
    }
  }
</style>

<div class="sidebar">
  <h4><i class="fas fa-shield-alt"></i> Security</h4>
  <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
  <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>