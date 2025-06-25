<form method="POST" action="register.php">
  <h2>Register User</h2>
  Name: <input type="text" name="name" required><br>
  Email: <input type="email" name="email" required><br>
  Phone: <input type="text" name="phone"><br>
  Password: <input type="password" name="password" required><br>
  Role:
  <select name="role" required>
    <option value="">--Select Role--</option>
    <option value="admin">Admin</option>
    <option value="security">Security Guard</option>
    <option value="student">Student</option>
  </select><br>
  <button type="submit">Register</button>
</form>
