<?php
session_start();
include '../includes/db.php';

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

        if ($user['role'] == 'admin') {
            header("Location: ../admin/dashboard.php");
            exit;
        } elseif ($user['role'] == 'security') {
            header("Location: ../security/dashboard.php");
            exit;
        } else {
            echo "<script>alert('Unknown role!'); window.location.href = 'login_form.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Wrong password!'); window.location.href = 'login_form.php';</script>";
        exit;
    }
} else {
    echo "<script>alert('User not found!'); window.location.href = 'login_form.php';</script>";
    exit;
}
?>
