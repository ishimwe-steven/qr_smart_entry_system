<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $phone, $password, $role);
    if ($stmt->execute()) {
        header("Location: manage_users.php");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
}
?>
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
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
      color:black;
      margin-bottom: 2rem;
    }
    .form-label {
      color: #2c3e50;
      font-weight: 500;
    }
    .form-control, .form-select {
     color: #a0aec0;
     
      border: 1px solid #2c3e50;
      border-radius: 0.5rem;
      padding: 0.5rem;
      transition: border-color 0.3s ease;
    }
    .form-control:focus, .form-select:focus {
      border-color: #00c4ff;
      outline: none;
      box-shadow: 0 0 5px rgba(0, 196, 255, 0.5);
    }
    .form-control::placeholder {
      color: #a0aec0;
    }
    .btn-success {
      background: linear-gradient(45deg, #28a745, #34c759);
      border: none;
      border-radius: 0.5rem;
      font-weight: 500;
      padding: 0.75rem 1.5rem;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .btn-success:hover {
      background: linear-gradient(45deg, #218838, #2cb050);
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
    }
    .btn-secondary {
      background: linear-gradient(45deg, #6c757d, #82909d);
      border: none;
      border-radius: 0.5rem;
      font-weight: 500;
      padding: 0.75rem 1.5rem;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .btn-secondary:hover {
      background: linear-gradient(45deg, #5a6268, #6f7b86);
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
    }
    .btn i {
      margin-right: 0.5rem;
    }
    .alert-danger {
      background: linear-gradient(45deg, #dc3545, #ff4d4d);
      border: none;
      border-radius: 0.5rem;
      color: #ffffff;
      font-weight: 500;
      padding: 1rem;
      margin-bottom: 1rem;
    }
    form {
      max-width: 600px;
      margin: 110px auto 0 auto;
      background: rgba(255, 255, 255, 0.95);
      padding: 2rem;
      border-radius: 1rem;
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
    }
    @media (max-width: 768px) {
      .flex-grow-1 {
        margin-left: 0;
        padding: 1rem;
      }
      form {
        max-width: 100%;
        margin: 60px auto 0 auto;
        padding: 1rem;
      }
      h2 {
        font-size: 2rem;
      }
    }

   </style>
<div class="d-flex">
  <?php include '../includes/admin_sidebar.php'; ?>

  <div class="flex-grow-1 p-2"style="margin-left:300px;  width:1500px;">
    <h2>Add User</h2>
    <form method="POST" style="width:800px; margin-top:110px; margin-left:350px;">
      <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" placeholder="Enter  Full Name"class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" placeholder="Enter  Email " class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Phone</label>
        <input type="text" name="phone" placeholder="Enter PhoneNumber"class="form-control">
      </div>
      <div class="mb-3">
        <label>Role</label>
        <select name="role" class="form-control" required>
          <option value="">Select role</option>
          <option value="security">Security</option>
          
        </select>
      </div>
      <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" placeholder="Set Password"class="form-control" required>
      </div>
      <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save</button>
      <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
