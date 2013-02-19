<?php
/**
 * ImageEdit
 *
 * Image manipulation using input from js editor.
 *
 * @author akirk
 * @copyright Copyright (c) 2013
 * @version 0.1
 * @access public
 */
class ImageEdit {
	public $cache_dir;
	public $cache_id;
	public $cache_name;
	public $current_img;
	public $errors;
	public $ext;
	public $fieldname;
	public $has_image;
	public $lib_uri;
	public $max_size;
	public $max_version;
	public $types;
	public $version;

	public function __construct(array $config = array())
	{
		$this->cache_dir = 'cache';
		$this->errors    = array();
		$this->fieldname = 'image';
		$this->max_size  = 200 * 1024;
		$this->types     = array(
	        'gif' => 'image/gif',
	        'png' => 'image/png',
	        'jpg' => 'image/jpeg'
        );
		$this->version     = 1;
		$this->max_version = 1;

		$lib_dir = explode('/', $_SERVER['PHP_SELF']);
		array_pop($lib_dir);
		$this->lib_uri = implode('/', $lib_dir) . '/';

		foreach ($config as $key=>$val) {
			if (property_exists($this, $key)) {
				$this->$key = $val;
			}
		}

		// Check for writable cache dir:
		if (!is_writable(($this->cache_dir))) {
			trigger_error('Cache dir ' . $this->cache_dir . ' is not writable', E_USER_ERROR);
		}
	}


	protected function buildCacheName($version)
	{
		return 'cache/' . $this->cache_id . '_' . $version . '.' . $this->ext;
		#$this->cache_name  = $cache_name;
		#$this->current_img = $this->lib_uri . $cache_name;
	}

	protected function buildImagePath($version)
	{
		return $this->lib_uri . $this->buildCacheName($version);
		#$this->cache_name  = $cache_name;
		#$this->current_img = $this->lib_uri . $cache_name;
	}

	#public function hasImage($fieldname)
	protected function hasImage()
	{
		$this->has_img = false;
		$fieldname     = $this->fieldname;
		// Check for posted values:
		#echo "<pre>\n";var_dump($_POST);echo "</pre>\n";exit;
		if (isset($_POST['cache_id'])
		 && isset($_POST['ext'])
		 && isset($_POST['version'])
		 && isset($_POST['max_version'])) {
			$this->cache_id    = $_POST['cache_id'];
			$this->ext         = $_POST['ext'];
			$this->version     = $_POST['version'];
			$this->max_version = $_POST['max_version'];
			$cache_name        = $this->buildCacheName($this->version);
			if (file_exists($cache_name)) {
				$this->has_img     = true;
				$this->current_img = $this->buildImagePath($this->version);
				$this->cache_name  = $cache_name;
				return true;
			}
		}
		// Check for uploaded values:
		if (!isset($_FILES[$fieldname]))
		{
			return false;
		}
		if (!$_FILES[$fieldname]['error']
		  && $_FILES[$fieldname]['size'] < $this->max_size
		  && in_array($_FILES[$fieldname]['type'], $this->types)) {
			if (is_uploaded_file($_FILES[$fieldname]['tmp_name'])) {
				$this->has_img     = true;
				$name              = $_FILES[$fieldname]['name'];
				$this->cache_id    = md5(time().rand());
				$this->ext         = substr($name, strrpos($name, '.') + 1);
				$this->current_img = $this->buildImagePath($this->version);

				move_uploaded_file($_FILES[$fieldname]['tmp_name'], $this->buildCacheName($this->version));
				return true;
			}
		}
		$this->errors[] = 'Image upload failed. It may exceed the maximum filesize of '
			. $this->getFormatedSize($this->max_size) . ' or be of an unsupported type (types supported are '
			. implode(', ', array_keys($this->types)). ').';
		return false;
	}

	protected function imageCreateFromType($type, $filename)
	{
		if ($type == 'jpg') {
			$type = 'jpeg';
		}
		$f = 'imagecreatefrom' . $type;
		return $f($filename);
	}

	protected function imageWrite($img, $type, $filename, $quality = 100)
	{
		if ($type == 'jpg') {
			$type = 'jpeg';
		}
		$f = 'image' . $type;
		return $f($img, $filename, $quality);
	}

	protected function rotate($degrees = 0)
	{
		$version  = $this->version;
		$new_file = $this->buildCacheName(++$version);
		$source   = $this->imageCreateFromType($this->ext, $this->cache_name);
		$rotate   = imagerotate($source, $degrees, 0);
		$ok       = true;
		if ($this->imageWrite($rotate, $this->ext, $new_file)) {
			$this->version     = $version;
			$this->max_version = $version;
			$this->current_img = $this->buildImagePath($version);
		} else {
			$ok             = false;
			$this->errors[] = 'Image rotate failed.';
		}
		imagedestroy($rotate);
		return $ok;
	}

	public function run()
	{
		$has_img = $this->hasImage();
		#echo "<pre>\n";var_dump($has_img);echo "</pre>\n";exit;
		if ($has_img && isset($_POST['action'])) {
			$action = 'action' . ucfirst($_POST['action']);
			if (method_exists($this, $action)) {
				$this->$action();
			}
		}
	}

	public function getValues()
	{
		$return = array(
			'cache_id'    => $this->cache_id,
			'current_img' => $this->current_img,
			'errors'      => $this->errors,
			'ext'         => $this->ext,
			'has_img'     => $this->has_img,
			'max_version' => $this->max_version,
			'version'     => $this->version
		);

		return $return;
	}

	public function actionUndo()
	{
		if ($this->version > 1) {
			$this->version--;
			$this->current_img = $this->buildImagePath($this->version);
		}
	}

	public function actionRedo()
	{

		if ($this->version < $this->max_version) {
			$this->version++;
			$this->current_img = $this->buildImagePath($this->version);
		}
	}

	public function actionAnticlockwise()
	{
		$this->rotate(90);
	}

	public function actionClockwise()
	{
		$this->rotate(-90);
	}

	public function actionCrop()
	{
		$version  = $this->version;
		$new_file = $this->buildCacheName(++$version);
		$canvas   = imagecreatetruecolor($_POST['w'], $_POST['h']);
		$piece    = $this->imageCreateFromType($this->ext, $this->cache_name);

		imagecopyresampled(
			$canvas, $piece,
			0, 0, $_POST['x'], $_POST['y'],
			$_POST['w'], $_POST['h'], $_POST['w'], $_POST['h']
		);

		$ok       = true;
		if ($this->imageWrite($canvas, $this->ext, $new_file)) {
			$this->version     = $version;
			$this->max_version = $version;
			$this->current_img = $this->buildImagePath($version);
		} else {
			$ok             = false;
			$this->errors[] = 'Image crop failed.';
		}
		imagedestroy($canvas);
		imagedestroy($piece);
		return $ok;

		/*
		   // The fixed image size (canvas):
		   if (is_array($this->fixed_image)) {
		   $fixed_w = $this->fixed_image[0];
		   $fixed_h = $this->fixed_image[1];
		   $scale_factor = $fixed_w / $this->new_w;
		   $this->src_w = $this->orig_w;
		   $this->src_h = $this->orig_h;
		   $this->dst_w = round($this->src_w * $scale_factor);
		   $this->dst_h = round($this->src_h * $scale_factor);
		   $canvas_w = $fixed_w;
		   $canvas_h = $fixed_h;
		   } else {
		   $this->src_w = $this->new_w;
		   $this->src_h = $this->new_h;
		   $this->dst_w = $this->new_w;
		   $this->dst_h = $this->new_h;
		   $canvas_w = $this->new_w;
		   $canvas_h = $this->new_h;
		   }

		   $canvas = imagecreatetruecolor($canvas_w, $canvas_h);
		   // $piece          = imagecreatefromjpeg($this->image_path);
		   $piece = $this->_imageCreateFromType($this->image_path);
		   $new_image_name = $this->_createImageName($this->session->Get('image_current') + 1);
		   // bool imagecopyresampled(resource $dst_image, resource $src_image, int $dst_x, int $dst_y, int $src_x, int $src_y, int $dst_w, int $dst_h, int $src_w, int $src_h)
		   imagecopyresampled($canvas, $piece, 0, 0, $this->src_x, $this->src_y, $this->dst_w, $this->dst_h, $this->src_w, $this->src_h);
		   //if (imagejpeg($canvas, $this->image_cache_dir . $new_image_name, 100)) {
		   if ($this->_writeImage($canvas, $this->image_cache_dir . $new_image_name, 100)) {
		   $this->result .= '<p>Image crop successful!</p>' . "\n";
		   #$src = $this->image_cache_dir . $new_image_name;
		   $src = $this->image_cache_url . $new_image_name;
		   $this->return_data['src'] = $src;
		   #$dimensions = getimagesize($src);
		   #$this->result .= '<img src="' . $src . '" alt="Test" ' . $dimensions[3] . '" />';
		   } else {
		   $this->result .= '<p>Image crop failed.</p>' . "\n";
		   return false;
		   }
		   imagedestroy($canvas);
		   imagedestroy($piece);
		   return true;
		   */
	}

	public function actionGreyscale()
	{
		/*// $source = imagecreatefromjpeg($image_cache.$new_image_name);
		$source = $this->_imageCreateFromType($image_cache . $new_image_name);
		//$source = imagerotate($source, 270, 0);
		imagefilter($source, IMG_FILTER_grayscale);
		$new_image_name = $this->_createImageName($this->session->Get('image_current') + 1);
		// if(imagejpeg($source, $this->image_cache_dir.$new_image_name, 100))
		if ($this->_imageType($source, $this->image_cache_dir . $new_image_name, 100)) {
			$this->result .= '<p>Image grayscale successful!</p>' . "\n";
			$src = $this->image_cache_url . $new_image_name;
			$dimensions = getimagesize($src);
			$this->result .= '<img src="' . $src . '" alt="Test" ' . $dimensions[3] . '" />';
		} else {
			$this->result .= '<p>Image grayscale failed.</p>' . "\n";
		}
		imagedestroy($source);*/
	}

	public function actionSave()
	{
	}

	public function getFormatedSize($bytes) {
		if(!empty($bytes)) {
			//SET TEXT TITLES TO SHOW AT EACH LEVEL
			$s = array('bytes', 'kb', 'MB', 'GB', 'TB', 'PB');
			$e = floor(log($bytes)/log(1024));
			//CREATE COMPLETED OUTPUT
			$output = sprintf('%.' . ($e == 0 ? 0 : 2) . 'f ' . $s[$e], ($bytes/pow(1024, $e)));
			return $output;
		}
	}
}