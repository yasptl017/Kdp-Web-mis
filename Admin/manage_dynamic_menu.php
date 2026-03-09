<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

/* ================================
   UPLOAD DIRECTORY
================================ */
$uploadDir = "uploads/menu_photos/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* ================================
   ADD / UPDATE MENU
================================ */
if (isset($_POST['save_menu'])) {

    $id = intval($_POST['menu_id']);
    $menu = $conn->real_escape_string($_POST['menu']);
    $heading = $conn->real_escape_string($_POST['heading']);
    $sub_heading = $conn->real_escape_string($_POST['sub_heading']);
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);

    /* Event Tags */
    $eventTags = [];
    if (isset($_POST['event_tags']) && is_array($_POST['event_tags'])) {
        foreach ($_POST['event_tags'] as $tag) {
            $tag = trim($tag);
            if ($tag !== '') {
                $eventTags[] = $tag;
            }
        }
    }
    $eventTagsJSON = $conn->real_escape_string(json_encode($eventTags, JSON_UNESCAPED_SLASHES));

    /* Fetch old photos */
    $oldPhotos = [];
    if ($id > 0) {
        $q = $conn->query("SELECT photos FROM dynamic_menu WHERE id=$id");
        if ($q && $q->num_rows > 0) {
            $oldPhotos = json_decode($q->fetch_assoc()['photos'], true) ?? [];
        }
    }

    /* Keep checked photos — if editing, only keep explicitly checked ones */
    $keepPhotos = [];
    if ($id > 0) {
        // $_POST['keep_photos'] missing means all deselected → keep nothing
        $checked = isset($_POST['keep_photos']) ? (array)$_POST['keep_photos'] : [];
        foreach ($checked as $p) {
            if (in_array($p, $oldPhotos)) {
                $keepPhotos[] = $p;
            }
        }
        foreach ($oldPhotos as $old) {
            if (!in_array($old, $keepPhotos) && file_exists($old)) {
                unlink($old);
            }
        }
    } else {
        $keepPhotos = $oldPhotos; // new record, nothing to keep
    }

    $photos = $keepPhotos;

    /* Upload new photos */
    if (!empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['name'] as $i => $name) {
            if ($_FILES['photos']['error'][$i] === 0) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                    $newName = "menu_" . time() . "_" . rand(1000,9999) . "." . $ext;
                    $target = $uploadDir . $newName;
                    move_uploaded_file($_FILES['photos']['tmp_name'][$i], $target);
                    $photos[] = $target;
                }
            }
        }
    }

    $photosJSON = $conn->real_escape_string(json_encode($photos, JSON_UNESCAPED_SLASHES));

    if ($id === 0) {
        $conn->query("
            INSERT INTO dynamic_menu
            (menu, heading, sub_heading, title, description, photos, event_tags)
            VALUES
            ('$menu','$heading','$sub_heading','$title','$description','$photosJSON','$eventTagsJSON')
        ");
        $message = "Menu item added successfully!";
    } else {
        $conn->query("
            UPDATE dynamic_menu SET
            menu='$menu',
            heading='$heading',
            sub_heading='$sub_heading',
            title='$title',
            description='$description',
            photos='$photosJSON',
            event_tags='$eventTagsJSON'
            WHERE id=$id
        ");
        $message = "Menu item updated successfully!";
    }

    $messageType = "success";
}

/* ================================
   DELETE MENU
================================ */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $q = $conn->query("SELECT photos FROM dynamic_menu WHERE id=$id");
    if ($q && $q->num_rows > 0) {
        $arr = json_decode($q->fetch_assoc()['photos'], true) ?? [];
        foreach ($arr as $p) {
            if (file_exists($p)) unlink($p);
        }
    }
    $conn->query("DELETE FROM dynamic_menu WHERE id=$id");
    $message = "Menu item deleted!";
    $messageType = "success";
}

$menus = $conn->query("SELECT * FROM dynamic_menu ORDER BY id DESC");
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<style>
.photo-selector{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:15px;margin-top:10px}
.photo-item{position:relative;border:2px solid #ddd;border-radius:8px;padding:8px;background:#f8f9fa;transition:.3s}
.photo-item.selected{border-color:#28a745;background:#d4edda}
.photo-item img{width:100%;height:120px;object-fit:cover;border-radius:4px}
.photo-checkbox{position:absolute;top:15px;right:15px;width:22px;height:22px;cursor:pointer}
.photo-filename{font-size:11px;color:#666;margin-top:5px;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.select-all-controls{margin-bottom:10px;padding:10px;background:#e7f3ff;border-radius:5px;display:flex;gap:10px;align-items:center}
.tag-item{display:flex;gap:10px;margin-bottom:10px;align-items:center}
.tag-item input{flex:1}
.tag-item .btn-remove{width:40px}
</style>

<body>

<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

<h2 class="h4 mb-4">Manage Dynamic Menu</h2>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
<?php echo $message; ?>
<button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<button class="btn btn-primary mb-3"
data-bs-toggle="modal"
data-bs-target="#menuModal"
onclick="addMenu()">Add Menu</button>

<table id="menuTable" class="table table-bordered table-striped align-middle">
<thead>
<tr class="text-center">
<th>#</th>
<th>Menu</th>
<th>Heading</th>
<th>Title</th>
<th>Photos</th>
<th>Event Tags</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php $i=1; while($row=$menus->fetch_assoc()):
$photos = json_decode($row['photos'], true) ?? [];
$tags = json_decode($row['event_tags'] ?? '[]', true) ?? [];
?>
<tr>
<td class="text-center"><?php echo $i++; ?></td>
<td><?php echo htmlspecialchars($row['menu']); ?></td>
<td><?php echo htmlspecialchars($row['heading']); ?></td>
<td><?php echo htmlspecialchars($row['title']); ?></td>
<td class="text-center"><?php echo count($photos); ?></td>
<td><?php echo implode(', ', array_map('htmlspecialchars', $tags)); ?></td>
<td class="text-center">
<button class="btn btn-warning btn-sm"
onclick='editMenu(<?php echo json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
data-bs-toggle="modal"
data-bs-target="#menuModal"><i class="fas fa-edit"></i></button>

<a href="?delete=<?php echo $row['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this menu item?')">
<i class="fas fa-trash"></i>
</a>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</main>

<!-- MODAL -->
<div class="modal fade" id="menuModal">
<div class="modal-dialog modal-xl">
<div class="modal-content">

<form method="POST" enctype="multipart/form-data">

<div class="modal-header">
<h5 id="modalTitle">Add Menu</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<input type="hidden" name="menu_id" id="menu_id">

<label>Menu</label>
<input type="text" name="menu" id="menu" class="form-control mb-3" required>

<label>Heading</label>
<input type="text" name="heading" id="heading" class="form-control mb-3" required>

<label>Sub Heading</label>
<input type="text" name="sub_heading" id="sub_heading" class="form-control mb-3">

<label>Title</label>
<input type="text" name="title" id="title" class="form-control mb-3" required>

<label>Description</label>
<textarea name="description" id="description" class="form-control mb-3"></textarea>

<label>Event Tags</label>
<div id="eventTagsContainer"></div>
<button type="button" class="btn btn-success btn-sm mb-3" onclick="addTagField()">Add New Tag</button>

<label>Photos</label>
<input type="file" name="photos[]" multiple class="form-control mb-3">

<div id="existingPhotos"></div>

</div>

<div class="modal-footer">
<button type="submit" name="save_menu" class="btn btn-primary">Save</button>
<button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>

</form>

</div>
</div>
</div>

<?php include "footer.php"; ?>
<script>
$(document).ready(()=>$('#menuTable').DataTable());

$('#menuModal').on('shown.bs.modal',()=>{
if(!$('#description').next('.note-editor').length){
initSummernote('#description', {height: 250});
}
if(window.pendingDescription!==undefined){
$('#description').summernote('code',window.pendingDescription);
window.pendingDescription=undefined;
}
});

$('#menuModal').on('hidden.bs.modal',()=>{
if($('#description').next('.note-editor').length){
$('#description').summernote('destroy');
}
window.pendingDescription=undefined;
});

let tagCounter = 0;

function addTagField(value=''){
let container = document.getElementById('eventTagsContainer');
let div = document.createElement('div');
div.className = 'tag-item';
div.id = 'tagItem_'+tagCounter;
div.innerHTML = `
<input type="text" name="event_tags[]" class="form-control" value="${value}" placeholder="Enter event tag">
<button type="button" class="btn btn-danger btn-sm btn-remove" onclick="removeTagField(${tagCounter})"><i class="fas fa-times"></i></button>
`;
container.appendChild(div);
tagCounter++;
}

function removeTagField(id){
let el = document.getElementById('tagItem_'+id);
if(el) el.remove();
}

function addMenu(){
modalTitle.innerText="Add Menu";
menu_id.value=menu.value=heading.value=sub_heading.value=title.value="";
description.value="";
existingPhotos.innerHTML="";
eventTagsContainer.innerHTML="";
tagCounter = 0;
addTagField();
window.pendingDescription=undefined;
}

function editMenu(m){
modalTitle.innerText="Update Menu";
menu_id.value=m.id;
menu.value=m.menu;
heading.value=m.heading;
sub_heading.value=m.sub_heading;
title.value=m.title;
window.pendingDescription=m.description;

eventTagsContainer.innerHTML="";
tagCounter = 0;
let tags=[];
try{tags=JSON.parse(m.event_tags);}catch(e){}
if(tags.length){
tags.forEach(t=>addTagField(t));
}else{
addTagField();
}

let photos=[];
try{photos=JSON.parse(m.photos);}catch(e){}

if(photos.length){
let html='<div class="alert alert-info">';
html+='<div class="select-all-controls">';
html+='<button type="button" class="btn btn-sm btn-success" onclick="selectAllPhotos(true)">Select All</button>';
html+='<button type="button" class="btn btn-sm btn-warning" onclick="selectAllPhotos(false)">Deselect All</button>';
html+='<span class="ms-auto"><span id="selectedCount">'+photos.length+'</span> / '+photos.length+' selected</span>';
html+='</div><div class="photo-selector">';
photos.forEach((p,i)=>{
let f=p.split('/').pop();
html+='<div class="photo-item selected" id="photoItem_'+i+'">';
html+='<input type="checkbox" name="keep_photos[]" value="'+p+'" checked class="photo-checkbox" id="photo_'+i+'" onchange="togglePhotoSelection('+i+')">';
html+='<img src="'+p+'"><div class="photo-filename">'+f+'</div></div>';
});
html+='</div></div>';
existingPhotos.innerHTML=html;
}else existingPhotos.innerHTML='';
}

function togglePhotoSelection(i){
let cb=document.getElementById('photo_'+i);
let item=document.getElementById('photoItem_'+i);
cb.checked?item.classList.add('selected'):item.classList.remove('selected');
updateSelectedCount();
}

function selectAllPhotos(v){
document.querySelectorAll('.photo-checkbox').forEach((cb,i)=>{
cb.checked=v;
document.getElementById('photoItem_'+i).classList.toggle('selected',v);
});
updateSelectedCount();
}

function updateSelectedCount(){
let c=document.querySelectorAll('.photo-checkbox:checked').length;
let el=document.getElementById('selectedCount');
if(el)el.textContent=c;
}
</script>

</body>
</html>