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

// Table header
$html .= '<table border="1" cellpadding="5">
<tr style="background-color:#f2f2f2;">
  <th><b>ID</b></th>
  <th><b>Name</b></th>
  <th><b>Reg No / National ID</b></th>
  <th><b>Role</b></th>
  <th><b>Serial</b></th>
  <th><b>Status</b></th>
  <th><b>Entry Time</b></th>
  <th><b>Exit Time</b></th>
</tr>';

// Query that supports both students and others
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
  $html .= '<tr>
    <td>' . $row['id'] . '</td>
    <td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>
    <td>' . htmlspecialchars($row['identifier']) . '</td>
    <td>' . htmlspecialchars($row['role']) . '</td>
    <td>' . htmlspecialchars($row['serial_number']) . '</td>
    <td>' . htmlspecialchars($row['status']) . '</td>
    <td>' . htmlspecialchars($row['entry_time']) . '</td>
    <td>' . htmlspecialchars($row['exit_time']) . '</td>
  </tr>';
}

$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('laptop_movement_report_' . date("Ymd_His") . '.pdf', 'I');
?>
