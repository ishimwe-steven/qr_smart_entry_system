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
      width: 100%;
    }
    h2 {
      font-size: 2.5rem;
      font-weight: 700;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
      margin-bottom: 2rem;
    }
    h4 {
      font-size: 1.5rem;
      font-weight: 600;
      color: #2c3e50;
      margin-top: 1.5rem;
      margin-bottom: 1rem;
    }
    .form-label {
      color: #2c3e50;
      font-weight: 500;
    }
    .form-control {
      
      color: #ffffff;
      border: 1px solid #2c3e50;
      border-radius: 0.5rem;
      padding: 0.5rem;
      transition: border-color 0.3s ease;
    }
    .form-control:focus {
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
      max-width: 800px;
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
      h4 {
        font-size: 1.25rem;
      }
      .col-md-6, .col-md-4 {
        flex: 0 0 100%;
        max-width: 100%;
      }
    }

</style>


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
          <input type="text" name="first_name" placeholder="Enter First Name" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label>Last Name</label>
          <input type="text" name="last_name" placeholder="Enter last Name" class="form-control" required>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label>Email</label>
          <input type="email" name="email" placeholder="Enter Email"class="form-control">
        </div>
        <div class="col-md-6 mb-3">
          <label>Phone</label>
          <input type="text" name="phone"placeholder="Enter PhoneNumber" class="form-control">
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label>Picture</label>
          <input type="file" name="picture" placeholder="Choose Pricture"class="form-control" accept="image/*">
        </div>
        <div class="col-md-6 mb-3">
          <label>Reg No</label>
          <input type="text" name="reg_no" placeholder="Enter RegNumber" class="form-control" required>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label>Department</label>
          <input type="text" name="department" placeholder="Enter Department"class="form-control">
        </div>
      </div>

      <h4>Laptop Info</h4>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label>Brand</label>
          <input type="text" name="brand" placeholder="Etner Brand"class="form-control" required>
        </div>

        <div class="col-md-4 mb-3">
          <label>Serial Number</label>
          <input type="text" name="serial_number" placeholder="Enter Serial Number" class="form-control" required>
        </div>
      </div>

      <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save</button>
      <a href="manage_students.php" class="btn btn-secondary">Cancel</a>

    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
