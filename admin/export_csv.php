<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../auth/login_form.php");
  exit;
}

include '../includes/db.php';

$start = $_GET['start_date'] ?? '';
$end = $_GET['end_date'] ?? '';

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=laptop_movement_report.csv');

$output = fopen('php://output', 'w');
// Updated header
fputcsv($output, ['ID', 'Name', 'Reg No / National ID', 'Role', 'Laptop Serial', 'Status', 'Entry Time', 'Exit Time']);

// Query supporting both Students and Others
$sql = "
  SELECT 
      m.id,
      COALESCE(s.first_name, o.first_name) AS first_name,
      COALESCE(s.last_name, o.last_name) AS last_name,
      COALESCE(s.reg_no, o.national_id) AS identifier,
      CASE 
        WHEN s.id IS NOT NULL THEN 'Student'
        WHEN o.id IS NOT NULL THEN o.role
        ELSE 'Unknown'
      END AS role,
      l.serial_number,
      m.status,
      m.entry_time,
      m.exit_time
  FROM laptop_movements m
  LEFT JOIN laptops l ON m.laptop_id = l.id
  LEFT JOIN students s ON l.student_id = s.id
  LEFT JOIN others o ON l.other_id = o.id
";

$params = [];
$types = '';

if (!empty($start) && !empty($end)) {
  $sql .= " WHERE m.entry_time BETWEEN ? AND ?";
  $params = [$start . ' 00:00:00', $end . ' 23:59:59'];
  $types = 'ss';
  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $result = $stmt->get_result();
} else {
  $result = $conn->query($sql);
}

while ($row = $result->fetch_assoc()) {
  fputcsv($output, [
    $row['id'],
    $row['first_name'] . ' ' . $row['last_name'],
    $row['identifier'],
    $row['role'],
    $row['serial_number'],
    $row['status'],
    $row['entry_time'],
    $row['exit_time']
  ]);
}

fclose($output);
exit;
?>
