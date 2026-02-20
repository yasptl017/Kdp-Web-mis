<!DOCTYPE html>
<html lang="en">
<?php 
include 'dptname.php';
include_once "../Admin/dbconfig.php";

$faculty_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$faculty_query = "SELECT * FROM faculty WHERE id = $faculty_id AND department = '$DEPARTMENT_NAME'";
$faculty_result = $conn->query($faculty_query);
$faculty = $faculty_result->fetch_assoc();

if (!$faculty) {
    header("Location: faculty.php");
    exit();
}

$page_title = $faculty['name'] . " - " . $DEPARTMENT_NAME;

function hasContent($content) {
    if (empty($content)) return false;
    $text = strip_tags($content);
    $text = html_entity_decode($text);
    $text = trim($text);
    $upper = strtoupper($text);
    if ($upper == 'NIL' || $upper == 'N/A' || $upper == 'NA' || $upper == '-' || strlen($text) < 4) {
        return false;
    }
    if (preg_match('/^[\s\n\r\t<>\/&nbsp;]+$/', $content)) {
        return false;
    }
    return true;
}

function parseEducation($content) {
    $lines = array_filter(array_map('trim', explode("\n", $content)));
    $result = [];
    foreach ($lines as $line) {
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) >= 3) {
            $result[] = [
                'duration' => $parts[0],
                'qualification' => $parts[1],
                'university' => $parts[2]
            ];
        }
    }
    return $result;
}

function parseWorkExperience($content) {
    $lines = array_filter(array_map('trim', explode("\n", $content)));
    $result = [];
    foreach ($lines as $line) {
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) >= 3) {
            $result[] = [
                'duration' => $parts[0],
                'institute' => $parts[1],
                'designation' => $parts[2]
            ];
        }
    }
    return $result;
}

function parseExpertLectures($content) {
    $lines = array_filter(array_map('trim', explode("\n", $content)));
    $result = [];
    foreach ($lines as $line) {
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) >= 4) {
            $result[] = [
                'date' => $parts[0],
                'location' => $parts[1],
                'topic' => $parts[2],
                'remark' => $parts[3]
            ];
        }
    }
    return $result;
}

$tabs = [];
$firstTab = null;

if (hasContent($faculty['education'])) {
    $tabs[] = ['id' => 'education', 'title' => 'Education', 'icon' => 'fa-graduation-cap', 'type' => 'education'];
    if (!$firstTab) $firstTab = 'education';
}
if (hasContent($faculty['work_experience'])) {
    $tabs[] = ['id' => 'work', 'title' => 'Work Experience', 'icon' => 'fa-briefcase', 'type' => 'work'];
    if (!$firstTab) $firstTab = 'work';
}
if (hasContent($faculty['skills'])) {
    $tabs[] = ['id' => 'skills', 'title' => 'Skills & Expertise', 'icon' => 'fa-tools', 'type' => 'html'];
    if (!$firstTab) $firstTab = 'skills';
}
if (hasContent($faculty['course_taught'])) {
    $tabs[] = ['id' => 'courses', 'title' => 'Courses Taught', 'icon' => 'fa-chalkboard', 'type' => 'html'];
    if (!$firstTab) $firstTab = 'courses';
}
if (hasContent($faculty['training'])) {
    $tabs[] = ['id' => 'training', 'title' => 'Training & Workshops', 'icon' => 'fa-certificate', 'type' => 'html'];
    if (!$firstTab) $firstTab = 'training';
}
if (hasContent($faculty['research_projects'])) {
    $tabs[] = ['id' => 'research', 'title' => 'Research Projects', 'icon' => 'fa-flask', 'type' => 'html'];
    if (!$firstTab) $firstTab = 'research';
}
if (hasContent($faculty['publications'])) {
    $tabs[] = ['id' => 'publications', 'title' => 'Publications', 'icon' => 'fa-file-alt', 'type' => 'html'];
    if (!$firstTab) $firstTab = 'publications';
}
if (hasContent($faculty['academic_projects'])) {
    $tabs[] = ['id' => 'projects', 'title' => 'Academic Projects', 'icon' => 'fa-project-diagram', 'type' => 'html'];
    if (!$firstTab) $firstTab = 'projects';
}
if (hasContent($faculty['patents'])) {
    $tabs[] = ['id' => 'patents', 'title' => 'Patents', 'icon' => 'fa-lightbulb', 'type' => 'html'];
    if (!$firstTab) $firstTab = 'patents';
}
if (hasContent($faculty['memberships'])) {
    $tabs[] = ['id' => 'memberships', 'title' => 'Professional Memberships', 'icon' => 'fa-users', 'type' => 'html'];
    if (!$firstTab) $firstTab = 'memberships';
}
if (hasContent($faculty['awards'])) {
    $tabs[] = ['id' => 'awards', 'title' => 'Awards & Honors', 'icon' => 'fa-trophy', 'type' => 'html'];
    if (!$firstTab) $firstTab = 'awards';
}
if (hasContent($faculty['expert_lectures'])) {
    $tabs[] = ['id' => 'lectures', 'title' => 'Expert Lectures', 'icon' => 'fa-microphone', 'type' => 'lectures'];
    if (!$firstTab) $firstTab = 'lectures';
}
if (hasContent($faculty['portfolio'])) {
    $tabs[] = ['id' => 'portfolio', 'title' => 'Portfolio', 'icon' => 'fa-folder-open', 'type' => 'html'];
    if (!$firstTab) $firstTab = 'portfolio';
}

$extra_fields = !empty($faculty['extra_fields']) ? json_decode($faculty['extra_fields'], true) : [];
if (!empty($extra_fields) && is_array($extra_fields)) {
    foreach ($extra_fields as $index => $field) {
        if (hasContent($field['desc'])) {
            $tabs[] = ['id' => 'extra'.$index, 'title' => $field['title'], 'icon' => 'fa-plus-circle', 'type' => 'html'];
            if (!$firstTab) $firstTab = 'extra'.$index;
        }
    }
}
?>
<?php include '../assets/preload/head.php'; ?>
<body>
    <?php include '../assets/preload/topbar.php'; ?>
    <?php include '../assets/preload/header.php'; ?>
    <?php include '../assets/preload/navigation.php'; ?>
    <?php include '../assets/preload/mobilenav.php'; ?>

    <section class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="page-title">Faculty Profile</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Departments</a></li>
                            <li class="breadcrumb-item"><a href="aboutdpt.php"><?php echo $DEPARTMENT_NAME; ?></a></li>
                            <li class="breadcrumb-item"><a href="faculty.php">Faculty</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($faculty['name']); ?></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <?php include 'dptnavigation.php'; ?>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="faculty-profile-header">
                <div class="profile-simple-card">
                    <div class="row">
                        <div class="col-lg-3 text-center mb-4 mb-lg-0">
                            <div class="profile-photo-wrapper">
                                <?php if (!empty($faculty['photo'])): ?>
                                    <img src="../Admin/<?php echo $faculty['photo']; ?>" alt="<?php echo htmlspecialchars($faculty['name']); ?>" class="profile-photo-img">
                                <?php else: ?>
                                    <div class="profile-photo-placeholder">
                                        <?php echo strtoupper(substr($faculty['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-lg-9">
                            <h2 class="faculty-name"><?php echo htmlspecialchars($faculty['name']); ?></h2>
                            <p class="faculty-designation"><i class="fas fa-user-tie me-2"></i><?php echo htmlspecialchars($faculty['designation']); ?></p>
                            
                            <div class="faculty-info-grid">
                                <?php if (!empty($faculty['email'])): ?>
                                    <div class="info-item">
                                        <span class="info-label"><i class="fas fa-envelope me-1"></i>Email</span>
                                        <a href="mailto:<?php echo htmlspecialchars($faculty['email']); ?>" class="info-value">
                                            <?php echo htmlspecialchars($faculty['email']); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($faculty['phone'])): ?>
                                    <div class="info-item">
                                        <span class="info-label"><i class="fas fa-phone me-1"></i>Phone</span>
                                        <a href="tel:<?php echo htmlspecialchars($faculty['phone']); ?>" class="info-value">
                                            <?php echo htmlspecialchars($faculty['phone']); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($faculty['date_of_joining'])): ?>
                                    <div class="info-item">
                                        <span class="info-label"><i class="fas fa-calendar-check me-1"></i>Joined</span>
                                        <span class="info-value">
                                            <?php echo date("d F Y", strtotime($faculty['date_of_joining'])); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($faculty['experience_years'])): ?>
                                    <div class="info-item">
                                        <span class="info-label"><i class="fas fa-briefcase me-1"></i>Experience</span>
                                        <span class="info-value">
                                            <?php echo htmlspecialchars($faculty['experience_years']); ?> years
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (count($tabs) > 0): ?>
            <div class="faculty-tabs-wrapper">
                <ul class="nav nav-tabs faculty-nav-tabs" id="facultyTabs" role="tablist">
                    <?php foreach ($tabs as $index => $tab): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo ($tab['id'] == $firstTab) ? 'active' : ''; ?>" 
                                    id="<?php echo $tab['id']; ?>-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#<?php echo $tab['id']; ?>" 
                                    type="button">
                                <i class="fas <?php echo $tab['icon']; ?> me-2"></i><?php echo $tab['title']; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="tab-content faculty-tab-content" id="facultyTabsContent">
                    <?php foreach ($tabs as $index => $tab): ?>
                        <div class="tab-pane fade <?php echo ($tab['id'] == $firstTab) ? 'show active' : ''; ?>" 
                             id="<?php echo $tab['id']; ?>" 
                             role="tabpanel">
                            <div class="tab-content-wrapper">
                                <?php if ($tab['type'] == 'education'): ?>
                                    <?php $education = parseEducation($faculty['education']); ?>
                                    <div class="timeline-wrapper">
                                        <?php foreach ($education as $idx => $edu): ?>
                                            <div class="timeline-item">
                                                <div class="timeline-marker"></div>
                                                <div class="timeline-content">
                                                    <div class="timeline-badge"><?php echo htmlspecialchars($edu['duration']); ?></div>
                                                    <h4><?php echo htmlspecialchars($edu['qualification']); ?></h4>
                                                    <p class="institution-name">
                                                        <i class="fas fa-university me-2"></i><?php echo htmlspecialchars($edu['university']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                
                                <?php elseif ($tab['type'] == 'work'): ?>
                                    <?php $work = parseWorkExperience($faculty['work_experience']); ?>
                                    <div class="timeline-wrapper">
                                        <?php foreach ($work as $idx => $exp): ?>
                                            <div class="timeline-item">
                                                <div class="timeline-marker"></div>
                                                <div class="timeline-content">
                                                    <div class="timeline-badge"><?php echo htmlspecialchars($exp['duration']); ?></div>
                                                    <h4><?php echo htmlspecialchars($exp['designation']); ?></h4>
                                                    <p class="institution-name">
                                                        <i class="fas fa-building me-2"></i><?php echo htmlspecialchars($exp['institute']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                
                                <?php elseif ($tab['type'] == 'lectures'): ?>
                                    <?php $lectures = parseExpertLectures($faculty['expert_lectures']); ?>
                                    <div class="lectures-grid">
                                        <?php foreach ($lectures as $idx => $lecture): ?>
                                            <div class="lecture-card">
                                                <div class="lecture-header">
                                                    <div class="lecture-date">
                                                        <i class="fas fa-calendar-day"></i>
                                                        <span><?php echo htmlspecialchars($lecture['date']); ?></span>
                                                    </div>
                                                    <div class="lecture-location">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <span><?php echo htmlspecialchars($lecture['location']); ?></span>
                                                    </div>
                                                </div>
                                                <h5 class="lecture-topic"><?php echo htmlspecialchars($lecture['topic']); ?></h5>
                                                <?php if (!empty($lecture['remark'])): ?>
                                                    <p class="lecture-remark">
                                                        <i class="fas fa-info-circle me-2"></i><?php echo htmlspecialchars($lecture['remark']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                
                                <?php else: ?>
                                    <div class="content-display">
                                        <?php 
                                        $field_name = str_replace(['extra'], [''], $tab['id']);
                                        if (strpos($tab['id'], 'extra') === 0) {
                                            $extra_index = intval(str_replace('extra', '', $tab['id']));
                                            echo $extra_fields[$extra_index]['desc'];
                                        } else {
                                            $content_map = [
                                                'skills' => 'skills',
                                                'courses' => 'course_taught',
                                                'training' => 'training',
                                                'research' => 'research_projects',
                                                'publications' => 'publications',
                                                'projects' => 'academic_projects',
                                                'patents' => 'patents',
                                                'memberships' => 'memberships',
                                                'awards' => 'awards',
                                                'portfolio' => 'portfolio'
                                            ];
                                            if (isset($content_map[$tab['id']])) {
                                                echo $faculty[$content_map[$tab['id']]];
                                            }
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-info text-center mt-4">
                    <i class="fas fa-info-circle me-2"></i>Additional information will be available soon.
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="faculty.php" class="btn btn-secondary btn-lg px-5">
                    <i class="fas fa-arrow-left me-2"></i>Back to Faculty List
                </a>
            </div>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>