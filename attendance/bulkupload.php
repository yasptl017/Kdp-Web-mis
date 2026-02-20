<?php
include('dbconfig.php'); // Include database connection
// Optional message from add/update redirects
$msg = '';
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}

// Handle CSV file upload
if (isset($_POST['upload_csv'])) {
    if ($_FILES['csv_file']['name']) {
        // Get the file extension and check if it is a CSV file
        $ext = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);
        if ($ext !== 'csv') {
            $msg = "Only CSV files are allowed!";
        } else {
            // Open the uploaded CSV file
            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $header = fgetcsv($file, 0, ',', '"', '\\'); // Skip the header row

            // Loop through the CSV rows
            while (($row = fgetcsv($file, 0, ',', '"', '\\')) !== false) {
                // Extract the data from the row
                $term = $row[0];
                $enrollmentNo = $row[1];
                $name = $row[2];
                $sem = $row[3];
                $class = $row[4];
                $labBatch = $row[5];
                $tutBatch = $row[6];
                $status = 1; // Default status as active

                // Check if student already exists with the same term and enrollmentNo
                $stmt = $conn->prepare("SELECT id FROM students WHERE term = ? AND enrollmentNo = ?");
                $stmt->bind_param("ss", $term, $enrollmentNo);
                $stmt->execute();
                $result = $stmt->get_result();

                // If a duplicate entry exists, skip this row
                if ($result->num_rows == 0) {
                    // Insert the student data into the database
                    $insertStmt = $conn->prepare("INSERT INTO students (term, enrollmentNo, name, sem, class, labBatch, tutBatch, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $insertStmt->bind_param("sssssssi", $term, $enrollmentNo, $name, $sem, $class, $labBatch, $tutBatch, $status);
                    $insertStmt->execute();
                    $insertStmt->close();
                }

                $stmt->close();
            }

            fclose($file); // Close the file

            // Redirect to manage students page with a success message
            header("Location: managestudents.php?msg=Bulk upload successful!");
            exit();
        }
    } else {
        $msg = "Please upload a CSV file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include('head.php'); ?>
<body class="app">
<?php include('header.php'); ?>

<div class="app-wrapper">
    <div class="app-content pt-3 p-md-3 p-lg-4">
        <div class="container-xl">
            <h1 class="app-page-title"><i class="bi bi-upload me-2"></i>Bulk Upload Students</h1>

            <?php if ($msg): ?>
                <div class="alert alert-warning d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-12 col-lg-7">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <h4>Upload CSV File</h4>
                            <form method="POST" action="bulkupload.php" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Choose CSV File</label>
                                    <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                                </div>
                                <button type="submit" name="upload_csv" class="btn btn-primary">
                                    <i class="bi bi-cloud-upload me-1"></i>Upload &amp; Import
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-5 mt-3 mt-lg-0">
                    <div class="app-card shadow-sm h-100" style="background:linear-gradient(135deg,#e8eaf6,#f3f4fd);">
                        <div class="app-card-body">
                            <h4>CSV Format</h4>
                            <p class="text-muted" style="font-size:0.875rem;">Columns must be in this exact order (with header row):</p>
                            <?php if (file_exists(__DIR__ . '/upload.csv')): ?>
                                <a href="upload.csv" class="btn btn-sm btn-outline-primary mb-2" download>
                                    Download Sample CSV
                                </a>
                            <?php endif; ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-2" style="font-size:0.8rem;">
                                    <thead class="table-light">
                                        <tr><th>#</th><th>Column</th><th>Example</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>1</td><td>Term</td><td>2024</td></tr>
                                        <tr><td>2</td><td>Enrollment No</td><td>12345</td></tr>
                                        <tr><td>3</td><td>Name</td><td>John Doe</td></tr>
                                        <tr><td>4</td><td>Semester</td><td>3</td></tr>
                                        <tr><td>5</td><td>Class</td><td>A</td></tr>
                                        <tr><td>6</td><td>Lab Batch</td><td>A1</td></tr>
                                        <tr><td>7</td><td>Tut Batch</td><td>A1</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="mb-0 text-muted" style="font-size:0.8rem;">
                                <i class="bi bi-info-circle me-1"></i>Duplicates (same term + enrollment no) are skipped automatically.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div><!--//container-xl-->
    </div><!--//app-content-->
</div><!--//app-wrapper-->

<?php include('footer.php'); ?>
</body>
</html>

<?php
$conn->close();
?>
