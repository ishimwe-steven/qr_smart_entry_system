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

// Fetch all reported issues based on filter
$sql = "
  SELECT r.id, r.issue_description, r.report_time, r.status,
         s.first_name, s.last_name,
         l.serial_number,
         u.name AS reported_by
  FROM issue_reports r
  LEFT JOIN laptops l ON r.laptop_id = l.id
  LEFT JOIN students s ON l.student_id = s.id
  LEFT JOIN users u ON r.reported_by = u.id
  $where
  ORDER BY r.report_time DESC
";
$result = $conn->query($sql);
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

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
    <div class="table-responsive"style="width:97%">
      <table id="issuesTable" class="table table-bordered table-striped" style="width:100%;">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Student</th>
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
                     onclick="return confirm('Are you sure you want to mark this issue as solved?')">
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
      language: {
        search: "Search reports:"
      }
    });
  });
</script>
