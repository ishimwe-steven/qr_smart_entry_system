<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';
include '../includes/admin_sidebar.php';

// Handle solve request
if (isset($_GET['solve'])) {
    $issue_id = intval($_GET['solve']);
    $conn->query("UPDATE issue_reports SET status='solved' WHERE id=$issue_id");
    header("Location: issue_reports.php?status=" . urlencode($_GET['status']));
    exit;
}

// Filter logic
$status_filter = $_GET['status'] ?? 'all';
$where = "";

if ($status_filter === 'solved') {
    $where = "WHERE r.status='solved'";
} elseif ($status_filter === 'unsolved') {
    $where = "WHERE r.status='unsolved'";
}

// Fetch all reported issues (Students + Others)
$sql = "
  SELECT 
      r.id,
      r.issue_description,
      r.report_time,
      r.status,
      COALESCE(s.first_name, o.first_name) AS first_name,
      COALESCE(s.last_name, o.last_name) AS last_name,
      COALESCE(o.national_id, '') AS national_id,
      CASE 
          WHEN s.id IS NOT NULL THEN 'Student'
          ELSE o.role
      END AS role,
      l.serial_number,
      u.name AS reported_by
  FROM issue_reports r
  LEFT JOIN laptops l ON r.laptop_id = l.id
  LEFT JOIN students s ON l.student_id = s.id
  LEFT JOIN others o ON l.other_id = o.id
  LEFT JOIN users u ON r.reported_by = u.id
  $where
  ORDER BY r.report_time DESC
";
$result = $conn->query($sql);
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<style>
  body { background-color: #f4f6f9; }
  .card { border: none; border-radius: 0.75rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,.15); }
  .flex-grow-1 { margin-left: 300px; padding: 2rem; }
  h2 {
    font-size: 2.5rem;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    color:black;
    display:flex;
    align-items:center;
    gap:0.5rem;
  }
  .form-label { color:#2c3e50; font-weight:500; }
  .form-select {
    color:black; border:1px solid #2c3e50; border-radius:0.5rem; padding:0.5rem;
    width:200px; transition:border-color 0.3s ease;
  }
  .form-select:focus {
    border-color:#00c4ff; outline:none; box-shadow:0 0 5px rgba(0,196,255,0.5);
  }
  .btn-secondary {
    background:linear-gradient(45deg,#6c757d,#82909d); border:none;
    border-radius:0.5rem; font-weight:500;
  }
  .btn-secondary:hover {
    background:linear-gradient(45deg,#5a6268,#6f7b86);
    transform:translateY(-2px); box-shadow:0 4px 15px rgba(108,117,125,0.4);
  }
  .btn-outline-primary {
    border-color:#007bff; color:#007bff; border-radius:0.5rem; font-weight:500;
  }
  .btn-outline-primary:hover {
    background:linear-gradient(45deg,#007bff,#00c4ff);
    color:#fff; border-color:transparent;
  }
  .btn-outline-danger {
    border-color:#dc3545; color:#dc3545; border-radius:0.5rem; font-weight:500;
  }
  .btn-outline-danger:hover {
    background:linear-gradient(45deg,#dc3545,#ff4d4d);
    color:#fff; border-color:transparent;
  }
  .btn-success {
    background:linear-gradient(45deg,#28a745,#34c759);
    border:none; border-radius:0.5rem; font-weight:500;
  }
  .btn-success:hover {
    background:linear-gradient(45deg,#218838,#2cb050);
    transform:translateY(-2px); box-shadow:0 4px 15px rgba(40,167,69,0.4);
  }
  .table-responsive {
    width:97%; background:rgba(255,255,255,0.95);
    border-radius:1rem; box-shadow:0 8px 32px rgba(31,38,135,0.37);
    overflow:hidden;
  }
  .table { margin-bottom:0; color:#2c3e50; }
  .table thead.table-dark {
    background:linear-gradient(45deg,#2c3e50,#34495e);
    color:#fff;
  }
  .table tbody tr:hover { background:#f1f3f5; }
  .badge.bg-success {
    background:linear-gradient(45deg,#28a745,#34c759)!important;
    border-radius:0.5rem; padding:0.5rem 1rem; font-weight:500;
  }
  .badge.bg-danger {
    background:linear-gradient(45deg,#dc3545,#ff4d4d)!important;
    border-radius:0.5rem; padding:0.5rem 1rem; font-weight:500;
  }
</style>

<div class="d-flex">
  <div class="flex-grow-1 p-4" style="margin-left:300px;">
    <h2><i class="fas fa-exclamation-circle"></i> Reported Issues</h2>

    <!-- Filter Dropdown -->
    <form method="GET" class="mb-3">
      <label for="status" class="form-label">Filter by Status:</label>
      <select name="status" id="status" class="form-select" style="width:200px; display:inline-block;">
        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All</option>
        <option value="solved" <?= $status_filter === 'solved' ? 'selected' : '' ?>>Solved</option>
        <option value="unsolved" <?= $status_filter === 'unsolved' ? 'selected' : '' ?>>Unsolved</option>
      </select>
      <button type="submit" class="btn btn-secondary ms-2">Apply</button>
    </form>

    <!-- Export Buttons -->
    <div class="mb-3">
      <a href="export_issues_csv.php?status_filter=<?= urlencode($status_filter) ?>" class="btn btn-outline-primary me-2">
        <i class="fas fa-file-csv"></i> Export CSV
      </a>
      <a href="export_issues_pdf.php?status_filter=<?= urlencode($status_filter) ?>" class="btn btn-outline-danger" target="_blank">
        <i class="fas fa-file-pdf"></i> Export PDF
      </a>
    </div>

    <!-- Table -->
    <div class="table-responsive" style="width:97%">
      <table id="issuesTable" class="table table-bordered table-striped" style="width:100%;">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>National ID</th>
            <th>Role</th>
            <th>Serial</th>
            <th>Reported By</th>
            <th>Description</th>
            <th>Reported At</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
              <td><?= htmlspecialchars($row['national_id']) ?></td>
              <td><?= htmlspecialchars($row['role']) ?></td>
              <td><?= htmlspecialchars($row['serial_number']) ?></td>
              <td><?= htmlspecialchars($row['reported_by']) ?></td>
              <td><?= htmlspecialchars($row['issue_description']) ?></td>
              <td><?= htmlspecialchars($row['report_time']) ?></td>
              <td>
                <span class="badge <?= strtolower($row['status']) === 'solved' ? 'bg-success' : 'bg-danger' ?>">
                  <?= ucfirst($row['status']) ?>
                </span>
              </td>
              <td>
                <?php if (strtolower($row['status']) === 'unsolved'): ?>
                  <a href="?solve=<?= $row['id'] ?>&status=<?= urlencode($status_filter) ?>"
                     class="btn btn-sm btn-success"
                     onclick="return confirm('Mark this issue as solved?')">
                    <i class="fas fa-check-circle"></i> Mark Solved
                  </a>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
  $(document).ready(function () {
    $('#issuesTable').DataTable({
      pageLength: 15,
      lengthChange: false,
      language: { search: "Search reports:" }
    });
  });
</script>
