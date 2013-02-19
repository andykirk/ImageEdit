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
	public $has_image;
	public $lib_uri;
	public $max_size;

	public function __construct(array $config = array())
	{
		$this->cache_dir = 'cache';
		$this->max_size  = 200 * 1024;

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

	public function getFormValues()
	{
		$return = array(
			'cache_id'    => $this->cache_id,
			'current_img' => $this->current_img
		);

		return $return;
	}

	#public function hasImage($fieldname)
	protected function hasImage($fieldname)
	{
		/*if (!is_null($this->has_image))	 {
			return $this->has_image;
		}*/
		if (isset($_FILES[$fieldname]) && !$_FILES[$fieldname]['error'] && $_FILES[$fieldname]['size'] < $this->max_size) {
			if (is_uploaded_file($_FILES[$fieldname]['tmp_name'])) {
				$this->has_image = true;
				return true;
			}
		}
		$this->has_image = false;
		return false;
	}

	public function start($fieldname)
	{
		$has_image = $this->hasImage($fieldname);
		if ($has_image) {
			// Name and extension:
			$name = $_FILES[$fieldname]['name'];
			$ext  = substr($name, strrpos($name, '.') + 1);

			// New unique filename
			$cache_id          = md5(time().rand());
			$cache_name        = 'cache/' . $cache_id . '_1.' . $ext;

			$this->cache_id    = $cache_id;
			$this->cache_name  = $cache_name;
			$this->current_img = $this->lib_uri . $cache_name;

			// move uploaded file into cache folder
			move_uploaded_file($_FILES[$fieldname]['tmp_name'], $cache_name);

			// change file permission to 644
			@chmod($cachename, 0644);
		}
	}
}