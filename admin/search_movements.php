<?php
include '../includes/db.php';

$query = '';
if (isset($_POST['query'])) {
    $query = trim($_POST['query']);
}

$sql = "
  SELECT m.id, m.status, m.entry_time, m.exit_time, 
         s.first_name, s.last_name, s.reg_no,
         l.brand, l.serial_number,
         u.name AS guard_name
  FROM laptop_movements m
  LEFT JOIN laptops l ON m.laptop_id = l.id
  LEFT JOIN students s ON l.student_id = s.id
  LEFT JOIN users u ON m.security_guard_id = u.id
";

if (!empty($query)) {
    $sql .= " WHERE 
      s.first_name LIKE ? OR
      s.last_name LIKE ? OR
      s.reg_no LIKE ? OR
      l.serial_number LIKE ? ";
    $stmt = $conn->prepare($sql);
    $param = "%$query%";
    $stmt->bind_param("ssss", $param, $param, $param, $param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

while ($row = $result->fetch_assoc()):
?>
<tr>
  <td><?= $row['id'] ?></td>
  <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
  <td><?= htmlspecialchars($row['reg_no']) ?></td>
  <td><?= htmlspecialchars($row['brand']) ?></td>
  <td><?= htmlspecialchars($row['serial_number']) ?></td>
  <td><?= htmlspecialchars($row['status']) ?></td>
  <td><?= htmlspecialchars($row['entry_time']) ?></td>
  <td><?= htmlspecialchars($row['exit_time']) ?></td>
  <td><?= htmlspecialchars($row['guard_name']) ?></td>
  <td>
    <a href="delete_movement.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this log?')"><i class="fas fa-trash"></i> Delete</a>
  </td>
</tr>
<?php endwhile; ?>
