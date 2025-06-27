<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../auth/login_form.php");
  exit;
}

require_once('../libs/tcpdf/tcpdf.php');
include '../includes/db.php';

$start = $_GET['start_date'] ?? '';
$end = $_GET['end_date'] ?? '';

// PDF Setup
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('Laptop Movement Report');
$pdf->AddPage();

$html = '<h2>Laptop Movement Report</h2>';
if (!empty($start) && !empty($end)) {
  $html .= "<p>From <strong>$start</strong> to <strong>$end</strong></p>";
}

$html .= '<table border="1" cellpadding="5">
<tr>
  <th>ID</th>
  <th>Student</th>
  <th>Serial</th>
  <th>Status</th>
  <th>Entry Time</th>
  <th>Exit Time</th>
</tr>';

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
  $html .= '<tr>
    <td>' . $row['id'] . '</td>
    <td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>
    <td>' . htmlspecialchars($row['serial_number']) . '</td>
    <td>' . $row['status'] . '</td>
    <td>' . $row['entry_time'] . '</td>
    <td>' . $row['exit_time'] . '</td>
  </tr>';
}

$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('laptop_report.pdf', 'I');
?>
