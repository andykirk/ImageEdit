<?php
$fieldname = 'image';
/*----*/
$step = 1;
#echo "<pre>\n"; var_dump($_SERVER); echo "</pre>\n"; exit;
require 'ImageEdit.php';
$imageEdit = new ImageEdit();
$imageEdit->start($fieldname);
if ($imageEdit->has_image) {
	$step = 2;
	$vals = $imageEdit->getFormValues();
	#echo "<pre>\n"; var_dump($vals); echo "</pre>\n"; exit;
}
/*
if (! $_FILES['image_file']['error'] && $_FILES['image_file']['size'] < $max_size) {
	if (is_uploaded_file($_FILES['image_file']['tmp_name'])) {

		// new unique filename
		$sTempFileName = 'cache/' . md5(time().rand());

		// move uploaded file into cache folder
		move_uploaded_file($_FILES['image_file']['tmp_name'], $sTempFileName);

		// change file permission to 644
		@chmod($sTempFileName, 0644);

		if (file_exists($sTempFileName) && filesize($sTempFileName) > 0) {
			$aSize = getimagesize($sTempFileName); // try to obtain image info
			if (!$aSize) {
				@unlink($sTempFileName);
				return;
			}

			// check for image type
			switch($aSize[2]) {
				case IMAGETYPE_JPEG:
					$sExt = '.jpg';

					// create a new image from file
					$vImg = @imagecreatefromjpeg($sTempFileName);
					break;
				case IMAGETYPE_PNG:
					$sExt = '.png';

					// create a new image from file
					$vImg = @imagecreatefrompng($sTempFileName);
					break;
				default:
					@unlink($sTempFileName);
					return;
			}

			// create a new true color image
			$vDstImg = @imagecreatetruecolor( $iWidth, $iHeight );

			// copy and resize part of an image with resampling
			imagecopyresampled($vDstImg, $vImg, 0, 0, (int)$_POST['x1'], (int)$_POST['y1'], $iWidth, $iHeight, (int)$_POST['w'], (int)$_POST['h']);

			// define a result image filename
			$sResultFileName = $sTempFileName . $sExt;

			// output image to file
			imagejpeg($vDstImg, $sResultFileName, $iJpgQuality);
			@unlink($sTempFileName);

			return $sResultFileName;
		}
	}
}*/

?>
<!DOCTYPE html>
<head>
<meta charset="utf-8">
<title>Image Editor</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="css/bootstrap-jasny.min.css">
<link rel="stylesheet" href="css/jquery.Jcrop.min.css">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container-fluid">
	<div class="row-fluid">
		<?php if ($step == 1): ?>
		<h1>Step 1: Choose image</h1>
		<form id="upload_form" enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<div class="fileupload fileupload-new" data-provides="fileupload">
				<div class="fileupload-new thumbnail" style="width: 200px; height: 150px;"><img src="http://www.placehold.it/200x150/EFEFEF/AAAAAA&text=no+image" /></div>
				<div class="fileupload-preview fileupload-exists thumbnail" style="max-width: 200px; max-height: 150px; line-height: 20px;"></div>
				<div>
					<span class="btn btn-file"><span class="fileupload-new">Select image</span><span class="fileupload-exists">Change</span><input type="file" name="<?php echo $fieldname; ?>" /></span>
					<a href="#" class="btn fileupload-exists" data-dismiss="fileupload">Remove</a>
					<button type="submit" class="btn">Next</button>
				</div>
			</div>
		</form>
		<?php else: ?>
		<h1>Step 2: Edit Image</h1>
		<form id="edit_form" enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<input type="hidden" value="<?php echo $vals['cache_id']; ?>" />
			<input id="x" type="hidden" name="x">
			<input id="y" type="hidden" name="y">
			<input id="w" type="hidden" name="w">
			<input id="h" type="hidden" name="h">
			<input id="action" type="hidden" name="action">
			<div id="toolbar" class="control-group">
			<button type="submit" class="btn btn-primary" data-action="undo"><i class="icon-share-alt icon-white icon-flipped"></i> Undo</button>
			<button type="submit" class="btn btn-primary" data-action="redo"><i class="icon-share-alt icon-white"></i> Redo</button>
			<button type="submit" class="btn btn-primary" data-action="anticlockwise"><i class="icon-repeat icon-white icon-flipped"></i> Rotate Anticlockwise</button>
			<button type="submit" class="btn btn-primary" data-action="clockwise"><i class="icon-repeat icon-white"></i> Rotate Clockwise</button>
			<button type="submit" class="btn btn-primary" data-action="crop"><i class="icon-retweet icon-white"></i> Crop</button>
			</div>
			<div><img src="<?php echo $vals['current_img']; ?>" id="editor" /></div>
		</form>
		<?php endif; ?>
	</div>
</div>
<div class="container-fluid">
	<div class="row-fluid">
	<?php
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