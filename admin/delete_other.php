<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';

$id = intval($_GET['id']);

// First delete any laptops owned by this other
$stmt = $conn->prepare("DELETE FROM laptops WHERE other_id = ? AND owner_type = 'other'");
$stmt->bind_param("i", $id);
$stmt->execute();

// Then delete the "other" record
$stmt2 = $conn->prepare("DELETE FROM others WHERE id = ?");
$stmt2->bind_param("i", $id);

if ($stmt2->execute()) {
    header("Location: manage_others.php?msg=deleted");
    exit;
} else {
    echo "<div class='alert alert-danger'>Error deleting record: " . $stmt2->error . "</div>";
}
?>
