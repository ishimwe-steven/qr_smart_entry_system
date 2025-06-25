<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';

$id = intval($_GET['id']);

// Get current picture filename
$stmt = $conn->prepare("SELECT picture FROM students WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: manage_students.php");
    exit;
}

$data = $result->fetch_assoc();
$pictureFile = $data['picture'];

// Delete laptop first
$stmt = $conn->prepare("DELETE FROM laptops WHERE student_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

// Delete student
$stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

// Delete picture file
if (!empty($pictureFile)) {
    $filePath = "../uploads/" . $pictureFile;
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

header("Location: manage_students.php");
exit;
?>
