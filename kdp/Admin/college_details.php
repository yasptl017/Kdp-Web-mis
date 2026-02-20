<!DOCTYPE html>
<html lang="en">
<?php 
session_start();
include "dbconfig.php"; 
include "head.php"; 

// Initialize variables
$message = "";
$messageType = "";
$collegeData = null;

// Create uploads directory if it doesn't exist
$uploadDir = "uploads/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Fetch existing college details
$sql = "SELECT * FROM college_details WHERE id = 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $collegeData = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $college_name = $conn->real_escape_string($_POST['college_name']);
    $college_alt_name = $conn->real_escape_string($_POST['college_alt_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $address = $conn->real_escape_string($_POST['address']);
    $contact_no = $conn->real_escape_string($_POST['contact_no']);

    // Summernote fields (HTML allowed)
    $college_description = $conn->real_escape_string($_POST['college_description']);
    $vision = $conn->real_escape_string($_POST['vision']);
    $mission = $conn->real_escape_string($_POST['mission']);
    $principal_message = $conn->real_escape_string($_POST['principal_message']);

    $principal_name = $conn->real_escape_string($_POST['principal_name']);

    $facebook_link = $conn->real_escape_string($_POST['facebook_link']);
    $instagram_link = $conn->real_escape_string($_POST['instagram_link']);
    $twitter_link = $conn->real_escape_string($_POST['twitter_link']);
    $linkedin_link = $conn->real_escape_string($_POST['linkedin_link']);

    // File upload variables
    $logo_1 = $collegeData['logo_1'] ?? "";
    $logo_2 = $collegeData['logo_2'] ?? "";
    $principal_photo = $collegeData['principal_photo'] ?? "";

    $allowed = ["jpg","jpeg","png","gif","webp"];

    function uploadFile($key, $oldFile, $uploadDir, $prefix, $allowed) {
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $newName = $uploadDir . $prefix . "_" . time() . "." . $ext;
                if (move_uploaded_file($_FILES[$key]['tmp_name'], $newName)) {
                    if (!empty($oldFile) && file_exists($oldFile)) unlink($oldFile);
                    return $newName;
                }
            }
        }
        return $oldFile;
    }

    $logo_1 = uploadFile("logo_1", $logo_1, $uploadDir, "logo1", $allowed);
    $logo_2 = uploadFile("logo_2", $logo_2, $uploadDir, "logo2", $allowed);
    $principal_photo = uploadFile("principal_photo", $principal_photo, $uploadDir, "principal", $allowed);

    // Update college details
    $sql = "
        UPDATE college_details SET
            college_name = '$college_name',
            college_alt_name = '$college_alt_name',
            logo_1 = '$logo_1',
            logo_2 = '$logo_2',
            email = '$email',
            address = '$address',
            contact_no = '$contact_no',
            college_description = '$college_description',
            vision = '$vision',
            mission = '$mission',
            principal_name = '$principal_name',
            principal_message = '$principal_message',
            principal_photo = '$principal_photo',
            facebook_link = '$facebook_link',
            instagram_link = '$instagram_link',
            twitter_link = '$twitter_link',
            linkedin_link = '$linkedin_link'
        WHERE id = 1
    ";

    if ($conn->query($sql)) {
        $message = "College details updated successfully!";
        $messageType = "success";

        $result = $conn->query("SELECT * FROM college_details WHERE id = 1");
        if ($result && $result->num_rows > 0) {
            $collegeData = $result->fetch_assoc();
        }
    } else {
        $message = "Error: " . $conn->error;
        $messageType = "danger";
    }
}
?>

<!-- Page-specific CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">

<style>
    .form-card {
        background: #fff;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
    }
    .form-card h5 {
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e9ecef;
    }
    .img-preview {
        max-height: 120px;
        border-radius: 6px;
        margin-top: 10px;
        display: block;
        border: 2px solid #dee2e6;
        padding: 5px;
        background: #f8f9fa;
    }
    .note-editor.note-frame {
        border: 1px solid #ced4da;
    }
</style>

<body>

<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

    <h2 class="h4 mb-4"><i class="fas fa-university me-2"></i>College Details Management</h2>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="collegeForm">

        <!-- BASIC INFO -->
        <div class="form-card">
            <h5><i class="fas fa-info-circle me-2"></i>Basic Information</h5>

            <div class="mb-3">
                <label class="form-label">College Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="college_name" 
                    value="<?php echo htmlspecialchars($collegeData['college_name'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">College Alt Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="college_alt_name" 
                    value="<?php echo htmlspecialchars($collegeData['college_alt_name'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" name="email" 
                    value="<?php echo htmlspecialchars($collegeData['email'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="contact_no" 
                    value="<?php echo htmlspecialchars($collegeData['contact_no'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Address <span class="text-danger">*</span></label>
                <textarea name="address" class="form-control" rows="3" required><?php 
                    echo htmlspecialchars($collegeData['address'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- COLLEGE DESCRIPTION -->
        <div class="form-card">
            <h5><i class="fas fa-align-left me-2"></i>College Description</h5>
            <textarea id="college_description" name="college_description" class="summernote"><?php 
                echo $collegeData['college_description'] ?? ''; ?></textarea>
        </div>

        <!-- LOGOS -->
        <div class="form-card">
            <h5><i class="fas fa-image me-2"></i>College Logos</h5>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Logo 1</label>
                    <input type="file" name="logo_1" class="form-control" accept="image/*">
                    <?php if (!empty($collegeData['logo_1']) && file_exists($collegeData['logo_1'])): ?>
                        <img src="<?php echo htmlspecialchars($collegeData['logo_1']); ?>" class="img-preview" alt="Logo 1">
                    <?php endif; ?>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Logo 2</label>
                    <input type="file" name="logo_2" class="form-control" accept="image/*">
                    <?php if (!empty($collegeData['logo_2']) && file_exists($collegeData['logo_2'])): ?>
                        <img src="<?php echo htmlspecialchars($collegeData['logo_2']); ?>" class="img-preview" alt="Logo 2">
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- VISION -->
        <div class="form-card">
            <h5><i class="fas fa-bullseye me-2"></i>Vision</h5>
            <textarea id="vision" name="vision" class="summernote"><?php 
                echo $collegeData['vision'] ?? ''; ?></textarea>
        </div>

        <!-- MISSION -->
        <div class="form-card">
            <h5><i class="fas fa-flag me-2"></i>Mission</h5>
            <textarea id="mission" name="mission" class="summernote"><?php 
                echo $collegeData['mission'] ?? ''; ?></textarea>
        </div>

        <!-- PRINCIPAL -->
        <div class="form-card">
            <h5><i class="fas fa-user-tie me-2"></i>Principal Information</h5>

            <div class="mb-3">
                <label class="form-label">Principal Name <span class="text-danger">*</span></label>
                <input type="text" name="principal_name" class="form-control" 
                    value="<?php echo htmlspecialchars($collegeData['principal_name'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Principal Photo</label>
                <input type="file" name="principal_photo" class="form-control" accept="image/*">
                <?php if (!empty($collegeData['principal_photo']) && file_exists($collegeData['principal_photo'])): ?>
                    <img src="<?php echo htmlspecialchars($collegeData['principal_photo']); ?>" class="img-preview" alt="Principal Photo">
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Principal Message</label>
                <textarea id="principal_message" name="principal_message" class="summernote"><?php 
                    echo $collegeData['principal_message'] ?? ''; ?></textarea>
            </div>
        </div>

        <!-- SOCIAL LINKS -->
        <div class="form-card">
            <h5><i class="fas fa-share-alt me-2"></i>Social Links</h5>

            <div class="mb-3">
                <label class="form-label"><i class="fab fa-facebook text-primary me-2"></i>Facebook URL</label>
                <input type="url" name="facebook_link" class="form-control"
                       value="<?php echo htmlspecialchars($collegeData['facebook_link'] ?? ''); ?>" 
                       placeholder="https://facebook.com/yourpage">
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="fab fa-instagram text-danger me-2"></i>Instagram URL</label>
                <input type="url" name="instagram_link" class="form-control"
                       value="<?php echo htmlspecialchars($collegeData['instagram_link'] ?? ''); ?>" 
                       placeholder="https://instagram.com/yourpage">
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="fab fa-twitter text-info me-2"></i>Twitter URL</label>
                <input type="url" name="twitter_link" class="form-control"
                       value="<?php echo htmlspecialchars($collegeData['twitter_link'] ?? ''); ?>" 
                       placeholder="https://twitter.com/yourpage">
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="fab fa-linkedin text-primary me-2"></i>LinkedIn URL</label>
                <input type="url" name="linkedin_link" class="form-control"
                       value="<?php echo htmlspecialchars($collegeData['linkedin_link'] ?? ''); ?>" 
                       placeholder="https://linkedin.com/company/yourpage">
            </div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-save me-2"></i>Save Details
            </button>
            <button type="reset" class="btn btn-secondary btn-lg">
                <i class="fas fa-undo me-2"></i>Reset
            </button>
        </div>

    </form>
</main>

<?php include "footer.php"; ?>

<!-- Page-specific Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>

<script>
console.log('=== College Details Management ===');

// Summernote configuration
const summernoteConfig = {
    height: 300,
    placeholder: 'Enter content here...',
    toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'clear']],
        ['fontname', ['fontname']],
        ['fontsize', ['fontsize']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['table', ['table']],
        ['insert', ['link', 'picture']],
        ['view', ['fullscreen', 'codeview', 'help']]
    ],
    popover: {
        image: [
            ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter', 'resizeNone']],
            ['float', ['floatLeft', 'floatRight', 'floatNone']],
            ['remove', ['removeMedia']]
        ],
        link: [
            ['link', ['linkDialogShow', 'unlink']]
        ],
        table: [
            ['add', ['addRowDown', 'addRowUp', 'addColLeft', 'addColRight']],
            ['delete', ['deleteRow', 'deleteCol', 'deleteTable']]
        ]
    }
};

// Initialize all Summernote editors
$(document).ready(function() {
    console.log('Initializing Summernote editors...');
    
    // Check if Summernote loaded
    if (typeof $.fn.summernote === 'undefined') {
        alert('Summernote failed to load. Please check your internet connection and refresh the page.');
        return;
    }
    
    // Initialize all editors
    $('#college_description').summernote(summernoteConfig);
    console.log('✓ College Description editor initialized');
    
    $('#vision').summernote(summernoteConfig);
    console.log('✓ Vision editor initialized');
    
    $('#mission').summernote(summernoteConfig);
    console.log('✓ Mission editor initialized');
    
    $('#principal_message').summernote(summernoteConfig);
    console.log('✓ Principal Message editor initialized');
    
    console.log('✓ All Summernote editors initialized successfully!');
});

console.log('✓ College Details page loaded');
</script>

</body>
</html>