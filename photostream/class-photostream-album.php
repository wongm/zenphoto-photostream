<?php
/**
 * PhotostreamAlbum Class
 *
 * @author Marcus Wong (wongm)
 * @package plugins
 */

// force UTF-8 Ø

class PhotostreamAlbum extends Album {

	/**
	 * Constructor for PhotostreamAlbum
	 *
	 * @param object &$gallery The parent gallery
	 * @param string $albumData Array data for this Album, from the earlier Photostream DB query
	 * @return Album
	 */
	function PhotostreamAlbum(&$gallery, $albumData) {
		// load Album values from Photostram DB query
		$this->data = $albumData;
		$folder8 = $albumData['folder'];
		
		if (!is_object($gallery) || strtolower(get_class($gallery)) != 'gallery') {
			debugLogBacktrace('Bad gallery in instantiation of album '.$folder8);
			$gallery = $gallery;
		}
		
		$folder8 = sanitize_path($folder8);
		$folderFS = internalToFilesystem($folder8);
		$this->gallery = &$gallery;
		if (empty($folder8)) {
			$localpath = getAlbumFolder();
		} else {
			$localpath = getAlbumFolder() . $folderFS . "/";
		}
		
		$this->name = $folder8;
		$this->localpath = $localpath;
	}
	
	// mocked out function to do nothing
	function getImages($page=0, $firstPageCount=0, $sorttype=null, $sortdirection=null, $care=true) {
		return null;
	}
	
	// overloaded functions inherited from Album
	// don't want them to do anything
	function save() {}
	function loadFileNames() {}
	function getAlbums() {}
}
?>