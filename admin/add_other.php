<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check duplicate National ID
    $checkOther = $conn->prepare("SELECT id FROM others WHERE national_id = ?");
    $checkOther->bind_param("s", $_POST['national_id']);
    $checkOther->execute();
    $checkOther->store_result();

    // Check duplicate serial_number
    $checkLaptop = $conn->prepare("SELECT id FROM laptops WHERE serial_number = ?");
    $checkLaptop->bind_param("s", $_POST['serial_number']);
    $checkLaptop->execute();
    $checkLaptop->store_result();

    if ($checkOther->num_rows > 0 && !empty($_POST['national_id'])) {
        $message = "<div class='alert alert-danger'>⚠ A record with this National ID already exists.</div>";
    } elseif ($checkLaptop->num_rows > 0) {
        $message = "<div class='alert alert-danger'>⚠ A laptop with this Serial Number already exists.</div>";
    } else {
        // Handle picture upload
        $pictureFile = '';
        if (!empty($_FILES['picture']['name'])) {
            $targetDir = "../uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $fileName = time() . "_" . basename($_FILES["picture"]["name"]);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES["picture"]["tmp_name"], $targetFile)) {
                $pictureFile = $fileName;
            } else {
                $message = "<div class='alert alert-danger'>⚠ Failed to upload picture. Please try again.</div>";
            }
        }

        // Insert into others
        $stmt = $conn->prepare("INSERT INTO others (first_name, last_name, email, phone, national_id, role, department, picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "ssssssss",
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['national_id'],
            $_POST['role'],
            $_POST['department'],
            $pictureFile
        );

        if ($stmt->execute()) {
            $other_id = $stmt->insert_id;

            // Insert laptop if details provided
            if (!empty($_POST['brand']) && !empty($_POST['serial_number'])) {
                $stmt2 = $conn->prepare("INSERT INTO laptops (owner_type, other_id, brand, serial_number) VALUES ('other', ?, ?, ?)");
                $stmt2->bind_param(
                    "iss",
                    $other_id,
                    $_POST['brand'],
                    $_POST['serial_number']
                );

                if ($stmt2->execute()) {
                    header("Location: manage_others.php");
                    exit;
                } else {
                    $message = "<div class='alert alert-danger'>Error adding laptop: " . $stmt2->error . "</div>";
                }
            } else {
                header("Location: manage_others.php");
                exit;
            }
        } else {
            $message = "<div class='alert alert-danger'>Error adding record: " . $stmt->error . "</div>";
        }
    }
}
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
   body { background-color: #f4f6f9; }
   .flex-grow-1 { margin-left: 300px; padding: 2rem; width: 100%; }
   form {
      max-width: 800px;
      margin: 110px auto 0 auto;
      background: rgba(255, 255, 255, 0.95);
      padding: 2rem;
      border-radius: 1rem;
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
   }
   h2 { font-size: 2.5rem; font-weight: 700; margin-bottom: 2rem; }
   h4 { font-size: 1.5rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 1rem; }
   .form-label { font-weight: 500; }
   .form-control { border-radius: 0.5rem; }
   .btn-success { background: linear-gradient(45deg, #28a745, #34c759); border: none; }
   .btn-secondary { background: linear-gradient(45deg, #6c757d, #82909d); border: none; }
   .alert-danger { background: linear-gradient(45deg, #dc3545, #ff4d4d); color: white; }
</style>

<div class="d-flex">
  <?php include '../includes/admin_sidebar.php'; ?>

  <div class="flex-grow-1 p-4">
    <h2>Add Other (Lecturer / Staff / Guest)</h2>

    <?= $message ?>

    <form method="POST" enctype="multipart/form-data">

      <h4>Personal Info</h4>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">First Name</label>
          <input type="text" name="first_name" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Last Name</label>
          <input type="text" name="last_name" class="form-control" required>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control">
        </div>
      </div>

      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">National ID (optional)</label>
          <input type="text" name="national_id" class="form-control">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Role</label>
          <select name="role" class="form-select" required>
            <option value="">Select</option>
            <option>Lecturer</option>
            <option>Staff</option>
            <option>Guest</option>
          </select>
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Department</label>
          <input type="text" name="department" class="form-control">
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Picture</label>
          <input type="file" name="picture" class="form-control" accept="image/*">
        </div>
      </div>

      <h4>Laptop Info (Optional)</h4>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Brand</label>
          <input type="text" name="brand" class="form-control">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Serial Number</label>
          <input type="text" name="serial_number" class="form-control">
        </div>
      </div>

      <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save</button>
      <a href="manage_others.php" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
