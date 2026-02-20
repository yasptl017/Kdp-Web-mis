<?php
$targetDir = __DIR__ . "/uploads/editor/";

if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

if (!empty($_FILES['file']['name'])) {

    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];

    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid file type"]);
        exit;
    }

    $fileName = time() . "_" . rand(1000, 9999) . "." . $ext;
    $targetPath = $targetDir . $fileName;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        echo json_encode(["location" => "uploads/editor/" . $fileName]);
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Upload failed"]);
    }
}
?>
