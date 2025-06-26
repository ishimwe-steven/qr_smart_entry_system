<?php
include '../includes/db.php';

$query = '';
if (isset($_POST['query'])) {
    $query = trim($_POST['query']);
}

$sql = "
  SELECT s.id AS student_id, s.first_name, s.last_name, s.reg_no, s.department, s.email, s.phone,
         l.id AS laptop_id, l.brand, l.serial_number
  FROM students s
  LEFT JOIN laptops l ON s.id = l.student_id
";

if (!empty($query)) {
    $sql .= " WHERE 
        s.first_name LIKE ? OR
        s.last_name LIKE ? OR
        s.reg_no LIKE ? OR
        s.department LIKE ? ";
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
  <td><?= $row['student_id'] ?></td>
  <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
  <td><?= htmlspecialchars($row['reg_no']) ?></td>
  <td><?= htmlspecialchars($row['department']) ?></td>
  <td><?= htmlspecialchars($row['email']) ?></td>
  <td><?= htmlspecialchars($row['phone']) ?></td>
  <td><?= htmlspecialchars($row['brand']) ?></td>
  <td><?= htmlspecialchars($row['serial_number']) ?></td>
  <td>
    <a href="edit_student.php?id=<?= $row['student_id'] ?>" class="btn btn-sm btn-warning">
      <i class="fas fa-edit"></i> Edit
    </a>
    <a href="delete_student.php?id=<?= $row['student_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
      <i class="fas fa-trash"></i> Delete
    </a>
    <?php if (!empty($row['laptop_id'])): ?>
      <a href="generate_qr.php?laptop_id=<?= $row['laptop_id'] ?>" class="btn btn-sm btn-info">
        <i class="fas fa-qrcode"></i> Generate QR
      </a>
    <?php else: ?>
      <span class="text-muted">No laptop</span>
    <?php endif; ?>
  </td>
</tr>
<?php endwhile; ?>
