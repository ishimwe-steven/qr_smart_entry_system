<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';

// ------------------ MOVEMENT FILTER ------------------
$mv_start = $_GET['mv_start'] ?? '';
$mv_end = $_GET['mv_end'] ?? '';

$mv_where = '';
$mv_params = [];
$mv_types = '';

if (!empty($mv_start) && !empty($mv_end)) {
    $mv_where = "WHERE m.entry_time BETWEEN ? AND ?";
    $mv_params = [$mv_start . ' 00:00:00', $mv_end . ' 23:59:59'];
    $mv_types = 'ss';
}

function build_where($where, $status) {
    return !empty($where) ? "$where AND m.status='$status'" : "WHERE m.status='$status'";
}

$sql_in = "SELECT COUNT(*) AS count FROM laptop_movements m " . build_where($mv_where, 'IN');
$sql_out = "SELECT COUNT(*) AS count FROM laptop_movements m " . build_where($mv_where, 'OUT');

$sql_inside = "
    SELECT COUNT(*) AS count FROM laptop_movements m
    LEFT JOIN (
        SELECT laptop_id, MAX(id) AS last_id
        FROM laptop_movements
        GROUP BY laptop_id
    ) t ON m.laptop_id = t.laptop_id AND m.id = t.last_id
";
$sql_inside .= !empty($mv_where) ? " $mv_where AND m.status='IN'" : " WHERE m.status='IN'";

function get_count($conn, $sql, $types, $params) {
    if ($types) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['count'];
    } else {
        return $conn->query($sql)->fetch_assoc()['count'];
    }
}

$in_count = get_count($conn, $sql_in, $mv_types, $mv_params);
$out_count = get_count($conn, $sql_out, $mv_types, $mv_params);
$inside_count = get_count($conn, $sql_inside, $mv_types, $mv_params);

// ------------------ ISSUE FILTER ------------------
$issue_start = $_GET['issue_start'] ?? '';
$issue_end = $_GET['issue_end'] ?? '';

$issue_where = '';
if (!empty($issue_start) && !empty($issue_end)) {
    $issue_where = "WHERE report_time BETWEEN '" . $issue_start . " 00:00:00' AND '" . $issue_end . " 23:59:59'";
}

$issue_sql = "SELECT COUNT(*) AS total_issues FROM issue_reports $issue_where";
$total_issues = $conn->query($issue_sql)->fetch_assoc()['total_issues'];
?>

<div class="d-flex">
  <?php include '../includes/admin_sidebar.php'; ?>

  <div class="flex-grow-1 p-4" style="margin-left:300px;">
    <h2>Reports</h2>

    <!-- MOVEMENT FILTER -->
    <form method="GET" class="row g-3 mb-4" style="max-width: 700px;">
      <input type="hidden" name="issue_start" value="<?= htmlspecialchars($issue_start) ?>">
      <input type="hidden" name="issue_end" value="<?= htmlspecialchars($issue_end) ?>">
      <div class="col-md-5">
        <label>Movement Start</label>
        <input type="date" name="mv_start" class="form-control" value="<?= htmlspecialchars($mv_start) ?>">
      </div>
      <div class="col-md-5">
        <label>Movement End</label>
        <input type="date" name="mv_end" class="form-control" value="<?= htmlspecialchars($mv_end) ?>">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-dark w-100"><i class="fas fa-filter"></i> Filter Movements</button>
      </div>
    </form>

    <!-- MOVEMENT SUMMARY -->
    <div class="row g-4 mb-5">
      <div class="col-md-4">
        <div class="card bg-info text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-door-open"></i> Total Entries</h5>
            <h2><?= $in_count ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-danger text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-door-closed"></i> Total Exits</h5>
            <h2><?= $out_count ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-success text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-laptop"></i> Laptops Inside</h5>
            <h2><?= $inside_count ?></h2>
          </div>
        </div>
      </div>
    </div>

    <!-- MOVEMENT EXPORT -->
    <div class="mb-5">
      <a href="export_csv.php?start_date=<?= urlencode($mv_start) ?>&end_date=<?= urlencode($mv_end) ?>" class="btn btn-outline-primary me-2">
        <i class="fas fa-file-csv"></i> Export Movements CSV
      </a>
      <a href="export_pdf.php?start_date=<?= urlencode($mv_start) ?>&end_date=<?= urlencode($mv_end) ?>" class="btn btn-outline-secondary" target="_blank">
        <i class="fas fa-file-pdf"></i> Export Movements PDF
      </a>
    </div>

    <!-- ISSUE FILTER -->
    <form method="GET" class="row g-3 mb-4" style="max-width: 700px;">
      <input type="hidden" name="mv_start" value="<?= htmlspecialchars($mv_start) ?>">
      <input type="hidden" name="mv_end" value="<?= htmlspecialchars($mv_end) ?>">
      <div class="col-md-5">
        <label>Issue Start</label>
        <input type="date" name="issue_start" class="form-control" value="<?= htmlspecialchars($issue_start) ?>">
      </div>
      <div class="col-md-5">
        <label>Issue End</label>
        <input type="date" name="issue_end" class="form-control" value="<?= htmlspecialchars($issue_end) ?>">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-dark w-100"><i class="fas fa-filter"></i> Filter Issues</button>
      </div>
    </form>

    <!-- ISSUE SUMMARY -->
    <div class="row g-4 mb-3">
      <div class="col-md-4">
        <div class="card bg-primary text-white shadow">
          <div class="card-body">
            <h5><i class="fas fa-exclamation-triangle"></i> Reported Issues</h5>
            <h2><?= $total_issues ?></h2>
          </div>
        </div>
      </div>
    </div>

    <!-- ISSUE EXPORT -->
    <div class="mb-4">
      <a href="export_issues_csv.php?start_date=<?= urlencode($issue_start) ?>&end_date=<?= urlencode($issue_end) ?>" class="btn btn-outline-success me-2">
        <i class="fas fa-file-csv"></i> Export Issues CSV
      </a>
      <a href="export_issues_pdf.php?start_date=<?= urlencode($issue_start) ?>&end_date=<?= urlencode($issue_end) ?>" class="btn btn-outline-danger" target="_blank">
        <i class="fas fa-file-pdf"></i> Export Issues PDF
      </a>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
