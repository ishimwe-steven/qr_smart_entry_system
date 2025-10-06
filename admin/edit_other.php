<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';

$message = '';
$id = intval($_GET['id']);

// Fetch other + laptop data
$sql = "
  SELECT o.*, l.brand, l.serial_number
  FROM others o
  LEFT JOIN laptops l ON l.other_id = o.id AND l.owner_type = 'other'
  WHERE o.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "<div class='alert alert-danger'>Record not found!</div>";
    exit;
}

$data = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle picture upload
    $pictureFile = $data['picture']; // keep existing if no new one
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

    // Update others
    $stmt = $conn->prepare("UPDATE others SET first_name=?, last_name=?, email=?, phone=?, national_id=?, role=?, department=?, picture=? WHERE id=?");
    $stmt->bind_param(
        "ssssssssi",
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['national_id'],
        $_POST['role'],
        $_POST['department'],
        $pictureFile,
        $id
    );

    if ($stmt->execute()) {
        // Update laptop if provided
        if (!empty($_POST['brand']) && !empty($_POST['serial_number'])) {
            // Check if a laptop already exists for this other
            $checkLaptop = $conn->prepare("SELECT id FROM laptops WHERE other_id=? AND owner_type='other'");
            $checkLaptop->bind_param("i", $id);
            $checkLaptop->execute();
            $checkLaptop->store_result();

            if ($checkLaptop->num_rows > 0) {
                // Update existing laptop
                $stmt2 = $conn->prepare("UPDATE laptops SET brand=?, serial_number=? WHERE other_id=? AND owner_type='other'");
                $stmt2->bind_param("ssi", $_POST['brand'], $_POST['serial_number'], $id);
                $stmt2->execute();
            } else {
                // Insert new laptop
                $stmt2 = $conn->prepare("INSERT INTO laptops (owner_type, other_id, brand, serial_number) VALUES ('other', ?, ?, ?)");
                $stmt2->bind_param("iss", $id, $_POST['brand'], $_POST['serial_number']);
                $stmt2->execute();
            }
        }
        header("Location: manage_others.php");
        exit;
    } else {
        $message = "<div class='alert alert-danger'>Error updating record: " . $stmt->error . "</div>";
    }
}
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
    body { background-color: #f4f6f9; }
    .flex-grow-1 { margin-left: 300px; padding: 2rem; }
    form {
        max-width: 800px;
        margin: 110px auto 0 auto;
        background: rgba(255, 255, 255, 0.95);
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
    }
    img { border-radius: 0.5rem; margin-top: 0.5rem; }
</style>

<div class="d-flex">
  <?php include '../includes/admin_sidebar.php'; ?>

  <div class="flex-grow-1 p-4">
    <h2>Edit Other (Lecturer / Staff / Guest)</h2>

    <?= $message ?>

    <form method="POST" enctype="multipart/form-data">

      <h4>Personal Info</h4>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label>First Name</label>
          <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($data['first_name']) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
          <label>Last Name</label>
          <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($data['last_name']) ?>" required>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label>Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data['email']) ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label>Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($data['phone']) ?>">
        </div>
      </div>

      <div class="row">
        <div class="col-md-4 mb-3">
          <label>National ID</label>
          <input type="text" name="national_id" class="form-control" value="<?= htmlspecialchars($data['national_id']) ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label>Role</label>
          <select name="role" class="form-select" required>
            <option <?= $data['role']=='Lecturer'?'selected':'' ?>>Lecturer</option>
            <option <?= $data['role']=='Staff'?'selected':'' ?>>Staff</option>
            <option <?= $data['role']=='Guest'?'selected':'' ?>>Guest</option>
          </select>
        </div>
        <div class="col-md-4 mb-3">
          <label>Department</label>
          <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($data['department']) ?>">
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label>Picture</label>
          <input type="file" name="picture" class="form-control" accept="image/*">
          <?php if ($data['picture']): ?>
            <small>Current: <img src="../uploads/<?= htmlspecialchars($data['picture']) ?>" width="60"></small>
          <?php endif; ?>
        </div>
      </div>

      <h4>Laptop Info (Optional)</h4>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label>Brand</label>
          <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($data['brand']) ?>">
        </div>
        <div class="col-md-6 mb-3">
          <label>Serial Number</label>
          <input type="text" name="serial_number" class="form-control" value="<?= htmlspecialchars($data['serial_number']) ?>">
        </div>
      </div>

      <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update</button>
      <a href="manage_others.php" class="btn btn-secondary">Cancel</a>

    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
