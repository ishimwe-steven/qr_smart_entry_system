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
    // Check duplicate reg_no
    $checkStudent = $conn->prepare("SELECT id FROM students WHERE reg_no = ?");
    $checkStudent->bind_param("s", $_POST['reg_no']);
    $checkStudent->execute();
    $checkStudent->store_result();

    // Check duplicate serial_number
    $checkLaptop = $conn->prepare("SELECT id FROM laptops WHERE serial_number = ?");
    $checkLaptop->bind_param("s", $_POST['serial_number']);
    $checkLaptop->execute();
    $checkLaptop->store_result();

    if ($checkStudent->num_rows > 0) {
        $message = "<div class='alert alert-danger'>⚠ A student with this Reg No already exists. Please check and try again.</div>";
    } elseif ($checkLaptop->num_rows > 0) {
        $message = "<div class='alert alert-danger'>⚠ A laptop with this Serial Number already exists. Please check and try again.</div>";
    } else {
        // Handle image upload
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

        // Insert student
        $stmt = $conn->prepare("INSERT INTO students (first_name, last_name, email, phone, picture, reg_no, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "sssssss",
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'],
            $pictureFile,
            $_POST['reg_no'],
            $_POST['department']
        );

        if ($stmt->execute()) {
            $student_id = $stmt->insert_id;

            // Insert laptop
            $stmt2 = $conn->prepare("INSERT INTO laptops (student_id, brand, serial_number) VALUES (?, ?, ?)");
            $stmt2->bind_param(
                "iss",
                $student_id,
                $_POST['brand'],
                $_POST['serial_number']
            );

            if ($stmt2->execute()) {
                header("Location: manage_students.php");
                exit;
            } else {
                $message = "<div class='alert alert-danger'>Error adding laptop: " . $stmt2->error . "</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Error adding student: " . $stmt->error . "</div>";
        }
    }
}
?>

<div class="d-flex">
  <?php include '../includes/admin_sidebar.php'; ?>

  <div class="flex-grow-1 p-4" style="margin-left:300px; width:1500px;">
    <h2>Add Student + Laptop</h2>

    <?= $message ?>

    <form method="POST" enctype="multipart/form-data" style="width:800px; margin-top:110px; margin-left:350px;">

      <h4>Student Info</h4>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label>First Name</label>
          <input type="text" name="first_name" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label>Last Name</label>
          <input type="text" name="last_name" class="form-control" required>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label>Email</label>
          <input type="email" name="email" class="form-control">
        </div>
        <div class="col-md-6 mb-3">
          <label>Phone</label>
          <input type="text" name="phone" class="form-control">
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label>Picture</label>
          <input type="file" name="picture" class="form-control" accept="image/*">
        </div>
        <div class="col-md-6 mb-3">
          <label>Reg No</label>
          <input type="text" name="reg_no" class="form-control" required>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label>Department</label>
          <input type="text" name="department" class="form-control">
        </div>
      </div>

      <h4>Laptop Info</h4>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label>Brand</label>
          <input type="text" name="brand" class="form-control" required>
        </div>

        <div class="col-md-4 mb-3">
          <label>Serial Number</label>
          <input type="text" name="serial_number" class="form-control" required>
        </div>
      </div>

      <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save</button>
      <a href="manage_students.php" class="btn btn-secondary">Cancel</a>

    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
