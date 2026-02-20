<?php
include('dbconfig.php');

$session_faculty_name = $_SESSION['Name'] ?? null;

$faculty_result = $conn->query("SELECT id, Name FROM faculty WHERE status = 1");
$term_result    = $conn->query("SELECT DISTINCT term FROM students ORDER BY term DESC");
$sem_result     = $conn->query("SELECT sem FROM semester WHERE status = 1");
$subject_result = $conn->query("SELECT subjectName, sem FROM subjects WHERE status = 1");
$slot_result    = $conn->query("SELECT timeslot FROM timeslot WHERE status = 1");
$lab_result     = $conn->query("SELECT labNo FROM labs WHERE status = 1");

$subjects = [];
while ($row = $subject_result->fetch_assoc()) {
    $subjects[] = $row;
}

$labs = [];
while ($row = $lab_result->fetch_assoc()) {
    $labs[] = $row['labNo'];
}

$batches = [];
foreach (['A', 'B', 'C', 'D'] as $letter) {
    for ($i = 1; $i <= 4; $i++) {
        $batches[] = $letter . $i;
    }
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
                <h1 class="app-page-title mb-0"><i class="bi bi-camera-video me-2"></i>Lab Attendance</h1>
                <a href="addLabMapping.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-calendar-plus me-1"></i>Add / Manage Lab Mapping
                </a>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <h4>Search Students</h4>
                            <form method="POST" action="takelabatt.php">
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
                                        <label class="form-label">Batch</label>
                                        <button type="button" class="btn batch-select-btn w-100 text-start" data-bs-toggle="modal" data-bs-target="#batchSelectModal">
                                            <i class="bi bi-list-check me-1"></i>Select Batch(es)
                                        </button>
                                        <small class="text-muted d-block mt-1" id="batchSelectionHint">No batch selected.</small>
                                        <div id="selectedBatchBadges" class="mt-2 d-flex flex-wrap gap-1"></div>
                                        <div id="batchHiddenContainer"></div>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Lab Number Mapping</label>
                                        <div id="batchLabContainer" class="row g-2"></div>
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
    .batch-select-btn {
        color: #fff;
        border: 0;
        background: linear-gradient(135deg, #0d6efd 0%, #0aa2c0 100%);
        box-shadow: 0 6px 14px rgba(13, 110, 253, 0.25);
        transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
    }

    .batch-select-btn:hover,
    .batch-select-btn:focus {
        color: #fff;
        filter: brightness(1.05);
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(10, 162, 192, 0.35);
    }
    </style>

<div class="modal fade" id="batchSelectModal" tabindex="-1" aria-labelledby="batchSelectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchSelectModalLabel">Select Batches</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="batchCheckboxContainer" class="row g-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="clearBatchSelectionBtn">Clear</button>
                <button type="button" class="btn btn-primary" id="saveBatchSelectionBtn" data-bs-dismiss="modal">Done</button>
            </div>
        </div>
    </div>
</div>

<script>
    const allSubjects = <?= json_encode($subjects); ?>;
    const allLabs = <?= json_encode($labs); ?>;
    const availableBatches = <?= json_encode($batches); ?>;
    const semSelect = document.getElementById('semSelect');
    const subjectSelect = document.getElementById('subjectSelect');
    const formEl = document.querySelector('form[action="takelabatt.php"]');
    const batchSelectionHint = document.getElementById('batchSelectionHint');
    const selectedBatchBadges = document.getElementById('selectedBatchBadges');
    const batchHiddenContainer = document.getElementById('batchHiddenContainer');
    const batchLabContainer = document.getElementById('batchLabContainer');
    const batchCheckboxContainer = document.getElementById('batchCheckboxContainer');
    const saveBatchSelectionBtn = document.getElementById('saveBatchSelectionBtn');
    const clearBatchSelectionBtn = document.getElementById('clearBatchSelectionBtn');

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

    function getSelectedBatchesFromModal() {
        return Array.from(batchCheckboxContainer.querySelectorAll('input[name="batch_option[]"]:checked')).map(cb => cb.value);
    }

    function renderBatchHiddenInputs(selectedBatches) {
        batchHiddenContainer.innerHTML = '';
        selectedBatches.forEach(batch => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'batch[]';
            input.value = batch;
            batchHiddenContainer.appendChild(input);
        });
    }

    function renderSelectedBatchBadges(selectedBatches) {
        selectedBatchBadges.innerHTML = '';
        if (selectedBatches.length === 0) {
            batchSelectionHint.textContent = 'No batch selected.';
            return;
        }

        batchSelectionHint.textContent = selectedBatches.length + ' batch(es) selected.';
        selectedBatches.forEach(batch => {
            const badge = document.createElement('span');
            badge.className = 'badge bg-primary-subtle text-dark border';
            badge.textContent = batch;
            selectedBatchBadges.appendChild(badge);
        });
    }

    function renderBatchLabSelectors() {
        const selectedBatches = getSelectedBatchesFromModal();
        const selectedLabValues = {};
        batchLabContainer.querySelectorAll('select[name^="batch_lab_map["]').forEach(select => {
            const match = select.name.match(/^batch_lab_map\[(.+)\]$/);
            if (match) {
                selectedLabValues[match[1]] = select.value;
            }
        });

        renderBatchHiddenInputs(selectedBatches);
        renderSelectedBatchBadges(selectedBatches);
        batchLabContainer.innerHTML = '';

        selectedBatches.forEach(batch => {
            const col = document.createElement('div');
            col.className = 'col-12';

            const label = document.createElement('label');
            label.className = 'form-label';
            label.textContent = 'Lab Number for ' + batch;

            const select = document.createElement('select');
            select.name = 'batch_lab_map[' + batch + ']';
            select.className = 'form-control';
            select.required = true;

            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Select Lab';
            select.appendChild(defaultOption);

            allLabs.forEach(labNo => {
                const option = document.createElement('option');
                option.value = labNo;
                option.textContent = labNo;
                if (selectedLabValues[batch] === labNo) {
                    option.selected = true;
                }
                select.appendChild(option);
            });

            col.appendChild(label);
            col.appendChild(select);
            batchLabContainer.appendChild(col);
        });
    }

    availableBatches.forEach(batch => {
        const col = document.createElement('div');
        col.className = 'col-6';
        col.innerHTML = `
            <label class="form-check border rounded p-2 d-flex align-items-center gap-2">
                <input class="form-check-input mt-0" type="checkbox" name="batch_option[]" value="${batch}">
                <span class="form-check-label">${batch}</span>
            </label>
        `;
        batchCheckboxContainer.appendChild(col);
    });

    saveBatchSelectionBtn.addEventListener('click', renderBatchLabSelectors);
    clearBatchSelectionBtn.addEventListener('click', function () {
        batchCheckboxContainer.querySelectorAll('input[name="batch_option[]"]').forEach(cb => {
            cb.checked = false;
        });
        renderBatchLabSelectors();
    });

    formEl.addEventListener('submit', function (event) {
        const selectedBatches = getSelectedBatchesFromModal();
        if (selectedBatches.length === 0) {
            event.preventDefault();
            alert('Please select at least one batch.');
            return;
        }

        const allLabSelected = Array.from(batchLabContainer.querySelectorAll('select[name^="batch_lab_map["]'))
            .every(select => select.value !== '');
        if (!allLabSelected) {
            event.preventDefault();
            alert('Please select a lab number for every selected batch.');
        }
    });

    renderBatchLabSelectors();
</script>

<?php include('footer.php'); ?>
</body>
</html>
<?php $conn->close(); ?>
