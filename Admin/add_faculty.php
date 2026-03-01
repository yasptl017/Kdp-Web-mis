<?php
// add_faculty.php
// Full-page Add / Edit Faculty form with Summernote rich text editor

session_start();
include "dbconfig.php";
include "head.php";

$msg = "";
$msgType = "success";
$isEdit = false;
$faculty = null;

// Upload directory
$uploadDir = "uploads/faculty/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// If editing, load faculty
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $res = $conn->query("SELECT * FROM faculty WHERE id = $id LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $faculty = $res->fetch_assoc();
        $isEdit = true;
    } else {
        header("Location: manage_faculty.php");
        exit;
    }
}

// Fetch departments for dropdown - Filter by role
if ($_SESSION['role'] == 'Admin') {
    $departments = $conn->query("SELECT department FROM departments WHERE visibility = 1 ORDER BY department ASC");
} else {
    $userDept = $conn->real_escape_string($_SESSION['user_name']);
    $departments = $conn->query("SELECT department FROM departments WHERE visibility = 1 AND department='$userDept' ORDER BY department ASC");
}

// Handle Save (Insert / Update)
if (isset($_POST['save_faculty'])) {

    $id = isset($_POST['faculty_id']) ? intval($_POST['faculty_id']) : 0;

    // Basic fields
    $department = $conn->real_escape_string($_POST['department'] ?? '');
    $idx = is_numeric($_POST['idx'] ?? null) ? intval($_POST['idx']) : ( $_POST['idx'] === '' ? 'NULL' : $conn->real_escape_string($_POST['idx']) );

    $name = $conn->real_escape_string($_POST['name'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $phone = $conn->real_escape_string($_POST['phone'] ?? '');
    $designation = $conn->real_escape_string($_POST['designation'] ?? '');
    $date_of_joining = !empty($_POST['date_of_joining']) ? $conn->real_escape_string($_POST['date_of_joining']) : NULL;
    $experience_years = $conn->real_escape_string($_POST['experience_years'] ?? '');

    // Text / Summernote fields (allow HTML)
    $education = $conn->real_escape_string($_POST['education'] ?? '');
    $work_experience = $conn->real_escape_string($_POST['work_experience'] ?? '');

    $skills = $conn->real_escape_string($_POST['skills'] ?? '');
    $course_taught = $conn->real_escape_string($_POST['course_taught'] ?? '');
    $training = $conn->real_escape_string($_POST['training'] ?? '');
    $portfolio = $conn->real_escape_string($_POST['portfolio'] ?? '');
    $research_projects = $conn->real_escape_string($_POST['research_projects'] ?? '');
    $publications = $conn->real_escape_string($_POST['publications'] ?? '');
    $academic_projects = $conn->real_escape_string($_POST['academic_projects'] ?? '');
    $patents = $conn->real_escape_string($_POST['patents'] ?? '');
    $memberships = $conn->real_escape_string($_POST['memberships'] ?? '');
    $awards = $conn->real_escape_string($_POST['awards'] ?? '');

    $expert_lectures = $conn->real_escape_string($_POST['expert_lectures'] ?? '');

    // Extra dynamic fields -> build JSON
    $extra_json = null;
    if (isset($_POST['extra_title']) && is_array($_POST['extra_title'])) {
        $extras = [];
        $titles = $_POST['extra_title'];
        $descs = $_POST['extra_desc'] ?? [];
        for ($i = 0; $i < count($titles); $i++) {
            $t = trim($titles[$i]);
            $d = $descs[$i] ?? '';
            if ($t === '' && trim(strip_tags($d)) === '') continue;
            $extras[] = ['title' => $t, 'desc' => $d];
        }
        if (!empty($extras)) {
            $extra_json = $conn->real_escape_string(json_encode($extras));
        }
    }

    // Handle photo upload
    $oldPhoto = '';
    if ($id > 0) {
        $q = $conn->query("SELECT photo FROM faculty WHERE id=$id");
        if ($q && $q->num_rows > 0) $oldPhoto = $q->fetch_assoc()['photo'];
    }

    $photoPath = $oldPhoto;
    if (isset($_FILES['photo']) && isset($_FILES['photo']['name']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $fileName = 'faculty_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $target = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                // remove old file
                if (!empty($oldPhoto) && file_exists($oldPhoto)) unlink($oldPhoto);
                $photoPath = $target;
            }
        } else {
            $msg = "Photo file type not allowed.";
            $msgType = "danger";
        }
    }

    // Prepare idx for SQL
    $idx_sql = ($idx === 'NULL') ? "NULL" : "'$idx'";

    if ($id == 0) {
        // INSERT
        $sql = "INSERT INTO faculty
            (department, idx, name, photo, email, phone, designation, date_of_joining, experience_years,
             education, work_experience, skills, course_taught, training, portfolio, research_projects,
             publications, academic_projects, patents, memberships, awards, expert_lectures, extra_fields)
         VALUES (
            '$department', $idx_sql, '$name', " . ($photoPath ? "'$photoPath'" : "NULL") . ", '$email', '$phone', '$designation', " . ($date_of_joining ? "'$date_of_joining'" : "NULL") . ",
            '$experience_years', '$education', '$work_experience', '$skills', '$course_taught', '$training', '$portfolio',
            '$research_projects', '$publications', '$academic_projects', '$patents', '$memberships', '$awards',
            '$expert_lectures', " . ($extra_json !== null ? "'$extra_json'" : "NULL") . "
         )";
        if ($conn->query($sql)) {
            header("Location: manage_faculty.php?msg=added");
            exit;
        } else {
            $msg = "Error inserting: " . $conn->error;
            $msgType = "danger";
        }
    } else {
        // UPDATE
        $sql = "UPDATE faculty SET
            department='$department',
            idx=$idx_sql,
            name='$name',
            photo=" . ($photoPath ? "'$photoPath'" : "NULL") . ",
            email='$email',
            phone='$phone',
            designation='$designation',
            date_of_joining=" . ($date_of_joining ? "'$date_of_joining'" : "NULL") . ",
            experience_years='$experience_years',
            education='$education',
            work_experience='$work_experience',
            skills='$skills',
            course_taught='$course_taught',
            training='$training',
            portfolio='$portfolio',
            research_projects='$research_projects',
            publications='$publications',
            academic_projects='$academic_projects',
            patents='$patents',
            memberships='$memberships',
            awards='$awards',
            expert_lectures='$expert_lectures',
            extra_fields=" . ($extra_json !== null ? "'$extra_json'" : "NULL") . "
            WHERE id = $id";
        if ($conn->query($sql)) {
            header("Location: manage_faculty.php?msg=updated");
            exit;
        } else {
            $msg = "Error updating: " . $conn->error;
            $msgType = "danger";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Faculty</title>

    <!-- Summernote CSS - Must be in HEAD -->
<style>
        .section-divider { 
            border-top: 2px solid #eee; 
            margin: 2rem 0; 
            padding-top: 1rem; 
        }
        .section-title { 
            font-weight: 600; 
            margin-bottom: 1rem; 
            font-size: 1.1rem; 
            color: #2c3e50;
        }
        .img-preview { 
            max-height: 120px; 
            border-radius: 6px; 
            display: block; 
            margin-top: 10px; 
        }
        .extra-field { 
            background: #f8f9fa; 
            border: 1px solid #dee2e6; 
            padding: 15px; 
            border-radius: 6px; 
            margin-bottom: 15px; 
        }
        .form-card { 
            background: #fff; 
            padding: 2rem; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .note-editor.note-frame {
            border: 1px solid #ced4da;
        }
    </style>
</head>
<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0">
            <i class="fas fa-chalkboard-teacher me-2"></i>
            <?php echo $isEdit ? 'Edit Faculty' : 'Add New Faculty'; ?>
        </h2>
        <a href="manage_faculty.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show">
            <?php echo $msg; ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" enctype="multipart/form-data" id="facultyForm">
            <input type="hidden" name="faculty_id" value="<?php echo $faculty['id'] ?? 0; ?>">

            <!-- DEPARTMENT INFO -->
            <div class="section-title">Department Information</div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department <span class="text-danger">*</span></label>
                    <select name="department" id="department" class="form-control" required>
                        <option value="">-- Select Department --</option>
                        <?php while ($dept = $departments->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($dept['department']); ?>"
                                <?php echo (isset($faculty['department']) && $faculty['department'] == $dept['department']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Index</label>
                    <input type="number" name="idx" id="idx" class="form-control"
                           value="<?php echo htmlspecialchars($faculty['idx'] ?? ''); ?>">
                </div>
            </div>

            <div class="section-divider"></div>

            <!-- PERSONAL DETAILS -->
            <div class="section-title">Personal Details</div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" required
                           value="<?php echo htmlspecialchars($faculty['name'] ?? ''); ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Photo</label>
                    <input type="file" name="photo" id="photo" class="form-control" accept="image/*" 
                           onchange="previewPhoto(this)">
                    <div class="mt-2">
                        <?php if (!empty($faculty['photo']) && file_exists($faculty['photo'])): ?>
                            <img src="<?php echo $faculty['photo']; ?>" id="photoPreview" class="img-preview">
                        <?php else: ?>
                            <img src="" id="photoPreview" class="img-preview" style="display:none;">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control"
                           value="<?php echo htmlspecialchars($faculty['email'] ?? ''); ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" id="phone" class="form-control"
                           value="<?php echo htmlspecialchars($faculty['phone'] ?? ''); ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" id="designation" class="form-control"
                           value="<?php echo htmlspecialchars($faculty['designation'] ?? ''); ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Date of Joining</label>
                    <input type="date" name="date_of_joining" id="date_of_joining" class="form-control"
                           value="<?php echo htmlspecialchars($faculty['date_of_joining'] ?? ''); ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Experience (Years)</label>
                    <input type="text" name="experience_years" id="experience_years" class="form-control"
                           value="<?php echo htmlspecialchars($faculty['experience_years'] ?? ''); ?>">
                </div>
            </div>

            <div class="section-divider"></div>

            <!-- EDUCATION QUALIFICATION -->
            <div class="section-title">Education Qualification</div>
            <div class="mb-3">
                <label class="form-label">Education (Duration | Qualification | University - one per line)</label>
                <textarea name="education" id="education" class="form-control" rows="3"><?php echo htmlspecialchars($faculty['education'] ?? ''); ?></textarea>
            </div>

            <div class="section-divider"></div>

            <!-- WORK EXPERIENCE -->
            <div class="section-title">Work Experience</div>
            <div class="mb-3">
                <label class="form-label">Work Experience (Duration | Institute | Designation - one per line)</label>
                <textarea name="work_experience" id="work_experience" class="form-control" rows="3"><?php echo htmlspecialchars($faculty['work_experience'] ?? ''); ?></textarea>
            </div>

            <div class="section-divider"></div>

            <!-- SKILLS AND KNOWLEDGE -->
            <div class="section-title">Skills and Knowledge</div>
            <div class="mb-3">
                <textarea name="skills" id="skills" class="summernote"><?php echo htmlspecialchars($faculty['skills'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="section-divider"></div>

            <!-- COURSE TAUGHT -->
            <div class="section-title">Course Taught</div>
            <div class="mb-3">
                <textarea name="course_taught" id="course_taught" class="summernote"><?php echo htmlspecialchars($faculty['course_taught'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="section-divider"></div>

            <!-- TRAINING AND WORKSHOP -->
            <div class="section-title">Training and Workshop</div>
            <div class="mb-3">
                <textarea name="training" id="training" class="summernote"><?php echo htmlspecialchars($faculty['training'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="section-divider"></div>

            <!-- PORTFOLIOS -->
            <div class="section-title">Portfolios</div>
            <div class="mb-3">
                <textarea name="portfolio" id="portfolio" class="summernote"><?php echo htmlspecialchars($faculty['portfolio'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="section-divider"></div>

            <!-- RESEARCH PROJECTS -->
            <div class="section-title">Research Projects</div>
            <div class="mb-3">
                <textarea name="research_projects" id="research_projects" class="summernote"><?php echo htmlspecialchars($faculty['research_projects'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="section-divider"></div>

            <!-- PUBLICATIONS -->
            <div class="section-title">Publications</div>
            <div class="mb-3">
                <textarea name="publications" id="publications" class="summernote"><?php echo htmlspecialchars($faculty['publications'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="section-divider"></div>

            <!-- ACADEMIC PROJECTS -->
            <div class="section-title">Academic Projects</div>
            <div class="mb-3">
                <textarea name="academic_projects" id="academic_projects" class="summernote"><?php echo htmlspecialchars($faculty['academic_projects'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="section-divider"></div>

            <!-- PATENTS -->
            <div class="section-title">Patents</div>
            <div class="mb-3">
                <textarea name="patents" id="patents" class="summernote"><?php echo htmlspecialchars($faculty['patents'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="section-divider"></div>

            <!-- PROFESSIONAL MEMBERSHIPS -->
            <div class="section-title">Professional Institution Memberships</div>
            <div class="mb-3">
                <textarea name="memberships" id="memberships" class="summernote"><?php echo htmlspecialchars($faculty['memberships'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="section-divider"></div>

            <!-- EXPERT LECTURES -->
            <div class="section-title">Expert Lectures</div>
            <div class="mb-3">
                <label class="form-label">Expert Lectures (Date | Location | Topic | Remark - one per line)</label>
                <textarea name="expert_lectures" id="expert_lectures" class="form-control" rows="3"><?php echo htmlspecialchars($faculty['expert_lectures'] ?? ''); ?></textarea>
            </div>

            <div class="section-divider"></div>

            <!-- AWARDS -->
            <div class="section-title">Awards</div>
            <div class="mb-3">
                <textarea name="awards" id="awards" class="summernote"><?php echo htmlspecialchars($faculty['awards'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="section-divider"></div>

            <!-- DYNAMIC EXTRA FIELDS -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Additional Custom Fields</h5>
                <button type="button" class="btn btn-primary" id="addExtraBtn">
                    <i class="fas fa-plus"></i> Add Field
                </button>
            </div>

            <div id="extraFieldsContainer">
                <!-- Dynamic fields will be added here -->
            </div>

            <template id="extraFieldTemplate">
                <div class="extra-field">
                    <div class="d-flex justify-content-between mb-2">
                        <div style="flex:1">
                            <label class="form-label">Field Title</label>
                            <input type="text" name="extra_title[]" class="form-control extra-title" 
                                   placeholder="Enter field title">
                        </div>
                        <div class="ms-2 align-self-end">
                            <button type="button" class="btn btn-danger btn-sm removeExtra">
                                <i class="fas fa-times"></i> Remove
                            </button>
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label">Description</label>
                        <textarea name="extra_desc[]" class="form-control extra-desc"></textarea>
                    </div>
                </div>
            </template>

            <div class="section-divider"></div>

            <!-- SUBMIT BUTTONS -->
            <div class="d-flex gap-2">
                <button type="submit" name="save_faculty" class="btn btn-success btn-lg">
                    <i class="fas fa-save"></i> <?php echo $isEdit ? 'Update' : 'Save'; ?>
                </button>
                <a href="manage_faculty.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>

        </form>
    </div>
</main>

<?php include "footer.php"; ?>

<!-- Summernote JS - Load AFTER jQuery from footer -->
<script>
// Wait for DOM and all scripts to load
$(document).ready(function() {
    
    // Check if Summernote loaded
    if (typeof $.fn.summernote === 'undefined') {
        alert('Summernote failed to load. Please check your internet connection and refresh the page.');
        return;
    }
    
    console.log('Summernote loaded successfully!');
    
    // Photo preview function
    window.previewPhoto = function(input) {
        const preview = document.getElementById('photoPreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => { 
                preview.src = e.target.result; 
                preview.style.display = 'block'; 
            };
            reader.readAsDataURL(input.files[0]);
        }
    };
    
    // Summernote configuration with full features
// Summernote config moved to summernote-config.js
    
    // Initialize all main Summernote editors
    console.log('Initializing main Summernote editors...');
    $('.summernote').each(function() {
        const id = $(this).attr('id');
        console.log('Initializing Summernote on:', id);
        $(this).summernote(summernoteConfig);
    });
    
    // Counter for unique IDs
    let extraCounter = 0;
    
    // Add new custom field
    $('#addExtraBtn').on('click', function() {
        console.log('Adding new custom field...');
        
        const template = document.getElementById('extraFieldTemplate');
        const container = document.getElementById('extraFieldsContainer');
        const clone = template.content.cloneNode(true);
        
        // Create unique ID
        const uniqueId = 'extra_desc_' + Date.now() + '_' + extraCounter++;
        
        // Set ID to textarea
        const textarea = clone.querySelector('.extra-desc');
        textarea.id = uniqueId;
        
        // Append to container
        container.appendChild(clone);
        
        // Initialize Summernote on new field
        console.log('Initializing Summernote on new field:', uniqueId);
        $('#' + uniqueId).summernote(summernoteConfig);
        
        // Attach remove handler
        container.lastElementChild.querySelector('.removeExtra').addEventListener('click', function() {
            console.log('Removing field:', uniqueId);
            // Destroy Summernote instance
            $('#' + uniqueId).summernote('destroy');
            // Remove the field
            container.lastElementChild.remove();
        });
    });
    
    // Load existing custom fields (for edit mode)
    <?php if ($isEdit && !empty($faculty['extra_fields'])): ?>
    try {
        console.log('Loading existing custom fields...');
        const existingFields = <?php echo $faculty['extra_fields']; ?>;
        
        if (Array.isArray(existingFields) && existingFields.length > 0) {
            console.log('Found', existingFields.length, 'existing fields');
            
            existingFields.forEach(function(item, index) {
                console.log('Loading field', index, ':', item.title);
                
                const template = document.getElementById('extraFieldTemplate');
                const container = document.getElementById('extraFieldsContainer');
                const clone = template.content.cloneNode(true);
                
                // Create unique ID
                const uniqueId = 'extra_desc_' + Date.now() + '_' + extraCounter++;
                
                // Set values
                clone.querySelector('.extra-title').value = item.title || '';
                const textarea = clone.querySelector('.extra-desc');
                textarea.id = uniqueId;
                
                // Append to container
                container.appendChild(clone);
                
                // Initialize Summernote with content
                $('#' + uniqueId).summernote(summernoteConfig);
                $('#' + uniqueId).summernote('code', item.desc || '');
                
                // Attach remove handler
                container.lastElementChild.querySelector('.removeExtra').addEventListener('click', function() {
                    console.log('Removing existing field:', uniqueId);
                    $('#' + uniqueId).summernote('destroy');
                    container.lastElementChild.remove();
                });
            });
        }
    } catch (e) {
        console.error('Error loading extra fields:', e);
    }
    <?php endif; ?>
    
    console.log('Faculty form initialization complete!');
});
</script>

</body>
</html>