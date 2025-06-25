<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';

// Get user ID
$id = intval($_GET['id']);

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    echo "<div class='alert alert-danger'>User not found!</div>";
    exit;
}
$user = $result->fetch_assoc();

// Update on POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];

    if (!empty($_POST['password'])) {
        // Update password too
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, role=?, password=? WHERE id=?");
        $stmt->bind_param("sssssi", $name, $email, $phone, $role, $password, $id);
    } else {
        // Update without password
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, role=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $email, $phone, $role, $id);
    }

    if ($stmt->execute()) {
        header("Location: manage_users.php");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
}
?>

<div class="d-flex">
  <?php include '../includes/admin_sidebar.php'; ?>

  <div class="flex-grow-1 p-4" style="margin-left:300px;  width:1500px;">
    <h2>Edit User</h2>
    <form method="POST"  style="width:800px; margin-top:110px; margin-left:350px;">
      <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
      </div>
      <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
      </div>
      <div class="mb-3">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>">
      </div>
      <div class="mb-3">
        <label>Role</label>
        <select name="role" class="form-control" required>
          <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
          <option value="security" <?= $user['role'] == 'security' ? 'selected' : '' ?>>Security</option>
          <option value="student" <?= $user['role'] == 'student' ? 'selected' : '' ?>>Student</option>
        </select>
      </div>
      <div class="mb-3">
        <label>New Password (leave blank to keep current)</label>
        <input type="password" name="password" class="form-control">
      </div>
      <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update</button>
      <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
