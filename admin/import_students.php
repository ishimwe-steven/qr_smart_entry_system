<?php
// admin/import_students.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

// Composer autoload MUST be required before using PhpSpreadsheet classes
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

include '../includes/db.php';
include '../includes/header.php';

$errors = [];
$success = null;
$summary = [
    'students_added' => 0,
    'students_skipped' => 0,
    'laptops_added' => 0,
    'laptops_skipped' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    if (!isset($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
        $errors[] = "No file uploaded.";
    } else {
        $tmp = $_FILES['file']['tmp_name'];
        $origName = $_FILES['file']['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['xlsx','xls','csv'];
        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid file type. Allowed: .xlsx, .xls, .csv";
        } else {
            try {
                $type = IOFactory::identify($tmp);
                $reader = IOFactory::createReader($type);
                $spreadsheet = $reader->load($tmp);

                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray(null, true, true, true);

                if (count($rows) < 2) {
                    $errors[] = "No data rows found in the sheet.";
                } else {
                    // Map header columns to normalized keys
                    $header = $rows[1];
                    $colMap = [];
                    foreach ($header as $col => $val) {
                        $key = strtolower(preg_replace('/[^a-z0-9]/', '', (string)$val));
                        if (!empty($key)) $colMap[$key] = $col;
                    }

                    $expectedKeys = [
                        'firstname' => ['firstname','first','firstname'],
                        'lastname' => ['lastname','last','surname'],
                        'regno' => ['regno','reg_no','regnumber','regnumber'],
                        'department' => ['department'],
                        'email' => ['email','emailaddress'],
                        'phone' => ['phone','telephone','tel'],
                        'picture' => ['picture','photo','image','profile','profilephoto'],
                        'laptopbrand' => ['laptopbrand','brand'],
                        'serialnumber' => ['serialnumber','serial_number','serial','sn']
                    ];

                    $map = [];
                    foreach ($colMap as $norm => $colLetter) {
                        $map[$norm] = $colLetter;
                    }

                    $getCell = function(array $row, array $variants) use ($map) {
                        foreach ($variants as $v) {
                            $norm = strtolower(preg_replace('/[^a-z0-9]/','',$v));
                            if (isset($map[$norm]) && isset($row[$map[$norm]])) {
                                return trim((string)$row[$map[$norm]]);
                            }
                        }
                        return '';
                    };

                    $conn->begin_transaction();

                    try {
                        $rowCount = 0;
                        foreach ($rows as $rIndex => $row) {
                            if ($rIndex == 1) continue;

                            $first_name = $getCell($row, ['first_name','firstname','first']);
                            $last_name  = $getCell($row, ['last_name','lastname','last','surname']);
                            $reg_no     = $getCell($row, ['reg_no','regno','regnumber']);
                            $department = $getCell($row, ['department']);
                            $email      = $getCell($row, ['email','emailaddress']);
                            $phone      = $getCell($row, ['phone','telephone','tel']);
                            $picture    = $getCell($row, ['picture','photo','image','profile','profilephoto']);
                            $brand      = $getCell($row, ['laptop_brand','laptopbrand','brand']);
                            $serial     = $getCell($row, ['serial_number','serialnumber','serial','sn']);

                            if ($first_name === '' && $last_name === '' && $reg_no === '' && $serial === '') {
                                continue;
                            }

                            $rowCount++;

                            if (empty($reg_no)) {
                                $summary['students_skipped']++;
                                continue;
                            }

                            $stmt = $conn->prepare("SELECT id FROM students WHERE reg_no = ?");
                            $stmt->bind_param("s", $reg_no);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            if ($res && $res->num_rows > 0) {
                                $student = $res->fetch_assoc();
                                $student_id = (int)$student['id'];
                                $summary['students_skipped']++;
                            } else {
                                $ins = $conn->prepare("INSERT INTO students (first_name, last_name, reg_no, department, email, phone, picture) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                $ins->bind_param("sssssss", $first_name, $last_name, $reg_no, $department, $email, $phone, $picture);
                                if (!$ins->execute()) {
                                    throw new Exception("Failed to insert student on row {$rIndex}: " . $ins->error);
                                }
                                $student_id = (int)$conn->insert_id;
                                $summary['students_added']++;
                            }

                            if (!empty($serial)) {
                                $stmtL = $conn->prepare("SELECT id FROM laptops WHERE serial_number = ?");
                                $stmtL->bind_param("s", $serial);
                                $stmtL->execute();
                                $resL = $stmtL->get_result();
                                if ($resL && $resL->num_rows > 0) {
                                    $summary['laptops_skipped']++;
                                } else {
                                    $insL = $conn->prepare("INSERT INTO laptops (student_id, brand, serial_number) VALUES (?, ?, ?)");
                                    $insL->bind_param("iss", $student_id, $brand, $serial);
                                    if (!$insL->execute()) {
                                        throw new Exception("Failed to insert laptop on row {$rIndex}: " . $insL->error);
                                    }
                                    $summary['laptops_added']++;
                                }
                            }
                        }

                        $conn->commit();
                        $success = "Import completed successfully. Processed rows: {$rowCount}";
                    } catch (Exception $e) {
                        $conn->rollback();
                        $errors[] = "Import failed: " . $e->getMessage();
                    }
                }
            } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
                $errors[] = "Error reading spreadsheet: " . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = "Unexpected error: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="d-flex">
  <?php include '../includes/admin_sidebar.php'; ?>

  <div class="flex-grow-1 p-4" style="margin-left:300px;">
    <h2>Import Students (Excel)</h2>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <div class="card mb-3" style="max-width:800px;">
        <div class="card-body">
          <p><strong>Summary:</strong></p>
          <ul>
            <li>Students added: <?= $summary['students_added'] ?></li>
            <li>Students skipped (already exist / invalid): <?= $summary['students_skipped'] ?></li>
            <li>Laptops added: <?= $summary['laptops_added'] ?></li>
            <li>Laptops skipped (duplicate serial): <?= $summary['laptops_skipped'] ?></li>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <div class="card p-3 mb-4" style="max-width:800px;">
      <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label for="file" class="form-label">Choose Excel file (.xlsx, .xls or .csv)</label>
          <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv" class="form-control" required>
        </div>
        <div>
          <button class="btn btn-primary">Upload & Import</button>
          <a href="manage_students.php" class="btn btn-secondary">Back</a>
        </div>
      </form>

      <hr>
      <p><strong>Expected columns (header row):</strong> first_name, last_name, reg_no, department, email, phone, picture, laptop_brand, serial_number</p>
      <p>Column header matching is flexible: underscores/spaces/case are ignored (eg "First Name", "first_name", "firstname" all work).</p>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
