<?php
$conn = new mysqli("localhost", "root", "", "loptop");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
