<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    exit("Unauthorized");
}

require_once('../libs/tcpdf/tcpdf.php');
include '../includes/db.php';

$start = $_GET['start_date'] ?? '';
$end = $_GET['end_date'] ?? '';

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

// Init TCPDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Issue Reports');
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// Title
$pdf->Write(0, "Issue Reports", '', 0, 'L', true, 0, false, false, 0);
$pdf->Ln(4);

// Table
$html = '<table border="1" cellpadding="4">
  <thead>
    <tr style="background-color:#f2f2f2;">
      <th><b>ID</b></th>
      <th><b>Student</b></th>
      <th><b>Serial No</b></th>
      <th><b>Reported By</b></th>
      <th><b>Description</b></th>
      <th><b>Time</b></th>
    </tr>
  </thead>
  <tbody>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
        <td>' . $row['id'] . '</td>
        <td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>
        <td>' . htmlspecialchars($row['serial_number']) . '</td>
        <td>' . htmlspecialchars($row['reported_by']) . '</td>
        <td>' . htmlspecialchars($row['issue_description']) . '</td>
        <td>' . $row['report_time'] . '</td>
      </tr>';
}

$html .= '</tbody></table>';
$pdf->writeHTML($html, true, false, false, false, '');
$pdf->Output('issue_reports_' . date("Ymd_His") . '.pdf', 'I');
