<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    exit("Unauthorized");
}

include '../includes/db.php';

$start = $_GET['start_date'] ?? '';
$end = $_GET['end_date'] ?? '';

$filename = "issue_reports_" . date("Ymd_His") . ".csv";
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=$filename");
$output = fopen("php://output", "w");

// CSV Header
fputcsv($output, ['ID', 'Student Name', 'Laptop Serial', 'Reported By', 'Description', 'Time']);

// Query
$where = '';
if (!empty($start) && !empty($end)) {
    $where = "WHERE r.report_time BETWEEN '" . $start . " 00:00:00' AND '" . $end . " 23:59:59'";
}

$sql = "
    SELECT r.id, s.first_name, s.last_name, l.serial_number, u.name AS reported_by, r.issue_description, r.report_time
    FROM issue_reports r
    LEFT JOIN laptops l ON r.laptop_id = l.id
    LEFT JOIN students s ON l.student_id = s.id
    LEFT JOIN users u ON r.reported_by = u.id
    $where
    ORDER BY r.report_time DESC
";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['first_name'] . ' ' . $row['last_name'],
        $row['serial_number'],
        $row['reported_by'],
        $row['issue_description'],
        $row['report_time']
    ]);
}
fclose($output);
exit;
