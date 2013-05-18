<?php
/**
 * PhotostreamImage Class
 *
 * @author Marcus Wong (wongm)
 * @package plugins
 */

// force UTF-8 Ø

class PhotostreamImage extends _Image {

	function PhotostreamImage(&$album, $filename) {
		
		global $_zp_current_album, $_zp_current_photostream, $_zp_gallery;
				
		// This is where the magic happens...
		// load Image data from the magic array from the earlier Photostream DB query
		$this->data = $_zp_current_photostream->data[$filename];
		$_zp_current_album = new PhotostreamAlbum($_zp_gallery, $this->data['album-data']);
		
		// $album is an Album object; it should already be created.
		if (!is_object($_zp_current_album)) return NULL;
		if (!$this->classSetup($_zp_current_album, $filename)) { // spoof attempt
			$this->exists = false;
			return;
		}
		// Check if the file exists.
		if (!file_exists($this->localpath) || is_dir($this->localpath)) {
			$this->exists = false;
			return;
		}
		
		// Get list of all columns in the dataset...
		// Then map from database record to the current item
		foreach (array_keys($this->data) as $field) {
			$this->set($field, $this->data[$field]);
		}
	}
	
	// overloaded functions inherited from _Image
	// don't want them to do anything
	function save() {}
}
?>