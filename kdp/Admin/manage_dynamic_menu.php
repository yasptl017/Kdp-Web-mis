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
    $description = $_POST['description'];

    /* Fetch old photos */
    $oldPhotos = [];
    if ($id > 0) {
        $q = $conn->query("SELECT photos FROM dynamic_menu WHERE id=$id");
        if ($q && $q->num_rows > 0) {
            $oldPhotos = json_decode($q->fetch_assoc()['photos'], true) ?? [];
        }
    }

    /* Keep checked photos */
    $keepPhotos = [];
    if ($id > 0 && isset($_POST['keep_photos'])) {
        foreach ($_POST['keep_photos'] as $p) {
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
        $keepPhotos = $oldPhotos;
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
            (menu, heading, sub_heading, title, description, photos)
            VALUES
            ('$menu','$heading','$sub_heading','$title','$description','$photosJSON')
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
            photos='$photosJSON'
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css">

<style>
.photo-selector{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:15px;margin-top:10px}
.photo-item{position:relative;border:2px solid #ddd;border-radius:8px;padding:8px;background:#f8f9fa;transition:.3s}
.photo-item.selected{border-color:#28a745;background:#d4edda}
.photo-item img{width:100%;height:120px;object-fit:cover;border-radius:4px}
.photo-checkbox{position:absolute;top:15px;right:15px;width:22px;height:22px;cursor:pointer}
.photo-filename{font-size:11px;color:#666;margin-top:5px;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.select-all-controls{margin-bottom:10px;padding:10px;background:#e7f3ff;border-radius:5px;display:flex;gap:10px;align-items:center}
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
<th>Action</th>
</tr>
</thead>
<tbody>
<?php $i=1; while($row=$menus->fetch_assoc()):
$photos = json_decode($row['photos'], true) ?? [];
?>
<tr>
<td class="text-center"><?php echo $i++; ?></td>
<td><?php echo htmlspecialchars($row['menu']); ?></td>
<td><?php echo htmlspecialchars($row['heading']); ?></td>
<td><?php echo htmlspecialchars($row['title']); ?></td>
<td class="text-center"><?php echo count($photos); ?></td>
<td class="text-center">
<button class="btn btn-warning btn-sm"
onclick='editMenu(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(()=>$('#menuTable').DataTable());

$('#menuModal').on('shown.bs.modal',()=>{
if(!$('#description').next('.note-editor').length){
$('#description').summernote({height:250});
}
if(window.pendingDescription!==undefined){
setTimeout(()=>{
$('#description').summernote('code',window.pendingDescription);
window.pendingDescription=undefined;
},100);
}
});

function addMenu(){
modalTitle.innerText="Add Menu";
menu_id.value=menu.value=heading.value=sub_heading.value=title.value="";
description.value="";
existingPhotos.innerHTML="";
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
