<?php
include('dbconfig.php');

$session_faculty_name = $_SESSION['Name'] ?? null;

$faculty_result = $conn->query("SELECT id, Name FROM faculty WHERE status = 1");
$term_result    = $conn->query("SELECT DISTINCT term FROM students ORDER BY term DESC");
$sem_result     = $conn->query("SELECT sem FROM semester WHERE status = 1");
$subject_result = $conn->query("SELECT subjectName, sem FROM subjects WHERE status = 1");
$slot_result    = $conn->query("SELECT timeslot FROM timeslot WHERE status = 1");
$tut_batch_result = $conn->query("SELECT DISTINCT tutBatch FROM students WHERE tutBatch IS NOT NULL AND TRIM(tutBatch) <> '' ORDER BY tutBatch");

$subjects = [];
while ($row = $subject_result->fetch_assoc()) {
    $subjects[] = $row;
}

$tut_batches = [];
while ($row = $tut_batch_result->fetch_assoc()) {
    $tut_batches[] = trim((string)$row['tutBatch']);
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
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h1 class="app-page-title mb-0"><i class="bi bi-book me-2"></i>Tutorial Attendance</h1>
                <a href="addTutMapping.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-calendar-plus me-1"></i>Add / Manage Tutorial Mapping
                </a>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <h4>Search Students</h4>
                            <form method="POST" action="taketutatt.php">
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Faculty Name</label>
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
                                        <label class="form-label">Term</label>
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
                                        <label class="form-label">Semester</label>
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
                                        <label class="form-label">Subject</label>
                                        <select name="subject" class="form-control" id="subjectSelect" required>
                                            <option value="">Select Semester first</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Tutorial Batch</label>
                                        <button type="button" class="btn tut-batch-btn w-100 text-start" data-bs-toggle="modal" data-bs-target="#tutBatchModal">
                                            <i class="bi bi-list-check me-1"></i>Select Tutorial Batch(es)
                                        </button>
                                        <small class="text-muted d-block mt-1" id="tutBatchHint">No tutorial batch selected.</small>
                                        <div id="selectedTutBatchBadges" class="mt-2 d-flex flex-wrap gap-1"></div>
                                        <div id="tutBatchHiddenContainer"></div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Date</label>
                                        <input type="date" name="date" class="form-control" value="<?= $default_date; ?>" required>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Slot</label>
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

<style>
    .tut-batch-btn {
        color: #fff;
        border: 0;
        background: linear-gradient(135deg, #198754 0%, #20c997 100%);
        box-shadow: 0 6px 14px rgba(25, 135, 84, 0.25);
        transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
    }

    .tut-batch-btn:hover,
    .tut-batch-btn:focus {
        color: #fff;
        filter: brightness(1.05);
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(32, 201, 151, 0.35);
    }
</style>

<div class="modal fade" id="tutBatchModal" tabindex="-1" aria-labelledby="tutBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tutBatchModalLabel">Select Tutorial Batches</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="tutBatchCheckboxContainer" class="row g-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="clearTutBatchBtn">Clear</button>
                <button type="button" class="btn btn-primary" id="saveTutBatchBtn" data-bs-dismiss="modal">Done</button>
            </div>
        </div>
    </div>
</div>

<script>
    const allSubjects = <?= json_encode($subjects); ?>;
    const availableTutBatches = <?= json_encode($tut_batches); ?>;
    const semSelect = document.getElementById('semSelect');
    const subjectSelect = document.getElementById('subjectSelect');
    const formEl = document.querySelector('form[action="taketutatt.php"]');
    const tutBatchHint = document.getElementById('tutBatchHint');
    const selectedTutBatchBadges = document.getElementById('selectedTutBatchBadges');
    const tutBatchHiddenContainer = document.getElementById('tutBatchHiddenContainer');
    const tutBatchCheckboxContainer = document.getElementById('tutBatchCheckboxContainer');
    const saveTutBatchBtn = document.getElementById('saveTutBatchBtn');
    const clearTutBatchBtn = document.getElementById('clearTutBatchBtn');

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

    function getSelectedTutBatches() {
        return Array.from(tutBatchCheckboxContainer.querySelectorAll('input[name="tut_batch_option[]"]:checked')).map(cb => cb.value);
    }

    function renderTutBatchHiddenInputs(selectedBatches) {
        tutBatchHiddenContainer.innerHTML = '';
        selectedBatches.forEach(batch => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'tutBatch[]';
            input.value = batch;
            tutBatchHiddenContainer.appendChild(input);
        });
    }

    function renderTutBatchBadges(selectedBatches) {
        selectedTutBatchBadges.innerHTML = '';
        if (selectedBatches.length === 0) {
            tutBatchHint.textContent = 'No tutorial batch selected.';
            return;
        }

        tutBatchHint.textContent = selectedBatches.length + ' tutorial batch(es) selected.';
        selectedBatches.forEach(batch => {
            const badge = document.createElement('span');
            badge.className = 'badge bg-primary-subtle text-dark border';
            badge.textContent = batch;
            selectedTutBatchBadges.appendChild(badge);
        });
    }

    function syncSelectedTutBatches() {
        const selectedBatches = getSelectedTutBatches();
        renderTutBatchHiddenInputs(selectedBatches);
        renderTutBatchBadges(selectedBatches);
    }

    availableTutBatches.forEach(batch => {
        const col = document.createElement('div');
        col.className = 'col-6';
        col.innerHTML = `
            <label class="form-check border rounded p-2 d-flex align-items-center gap-2">
                <input class="form-check-input mt-0" type="checkbox" name="tut_batch_option[]" value="${batch}">
                <span class="form-check-label">${batch}</span>
            </label>
        `;
        tutBatchCheckboxContainer.appendChild(col);
    });

    saveTutBatchBtn.addEventListener('click', syncSelectedTutBatches);
    clearTutBatchBtn.addEventListener('click', function () {
        tutBatchCheckboxContainer.querySelectorAll('input[name="tut_batch_option[]"]').forEach(cb => {
            cb.checked = false;
        });
        syncSelectedTutBatches();
    });

    formEl.addEventListener('submit', function (event) {
        if (getSelectedTutBatches().length === 0) {
            event.preventDefault();
            alert('Please select at least one tutorial batch.');
        }
    });

    syncSelectedTutBatches();
</script>

<?php include('footer.php'); ?>
</body>
</html>
<?php $conn->close(); ?>
