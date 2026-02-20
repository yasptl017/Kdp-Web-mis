<?php
// File upload handling
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir  = __DIR__ . "/";  // save in current folder
        $uploadFile = $uploadDir . "combined_holdings.csv"; // always overwrite

        // Check file extension
        $fileType = strtolower(pathinfo($_FILES["csv_file"]["name"], PATHINFO_EXTENSION));
        if ($fileType !== "csv") {
            $message = "<div class='alert alert-danger text-center'>❌ Only CSV files are allowed.</div>";
        } else {
            if (move_uploaded_file($_FILES["csv_file"]["tmp_name"], $uploadFile)) {
                $message = "<div class='alert alert-success text-center'>✅ File uploaded successfully as <b>combined_holdings.csv</b></div>";
            } else {
                $message = "<div class='alert alert-danger text-center'>⚠️ Error uploading file.</div>";
            }
        }
    } else {
        $message = "<div class='alert alert-warning text-center'>⚠️ Please choose a CSV file to upload.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload combined_holdings.csv</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #6a11cb, #2575fc);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .upload-container {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            width: 450px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .upload-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .upload-header h2 {
            font-weight: bold;
            color: #333;
        }
        .btn-custom {
            width: 100%;
            border-radius: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <div class="upload-header">
            <h2>📂 Upload CSV File</h2>
            <p class="text-muted">This will overwrite <b>combined_holdings.csv</b></p>
        </div>

        <?php if (!empty($message)) echo $message; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="csv_file" class="form-label">Choose CSV File</label>
                <input type="file" class="form-control" name="csv_file" id="csv_file" accept=".csv" required>
            </div>
            <button type="submit" class="btn btn-primary btn-custom">⬆️ Upload & Overwrite</button>
        </form>
    </div>
</body>
</html>
