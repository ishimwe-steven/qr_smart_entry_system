<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

require '../includes/db.php';
require '../vendor/autoload.php'; // make sure you have PhpSpreadsheet installed

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['import'])) {
    $fileName = $_FILES['file']['name'];
    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExt, ['xls', 'xlsx', 'csv'])) {
        $_SESSION['error_message'] = "Invalid file type. Please upload an Excel or CSV file.";
        header("Location: manage_others.php");
        exit;
    }

    try {
        // Load spreadsheet
        $spreadsheet = IOFactory::load($fileTmp);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $inserted = 0;
        $skipped = 0;

        // Skip header row
        for ($i = 2; $i <= count($rows); $i++) {
            $first_name   = trim($rows[$i]['A'] ?? '');
            $last_name    = trim($rows[$i]['B'] ?? '');
            $national_id  = trim($rows[$i]['C'] ?? '');
            $role         = trim($rows[$i]['D'] ?? '');
            $department   = trim($rows[$i]['E'] ?? '');
            $email        = trim($rows[$i]['F'] ?? '');
            $phone        = trim($rows[$i]['G'] ?? '');
            $brand        = trim($rows[$i]['H'] ?? '');
            $serial_number = trim($rows[$i]['I'] ?? '');

            if ($first_name === '' || $last_name === '' || $role === '') {
                $skipped++;
                continue;
            }

            // Check duplicate national ID (only if provided)
            if (!empty($national_id)) {
                $checkOther = $conn->prepare("SELECT id FROM others WHERE national_id = ?");
                $checkOther->bind_param("s", $national_id);
                $checkOther->execute();
                $checkOther->store_result();
                if ($checkOther->num_rows > 0) {
                    $skipped++;
                    continue;
                }
            }

            // Insert into others
            $stmt = $conn->prepare("INSERT INTO others (first_name, last_name, national_id, role, department, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $first_name, $last_name, $national_id, $role, $department, $email, $phone);
            if ($stmt->execute()) {
                $other_id = $stmt->insert_id;

                // Insert laptop if details provided
                if (!empty($brand) && !empty($serial_number)) {
                    // Avoid duplicate serials
                    $checkLaptop = $conn->prepare("SELECT id FROM laptops WHERE serial_number = ?");
                    $checkLaptop->bind_param("s", $serial_number);
                    $checkLaptop->execute();
                    $checkLaptop->store_result();
                    if ($checkLaptop->num_rows === 0) {
                        // Ensure columns exist before inserting
                        $hasOtherId = (bool) $conn->query("
                            SELECT COUNT(*) AS c FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='laptops' AND COLUMN_NAME='other_id'
                        ")->fetch_assoc()['c'];

                        $hasOwnerType = (bool) $conn->query("
                            SELECT COUNT(*) AS c FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='laptops' AND COLUMN_NAME='owner_type'
                        ")->fetch_assoc()['c'];

                        if ($hasOwnerType && $hasOtherId) {
                            $stmtLaptop = $conn->prepare("INSERT INTO laptops (owner_type, other_id, brand, serial_number) VALUES ('other', ?, ?, ?)");
                            $stmtLaptop->bind_param("iss", $other_id, $brand, $serial_number);
                        } elseif ($hasOtherId) {
                            $stmtLaptop = $conn->prepare("INSERT INTO laptops (other_id, brand, serial_number) VALUES (?, ?, ?)");
                            $stmtLaptop->bind_param("iss", $other_id, $brand, $serial_number);
                        } else {
                            // fallback (no other_id column)
                            $stmtLaptop = $conn->prepare("INSERT INTO laptops (brand, serial_number) VALUES (?, ?)");
                            $stmtLaptop->bind_param("ss", $brand, $serial_number);
                        }
                        $stmtLaptop->execute();
                    }
                }

                $inserted++;
            } else {
                $skipped++;
            }
        }

        $_SESSION['success_message'] = "✅ Imported successfully! Inserted: {$inserted}, Skipped: {$skipped}";
        header("Location: manage_others.php");
        exit;

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error reading file: " . $e->getMessage();
        header("Location: manage_others.php");
        exit;
    }
}
?>
