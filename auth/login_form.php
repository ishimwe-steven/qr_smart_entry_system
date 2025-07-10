<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Smart Entry System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome for icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <style>
    body {
      background:rgb(210, 223, 236);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #ffffff;
    }
    .login-card {
      width: 100%;
      max-width: 400px;
      padding: 2rem;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 1rem;
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
    }
    .login-card h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      font-weight: 700;
      color: #2c3e50;
      text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    .login-card h2 i {
      margin-right: 0.5rem;
    }
    .form-label {
      color: #2c3e50;
      font-weight: 500;
    }
    .form-control {
     
      color: #ffffff;
      border: 1px solid #2c3e50;
      border-radius: 0.5rem;
      padding: 0.5rem;
      transition: border-color 0.3s ease;
    }
    .form-control:focus {
      border-color: #00c4ff;
      outline: none;
      box-shadow: 0 0 5px rgba(0, 196, 255, 0.5);
    }
    .form-control::placeholder {
      color: #a0aec0;
    }
    .btn-primary {
      background: linear-gradient(45deg, #007bff, #00c4ff);
      border: none;
      border-radius: 0.5rem;
      font-weight: 500;
      padding: 0.75rem;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .btn-primary:hover {
      background: linear-gradient(45deg, #0056b3, #0099cc);
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
    }
    @media (max-width: 576px) {
      .login-card {
        max-width: 90%;
        padding: 1.5rem;
      }
      .login-card h2 {
        font-size: 1.75rem;
      }
    }
  </style>
</head>
<body>

<div class="login-card">
  <h2><i class="fas fa-lock"></i> Login</h2>
  <form method="POST" action="login.php">
    <div class="mb-3">
      <label for="email" class="form-label">Email address</label>
      <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required>
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Login</button>
  </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</body>
</html>