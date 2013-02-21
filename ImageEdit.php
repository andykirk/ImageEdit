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
	public $saved;
	public $savepath;
	public $savename;
	public $types;
	public $version;

	public function __construct(array $config = array())
	{
		$this->cache_dir    = 'cache';
		$this->errors       = array();
		$this->fieldname    = 'image';
		$this->max_size     = 200 * 1024;
		$this->max_version  = 1;
		$this->types        = array(
	        'gif' => 'image/gif',
	        'png' => 'image/png',
	        'jpg' => 'image/jpeg'
        );
		$this->saved        = false;
		$this->savepath     = false;
		$this->savename     = false;
		$this->version      = 1;


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
	}

	protected function buildImagePath($version)
	{
		return $this->lib_uri . $this->buildCacheName($version);
	}

	/*protected function clearCache()
	{
		foreach (scandir('cache') as $file) {
			echo "<pre>\n";var_dump($file);echo "</pre>\n";
			if (strpos($file, $this->cache_id) !== false) {
				unlink('cache' . DIRECTORY_SEPARATOR . $file);
			}
		}
	}*/

	protected function hasImage()
	{
		$this->has_img = false;
		$fieldname     = $this->fieldname;
		// Check for posted values:
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

	protected function imageWrite($new_img, $type, $dest_file, $quality = 100)
	{
		switch ($type) {
			case 'jpg':
				$r = imagejpeg($new_img, $dest_file, $quality);
				break;
			case 'png':
				$r = imagepng($new_img, $dest_file);
				break;
			case 'gif':
				$r = imagegif($new_img, $dest_file);
				break;
		} // switch
		chmod($dest_file, 0777);
		return $r;
	}

	protected function filter($mode = '')
	{
		$version  = $this->version;
		$new_file = $this->buildCacheName(++$version);
		$source   = $this->imageCreateFromType($this->ext, $this->cache_name);
		$ok       = true;

		switch ($mode) {
			case 'greyscale':
				imagefilter($source, IMG_FILTER_GRAYSCALE);
				break;
			case 'sepia':
				imagefilter($source, IMG_FILTER_GRAYSCALE);
				imagefilter($source, IMG_FILTER_COLORIZE, 90, 60, 40);
				break;
			default:
				break;
		} // switch


		if ($this->imageWrite($source, $this->ext, $new_file)) {
			$this->version     = $version;
			$this->max_version = $version;
			$this->current_img = $this->buildImagePath($version);
		} else {
			$ok             = false;
			$this->errors[] = 'Image greyscale filter failed.';
		}
		imagedestroy($source);
		return $ok;
	}

	protected function rotate($degrees = 0)
	{
		$version  = $this->version;
		$new_file = $this->buildCacheName(++$version);
		$source   = $this->imageCreateFromType($this->ext, $this->cache_name);
		$ok       = true;
		$rotate   = imagerotate($source, $degrees, 0);

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
				return $this->$action();
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
			'saved'       => $this->saved,
			'version'     => $this->version
		);

		return $return;
	}

	public function actionUndo()
	{
		if ($this->version > 1) {
			$this->version--;
			$this->current_img = $this->buildImagePath($this->version);
			return true;
		}
		return false;
	}

	public function actionRedo()
	{

		if ($this->version < $this->max_version) {
			$this->version++;
			$this->current_img = $this->buildImagePath($this->version);
			return true;
		}
		return false;
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
		$ok       = true;

		imagecopyresampled(
			$canvas, $piece,
			0, 0, $_POST['x'], $_POST['y'],
			$_POST['w'], $_POST['h'], $_POST['w'], $_POST['h']
		);

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
	}

	public function actionGreyscale()
	{
		return $this->filter('greyscale');
	}

	public function actionSepia()
	{
		return $this->filter('sepia');
	}

	public function actionSave()
	{
		if (!$this->savepath) {
			// Clearing the cache means that the final image can't be displayed,
			// so not sure what to do about this yet.
			#$this->clearCache();
			$this->saved = true;
			return true;
		}
		if (!$this->savepath) {
			$this->errors[] = 'No save path was set in config.';
			return false;
		}
		$savename = $this->savename
			      ? $this->savename
				  : $this->cache_id;
		$savepath = trim(rtrim($this->savepath, '/')) . '/';
		if (!file_exists($savepath) || !is_dir($savepath)) {
			$this->errors[] = "Folder $savepath does not exist.";
			return false;
		}
		if (!is_writable($savepath)) {
			$this->errors[] = "Folder $savepath is not writable. Folder permissions: " . substr(sprintf('%o', fileperms($savepath)), -4) . '.';
			return false;
		}
		$new_filename = $savepath . $savename . '.' . $this->ext;

		if (!copy($this->cache_name, $new_filename)) {
			$this->errors[] = "Failed to copy $new_filename from $this->cache_name.";
			return false;
		}
		$this->saved = true;
		return true;
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