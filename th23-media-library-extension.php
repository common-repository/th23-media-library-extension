<?php
/*
Plugin Name: th23 Media Library Extension
Plugin URI: http://th23.net/th23-media-library-extension
Description: Adds advanced filter options to the Media Library, attachment links to edit posts/ pages overview.
Version: 2.0.0
Author: Thorsten Hartmann (th23)
Author URI: http://th23.net
Text Domain: th23-media-library-extension
Domain Path: /lang

Copyright 2014-2016, Thorsten Hartmann (th23)
http://th23.net/
*/

class th23_media_library_extension {

	// Initialize class-wide variables
	public $plugin = array(); // plugin (setup) information
	public $options = array(); // plugin options (user defined, changable)
	public $data = array(); // data exchange between plugin functions

	function __construct() {

		// Setup basics
		$this->plugin['file'] = __FILE__;
		$this->plugin['basename'] = plugin_basename($this->plugin['file']);
		$this->plugin['dir_url'] = plugin_dir_url($this->plugin['file']);
		$this->plugin['version'] = '2.0.0'; // for dev: $this->plugin['version'] = time();

		// Localization
		load_plugin_textdomain('th23-media-library-extension', false, dirname($this->plugin['basename']) . '/lang');

	}

	// Ensure PHP <5 compatibility
	function th23_media_library_extension() {
		self::__construct();
	}

}

// === INITIALIZATION ===

$th23_media_library_extension_path = plugin_dir_path(__FILE__);

// Load additional PRO class, if it exists
if(file_exists($th23_media_library_extension_path . 'th23-media-library-extension-pro.php')) {
	require($th23_media_library_extension_path . 'th23-media-library-extension-pro.php');
}
// Mimic PRO class, if it does not exist
if(!class_exists('th23_media_library_extension_pro')) {
	class th23_media_library_extension_pro extends th23_media_library_extension {
		function __construct() {
			parent::__construct();
		}
		// Ensure PHP <5 compatibility
		function th23_media_library_extension_pro() {
			self::__construct();
		}
	}
}

// Load additional admin class, if required...
if(is_admin() && file_exists($th23_media_library_extension_path . 'th23-media-library-extension-admin.php')) {
	require($th23_media_library_extension_path . 'th23-media-library-extension-admin.php');
	$th23_media_library_extension = new th23_media_library_extension_admin();
}
// ...or initiate plugin via (mimiced) PRO class
else {
	$th23_media_library_extension = new th23_media_library_extension_pro();
}

?>