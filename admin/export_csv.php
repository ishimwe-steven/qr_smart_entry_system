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
header('Content-Disposition: attachment;filename=report.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Student', 'Laptop Serial', 'Status', 'Entry Time', 'Exit Time']);

$sql = "
  SELECT m.id, s.first_name, s.last_name, l.serial_number, m.status, m.entry_time, m.exit_time
  FROM laptop_movements m
  LEFT JOIN laptops l ON m.laptop_id = l.id
  LEFT JOIN students s ON l.student_id = s.id
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
    $row['serial_number'],
    $row['status'],
    $row['entry_time'],
    $row['exit_time']
  ]);
}

fclose($output);
exit;
?>
