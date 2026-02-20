<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summernote Faculty Form Test</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- jQuery (Required for Summernote) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Summernote CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">
    
    <!-- Summernote JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
    
    <style>
        body {
            background: #f5f5f5;
            padding: 20px;
        }
        .test-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .extra-field {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        #status {
            position: sticky;
            top: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="alert alert-info" id="status">
        <strong><i class="fas fa-info-circle"></i> Summernote Faculty Form Test</strong><br>
        This page tests Summernote with the same structure as add_faculty.php.
        Open browser console (F12) to see initialization messages.
    </div>

    <div class="test-card">
        <h2 class="mb-4">
            <i class="fas fa-chalkboard-teacher me-2"></i>
            Faculty Form - Summernote Test
        </h2>

        <form id="testForm">
            
            <!-- Test Editor 1 -->
            <div class="section-title">
                <i class="fas fa-lightbulb"></i> Skills and Knowledge
            </div>
            <div class="mb-4">
                <textarea id="skills" name="skills" class="summernote">
                    <h3>Programming Languages</h3>
                    <ul>
                        <li>Python</li>
                        <li>JavaScript</li>
                        <li>PHP</li>
                    </ul>
                </textarea>
            </div>

            <hr class="my-4">

            <!-- Test Editor 2 -->
            <div class="section-title">
                <i class="fas fa-book"></i> Course Taught
            </div>
            <div class="mb-4">
                <textarea id="course_taught" name="course_taught" class="summernote">
                    <p>Data Structures and Algorithms</p>
                    <p>Web Development</p>
                    <p>Database Management Systems</p>
                </textarea>
            </div>

            <hr class="my-4">

            <!-- Test Editor 3 -->
            <div class="section-title">
                <i class="fas fa-flask"></i> Research Projects
            </div>
            <div class="mb-4">
                <textarea id="research" name="research" class="summernote">
                    <h4>Current Research</h4>
                    <p>Working on <strong>AI in Education</strong></p>
                </textarea>
            </div>

            <hr class="my-4">

            <!-- Dynamic Fields Test -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    <i class="fas fa-plus-circle"></i> Dynamic Custom Fields
                </h5>
                <button type="button" class="btn btn-primary" id="addFieldBtn">
                    <i class="fas fa-plus"></i> Add Field
                </button>
            </div>

            <div id="dynamicContainer"></div>

            <template id="fieldTemplate">
                <div class="extra-field">
                    <div class="d-flex justify-content-between mb-2">
                        <input type="text" class="form-control field-title" placeholder="Field Title" style="flex:1">
                        <button type="button" class="btn btn-danger btn-sm ms-2 removeField">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </div>
                    <textarea class="form-control summernote field-desc"></textarea>
                </div>
            </template>

            <hr class="my-4">

            <!-- Action Buttons -->
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success" onclick="checkEditors()">
                    <i class="fas fa-check-circle"></i> Check Editors
                </button>
                <button type="button" class="btn btn-info" onclick="getContent()">
                    <i class="fas fa-code"></i> Get Content
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Test Submit
                </button>
            </div>

        </form>
    </div>

    <!-- Output Area -->
    <div id="output"></div>
</div>

<script>
console.log('=== Summernote Faculty Form Test ===');

// Summernote configuration
const summernoteConfig = {
    height: 250,
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
    ]
};

// Check dependencies
console.log('jQuery version:', $.fn.jquery);
console.log('Summernote available:', typeof $.fn.summernote !== 'undefined');

// Initialize main editors
$(document).ready(function() {
    console.log('Initializing Summernote editors...');
    
    $('#skills').summernote(summernoteConfig);
    console.log('✓ Skills editor initialized');
    
    $('#course_taught').summernote(summernoteConfig);
    console.log('✓ Course Taught editor initialized');
    
    $('#research').summernote(summernoteConfig);
    console.log('✓ Research editor initialized');
    
    console.log('✓ All main editors initialized!');
    
    // Update status
    $('#status').removeClass('alert-info').addClass('alert-success');
    $('#status').html('<strong><i class="fas fa-check-circle"></i> Success!</strong><br>All Summernote editors initialized. You can now type and format content.');
});

// Dynamic fields
let fieldCounter = 0;

$('#addFieldBtn').on('click', function() {
    addDynamicField('', '');
});

function addDynamicField(title = '', desc = '') {
    const template = document.getElementById('fieldTemplate');
    const container = document.getElementById('dynamicContainer');
    const clone = template.content.cloneNode(true);
    
    // Generate unique ID
    const uniqueId = 'dynamic_field_' + Date.now() + '_' + fieldCounter++;
    
    // Set values
    const textarea = clone.querySelector('.field-desc');
    textarea.id = uniqueId;
    textarea.value = desc;
    
    clone.querySelector('.field-title').value = title;
    
    // Append to container
    container.appendChild(clone);
    
    // Get the newly added field
    const newField = container.lastElementChild;
    
    // Initialize Summernote
    $('#' + uniqueId).summernote(summernoteConfig);
    console.log('✓ Dynamic field added:', uniqueId);
    
    // Remove handler
    newField.querySelector('.removeField').addEventListener('click', function() {
        $('#' + uniqueId).summernote('destroy');
        newField.remove();
        console.log('✓ Field removed:', uniqueId);
    });
}

// Check editors function
function checkEditors() {
    const output = document.getElementById('output');
    
    let html = '<div class="test-card"><h5><i class="fas fa-clipboard-check"></i> Editor Status:</h5>';
    html += '<table class="table table-bordered mt-3">';
    html += '<thead><tr><th>Editor</th><th>Status</th><th>Content Length</th></tr></thead><tbody>';
    
    // Check main editors
    const editors = [
        {id: 'skills', name: 'Skills and Knowledge'},
        {id: 'course_taught', name: 'Course Taught'},
        {id: 'research', name: 'Research Projects'}
    ];
    
    editors.forEach(editor => {
        const content = $('#' + editor.id).summernote('code');
        const isActive = $('#' + editor.id).summernote('isEmpty') === false;
        const status = isActive ? '<span class="badge bg-success">✓ Active</span>' : '<span class="badge bg-warning">Empty</span>';
        html += `<tr><td>${editor.name}</td><td>${status}</td><td>${content.length} chars</td></tr>`;
    });
    
    // Check dynamic fields
    const dynamicFields = document.querySelectorAll('#dynamicContainer .summernote');
    dynamicFields.forEach((field, index) => {
        const content = $('#' + field.id).summernote('code');
        html += `<tr><td>Dynamic Field ${index + 1}</td><td><span class="badge bg-info">Active</span></td><td>${content.length} chars</td></tr>`;
    });
    
    html += '</tbody></table>';
    html += '<p><strong>Total Editors:</strong> ' + (editors.length + dynamicFields.length) + '</p>';
    html += '</div>';
    
    output.innerHTML = html;
    
    console.log('=== Editor Check Complete ===');
    console.log('Main editors:', editors.length);
    console.log('Dynamic fields:', dynamicFields.length);
}

// Get content function
function getContent() {
    const output = document.getElementById('output');
    
    let html = '<div class="test-card"><h5><i class="fas fa-file-code"></i> Editor Content:</h5>';
    
    const editors = ['skills', 'course_taught', 'research'];
    
    editors.forEach(editorId => {
        const content = $('#' + editorId).summernote('code');
        html += '<div class="mt-3"><strong>' + editorId + ':</strong>';
        html += '<pre class="bg-light p-3 mt-2" style="max-height:200px;overflow:auto">' + escapeHtml(content) + '</pre></div>';
        console.log(editorId + ' content:', content.substring(0, 100) + '...');
    });
    
    html += '</div>';
    output.innerHTML = html;
}

// Escape HTML for display
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Form submit handler
$('#testForm').on('submit', function(e) {
    e.preventDefault();
    
    console.log('=== Form Submit Test ===');
    
    const formData = {
        skills: $('#skills').summernote('code'),
        course_taught: $('#course_taught').summernote('code'),
        research: $('#research').summernote('code')
    };
    
    console.log('Form data:', formData);
    
    alert('Form submitted! Check console for data.\n\nSkills length: ' + formData.skills.length + ' chars');
});

// Add some test dynamic fields on page load
$(document).ready(function() {
    setTimeout(function() {
        console.log('Adding test dynamic fields...');
        addDynamicField('Publications', '<p>List your publications here...</p>');
        addDynamicField('Awards', '<ul><li>Best Teacher Award 2023</li></ul>');
    }, 1000);
});
</script>

<!-- Bootstrap JS (optional, for modals and other components) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>