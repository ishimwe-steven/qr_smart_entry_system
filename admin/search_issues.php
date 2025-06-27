<?php
include '../includes/db.php';

$search = trim($_GET['search'] ?? '');

$sql = "
  SELECT r.id, r.issue_description, r.report_time,
         s.first_name, s.last_name, l.serial_number, u.name AS reported_by
  FROM issue_reports r
  LEFT JOIN laptops l ON r.laptop_id = l.id
  LEFT JOIN students s ON l.student_id = s.id
  LEFT JOIN users u ON r.reported_by = u.id
";

if (!empty($search)) {
    $sql .= " WHERE 
      s.first_name LIKE ? OR 
      s.last_name LIKE ? OR 
      l.serial_number LIKE ? OR 
      u.name LIKE ? OR 
      r.issue_description LIKE ?
    ";
}

$sql .= " ORDER BY r.report_time DESC";

$stmt = $conn->prepare($sql);

if (!empty($search)) {
    $like = "%$search%";
    $stmt->bind_param("sssss", $like, $like, $like, $like, $like);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0): ?>
  <table class="table table-bordered table-hover table-striped">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Student</th>
        <th>Serial No.</th>
        <th>Reported By</th>
        <th>Description</th>
        <th>Reported At</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
          <td><?= htmlspecialchars($row['serial_number']) ?></td>
          <td><?= htmlspecialchars($row['reported_by']) ?></td>
          <td><?= htmlspecialchars($row['issue_description']) ?></td>
          <td><?= htmlspecialchars($row['report_time']) ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
<?php else: ?>
  <p class="text-muted">No reported issues found.</p>
<?php endif; ?>
