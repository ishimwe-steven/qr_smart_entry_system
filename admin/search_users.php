<?php
include '../includes/db.php';

$query = '';
if (isset($_POST['query'])) {
    $query = trim($_POST['query']);
}

$sql = "SELECT * FROM users";
if (!empty($query)) {
    $sql .= " WHERE 
        name LIKE ? OR
        email LIKE ? OR
        phone LIKE ? OR
        role LIKE ? ";
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
  <td><?= htmlspecialchars($row['name']) ?></td>
  <td><?= htmlspecialchars($row['email']) ?></td>
  <td><?= htmlspecialchars($row['phone']) ?></td>
  <td><?= htmlspecialchars($row['role']) ?></td>
  <td>
    <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</a>
    <a href="delete_user.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i> Delete</a>
  </td>
</tr>
<?php endwhile; ?>
