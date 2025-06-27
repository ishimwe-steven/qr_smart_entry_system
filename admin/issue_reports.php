<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_form.php");
    exit;
}

include '../includes/db.php';
include '../includes/header.php';
include '../includes/admin_sidebar.php';
?>

<div class="d-flex">
  <div class="flex-grow-1 p-4" style="margin-left:300px;">
    <h2><i class="fas fa-exclamation-circle"></i> Reported Issues</h2>

    <!-- Search bar -->
    <form method="GET" class="row g-3 mb-3" style="max-width:600px;" onsubmit="return false;">
      <div class="col-md-10">
        <input type="text" id="searchInput" class="form-control" placeholder="Search by student, serial, reporter, or issue...">
      </div>
    </form>

    <!-- Export buttons -->
    <div class="mb-3">
      <a href="#" id="exportCsv" class="btn btn-outline-primary me-2"><i class="fas fa-file-csv"></i> Export CSV</a>
      <a href="#" id="exportPdf" class="btn btn-outline-danger" target="_blank"><i class="fas fa-file-pdf"></i> Export PDF</a>
    </div>

    <!-- Table -->
    <div class="table-responsive" id="reportResults">
      <p class="text-muted">Start typing to search reported issues...</p>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
$(document).ready(function() {
  function fetchReports(query = '') {
    $.ajax({
      url: 'search_issues.php',
      method: 'GET',
      data: { search: query },
      success: function(data) {
        $('#reportResults').html(data);
        // update export buttons with current search
        $('#exportCsv').attr('href', 'export_issues_csv.php?search=' + encodeURIComponent(query));
        $('#exportPdf').attr('href', 'export_issues_pdf.php?search=' + encodeURIComponent(query));
      }
    });
  }

  $('#searchInput').on('keyup', function() {
    const query = $(this).val();
    fetchReports(query);
  });

  fetchReports(); // load initial data
});
</script>
