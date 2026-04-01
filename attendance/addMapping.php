<?php
$mapping_pages = [
    'lecture' => 'addLectureMapping.php',
    'lab' => 'addLabMapping.php',
    'tutorial' => 'addTutMapping.php',
];

$active_tab = (string)($_GET['tab'] ?? 'lecture');
if (!array_key_exists($active_tab, $mapping_pages)) {
    $active_tab = 'lecture';
}

$redirect_params = [];
$edit_id = isset($_GET['edit_id']) ? (int)($_GET['edit_id'] ?? 0) : 0;
if ($edit_id > 0) {
    $redirect_params['edit_id'] = $edit_id;
}
if ((string)($_GET['embedded'] ?? '') === '1') {
    $redirect_params['embedded'] = '1';
}

$redirect_url = $mapping_pages[$active_tab];
if ($redirect_params !== []) {
    $redirect_url .= '?' . http_build_query($redirect_params);
}

header('Location: ' . $redirect_url);
exit;
