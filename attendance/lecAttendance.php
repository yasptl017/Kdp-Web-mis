<?php
include('dbconfig.php');

$session_faculty_name = $_SESSION['Name'] ?? null;

$faculty_result = $conn->query("SELECT id, Name FROM faculty WHERE status = 1");
$term_result    = $conn->query("SELECT DISTINCT term FROM students ORDER BY term DESC");
$sem_result     = $conn->query("SELECT sem FROM semester WHERE status = 1");
$subject_result = $conn->query("SELECT subjectName, sem FROM subjects WHERE status = 1");
$slot_result    = $conn->query("SELECT timeslot FROM timeslot WHERE status = 1");

$subjects = [];
while ($row = $subject_result->fetch_assoc()) {
    $subjects[] = $row;
}

$default_date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<?php include('head.php'); ?>
<body class="app">
<?php include('header.php'); ?>

<div class="app-wrapper">
    <div class="app-content pt-3 p-md-3 p-lg-4">
        <div class="container-xl">
            <h1 class="app-page-title"><i class="bi bi-journal-text me-2"></i>Lecture Attendance</h1>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <h4>Search Students</h4>
                            <form method="POST" action="takelecatt.php">
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="faculty">Faculty Name</label>
                                        <select name="faculty" class="form-control" required>
                                            <option value="">Select Faculty</option>
                                            <?php while ($faculty = $faculty_result->fetch_assoc()) { ?>
                                                <option value="<?= $faculty['id']; ?>"
                                                    <?= ($faculty['Name'] === $session_faculty_name) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($faculty['Name']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="term">Term</label>
                                        <select name="term" class="form-control" required>
                                            <option value="">Select Term</option>
                                            <?php
                                            $term_selected = true;
                                            while ($term = $term_result->fetch_assoc()) {
                                                $selected = $term_selected ? 'selected' : '';
                                                $term_selected = false;
                                                echo "<option value=\"{$term['term']}\" $selected>{$term['term']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="semSelect">Semester</label>
                                        <select name="sem" class="form-control" id="semSelect" required>
                                            <option value="">Select Semester</option>
                                            <?php while ($sem = $sem_result->fetch_assoc()) { ?>
                                                <option value="<?= $sem['sem']; ?>"><?= $sem['sem']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="subjectSelect">Subject</label>
                                        <select name="subject" class="form-control" id="subjectSelect" required>
                                            <option value="">Select Semester first</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="class">Class</label>
                                        <select name="class" class="form-control" required>
                                            <option value="">Select Class</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="date">Date</label>
                                        <input type="date" name="date" class="form-control" value="<?= $default_date; ?>" required>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="slot">Slot</label>
                                        <select name="slot" class="form-control" required>
                                            <option value="">Select Slot</option>
                                            <?php while ($slot = $slot_result->fetch_assoc()) { ?>
                                                <option value="<?= $slot['timeslot']; ?>"><?= $slot['timeslot']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <button type="submit" class="btn btn-primary w-100">
                                            Proceed to Attendance
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const allSubjects = <?= json_encode($subjects); ?>;
    const semSelect = document.getElementById('semSelect');
    const subjectSelect = document.getElementById('subjectSelect');

    semSelect.addEventListener('change', function () {
        const selectedSem = this.value;
        subjectSelect.innerHTML = '<option value="">Select Subject</option>';
        const filtered = allSubjects.filter(sub => sub.sem === selectedSem);
        filtered.forEach(sub => {
            const option = document.createElement('option');
            option.value = sub.subjectName;
            option.textContent = sub.subjectName;
            subjectSelect.appendChild(option);
        });
    });
</script>

<?php include('footer.php'); ?>
</body>
</html>
<?php $conn->close(); ?>
