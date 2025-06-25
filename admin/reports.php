<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$where = '';
$params = [];
$types = '';

if (!empty($start_date) && !empty($end_date)) {
    $where = "WHERE m.entry_time BETWEEN ? AND ?";
    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
    $types = 'ss';
}

// Helper to build query where part correctly
function build_where($where, $status) {
    if (!empty($where)) {
        return "$where AND m.status='$status'";
    } else {
        return "WHERE m.status='$status'";
    }
}

// Queries
$sql_in = "SELECT COUNT(*) AS count FROM laptop_movements m " . build_where($where, 'IN');
$sql_out = "SELECT COUNT(*) AS count FROM laptop_movements m " . build_where($where, 'OUT');

// For inside, no need for WHERE on subquery
$sql_inside = "
  SELECT COUNT(*) AS count FROM laptop_movements m
  LEFT JOIN (
    SELECT laptop_id, MAX(id) AS last_id
    FROM laptop_movements
    GROUP BY laptop_id
  ) t ON m.laptop_id = t.laptop_id AND m.id = t.last_id
";
if (!empty($where)) {
    $sql_inside .= " $where AND m.status='IN'";
} else {
    $sql_inside .= " WHERE m.status='IN'";
}

// Execute
function get_count($conn, $sql, $types, $params) {
    if ($types) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res['count'];
    } else {
        return $conn->query($sql)->fetch_assoc()['count'];
    }
}

$in_count = get_count($conn, $sql_in, $types, $params);
$out_count = get_count($conn, $sql_out, $types, $params);
$inside_count = get_count($conn, $sql_inside, $types, $params);
?>

<div class="d-flex">
  <?php include '../includes/admin_sidebar.php'; ?>

  <div class="flex-grow-1 p-4" style="margin-left:300px;">
    <h2>Reports</h2>

    <!-- Date Filter -->
    <form method="GET" class="row g-3 mb-4" style="max-width:600px;">
      <div class="col-md-5">
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
      </div>
      <div class="col-md-5">
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
      </div>
      <div class="col-md-2">
        <button class="btn btn-secondary w-100"><i class="fas fa-search"></i> Filter</button>
      </div>
    </form>

    <!-- Summary -->
    <div class="row g-4">
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

    <!-- Placeholder for export -->
    <div class="mt-4">
      <button class="btn btn-outline-primary"><i class="fas fa-file-download"></i> Export CSV</button>
      <button class="btn btn-outline-secondary"><i class="fas fa-file-pdf"></i> Export PDF</button>
    </div>

  </div>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
