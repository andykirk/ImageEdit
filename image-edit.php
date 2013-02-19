<?php
$fieldname = 'image';
$config = array(
);
/*----*/
// DON'T use this as-is - it's not safe:
$savepath = isset($_GET['s']) ? $_GET['s'] : '';
$step     = 1;
require 'ImageEdit.php';
$imageEdit = new ImageEdit($config);
$imageEdit->run();
$vals = $imageEdit->getValues();
if ($vals['has_img']) {
	$step = 2;
}
?>
<!DOCTYPE html>
<head>
<meta charset="utf-8">
<title>Image Editor</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="css/bootstrap-jasny.min.css">
<link rel="stylesheet" href="css/jquery.Jcrop.min.css">
<link rel="stylesheet" href="css/style.min.css">
</head>
<body>
<div class="container-fluid">
	<div class="row-fluid">
		<?php if ($step == 1): ?>
		<h1>Step 1: Choose image</h1>
		<?php if(!empty($vals['errors'])): ?>
		<?php foreach($vals['errors'] as $error): ?>
		<p class="alert alert-error"><?php echo $error; ?></p>
		<?php endforeach; ?>
		<?php endif; ?>
		<form id="upload_form" enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<div class="fileupload fileupload-new" data-provides="fileupload">
				<div class="fileupload-new thumbnail" style="width: 200px; height: 150px;"><img src="http://www.placehold.it/200x150/EFEFEF/AAAAAA&text=no+image" /></div>
				<div class="fileupload-preview fileupload-exists thumbnail" style="max-width: 200px; max-height: 150px; line-height: 20px;"></div>
				<div>
					<span class="btn btn-file"><span class="fileupload-new">Select image</span><span class="fileupload-exists">Change</span><input type="file" name="<?php echo $fieldname; ?>" id="fileinput" /></span>
					<a href="#" class="btn fileupload-exists" data-dismiss="fileupload" id="remove">Remove</a>
					<button type="submit" class="btn" id="btn-next">Next</button>
				</div>
			</div>
		</form>
		<?php else: ?>
		<h1>Step 2: Edit Image</h1>
		<form id="edit_form" enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<input type="hidden" id="cache_id" name="cache_id" value="<?php echo $vals['cache_id']; ?>" />
			<input type="hidden" id="ext" name="ext" value="<?php echo $vals['ext']; ?>" />
			<input type="hidden" id="max_version" name="max_version" value="<?php echo $vals['max_version']; ?>" />
			<input type="hidden" id="savepath" name="savepath" value="<?php echo $savepath; ?>" />
			<input type="hidden" id="version" name="version" value="<?php echo $vals['version']; ?>" />
			<input type="hidden" id="x" name="x">
			<input type="hidden" id="y" name="y">
			<input type="hidden" id="w" name="w">
			<input type="hidden" id="h" name="h">
			<input type="hidden" id="action" name="action">
			<div id="toolbar" class="control-group">
				<button type="submit" class="btn btn-primary" id="btn-undo" data-action="undo"><i class="icon-undo"></i> Undo</button>
				<button type="submit" class="btn btn-primary" id="btn-redo" data-action="redo"><i class="icon-redo"></i> Redo</button>
				<button type="submit" class="btn btn-primary" id="btn-anticlockwise" data-action="anticlockwise"><i class="icon-anticlockwise"></i> Rotate Anticlockwise</button>
				<button type="submit" class="btn btn-primary" id="btn-clockwise" data-action="clockwise"><i class="icon-clockwise"></i> Rotate Clockwise</button>
				<button type="submit" class="btn btn-primary" id="btn-crop" data-action="crop"><i class="icon-crop"></i> Crop</button>
				<button type="submit" class="btn btn-success" id="btn-save" data-action="save"><i class="icon-save"></i> Save</button>
			</div>
			<div><img src="<?php echo $vals['current_img']; ?>" id="editor" /></div>
		</form>
		<?php endif; ?>
	</div>
</div>
<div class="container-fluid">
	<div class="row-fluid">
	<?php
	echo "<pre>\n";var_dump($vals);echo "</pre>\n";
	if (isset($_POST)) {
		echo "<pre>\n"; var_dump($_POST); echo "</pre>\n";
		if (isset($_FILES)) {
			echo "<pre>\n"; var_dump($_FILES); echo "</pre>\n";
		}
	}
	?>
	</div>
</div>
<script src="js/jquery.min.js"></script>
<script src="js/jquery.Jcrop.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>