<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    exit("Unauthorized");
}

require_once('../libs/tcpdf/tcpdf.php');
include '../includes/db.php';

$start = $_GET['start_date'] ?? '';
$end = $_GET['end_date'] ?? '';
$status = $_GET['status_filter'] ?? 'all';

// Filter conditions
$conditions = [];
if (!empty($start) && !empty($end)) {
    $conditions[] = "r.report_time BETWEEN '{$start} 00:00:00' AND '{$end} 23:59:59'";
}
if ($status === 'solved') {
    $conditions[] = "r.status = 'solved'";
} elseif ($status === 'unsolved') {
    $conditions[] = "r.status = 'unsolved'";
}

$where_clause = '';
if (!empty($conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $conditions);
}

// Fetch students + others
$sql = "
    SELECT 
        r.id,
        r.issue_description,
        r.report_time,
        r.status,
        COALESCE(s.first_name, o.first_name) AS first_name,
        COALESCE(s.last_name, o.last_name) AS last_name,
        COALESCE(o.national_id, '') AS national_id,
        CASE 
            WHEN s.id IS NOT NULL THEN 'Student'
            ELSE o.role
        END AS role,
        l.serial_number,
        u.name AS reported_by
    FROM issue_reports r
    LEFT JOIN laptops l ON r.laptop_id = l.id
    LEFT JOIN students s ON l.student_id = s.id
    LEFT JOIN others o ON l.other_id = o.id
    LEFT JOIN users u ON r.reported_by = u.id
    $where_clause
    ORDER BY r.report_time DESC
";
$result = $conn->query($sql);

// PDF setup
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Issue Reports');
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 9);

$pdf->Write(0, "Issue Reports (" . ucfirst($status) . ")", '', 0, 'L', true, 0, false, false, 0);
$pdf->Ln(4);

$html = '
<table border="1" cellpadding="4">
  <thead>
    <tr style="background-color:#f2f2f2;">
      <th><b>ID</b></th>
      <th><b>Name</b></th>
      <th><b>National ID</b></th>
      <th><b>Role</b></th>
      <th><b>Serial No</b></th>
      <th><b>Reported By</b></th>
      <th><b>Description</b></th>
      <th><b>Status</b></th>
      <th><b>Reported Time</b></th>
    </tr>
  </thead>
  <tbody>';

while ($row = $result->fetch_assoc()) {
    $statusText = ucfirst($row['status']);
    $html .= "<tr>
        <td>{$row['id']}</td>
        <td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>
        <td>" . htmlspecialchars($row['national_id']) . "</td>
        <td>" . htmlspecialchars($row['role']) . "</td>
        <td>" . htmlspecialchars($row['serial_number']) . "</td>
        <td>" . htmlspecialchars($row['reported_by']) . "</td>
        <td>" . htmlspecialchars($row['issue_description']) . "</td>
        <td>{$statusText}</td>
        <td>{$row['report_time']}</td>
      </tr>";
}

$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, false, false, '');
$pdf->Output('issue_reports_' . date("Ymd_His") . '.pdf', 'I');
