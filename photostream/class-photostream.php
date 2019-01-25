<?php
/**
 * Photostream Class
 *
 * @author Marcus Wong (wongm)
 * @package plugins
 */

// force UTF-8 Ø

class Photostream extends Album {
	
	var $sqlWhere;
	var $sqlOrderBy;
	var $sqlGroupBy;
	var $sqlJoin;
	
	/**
	 * Constructor for Photostream
	 *
	 * @param object &$gallery The parent gallery
	 * @return Photostream
	 */
	function __construct(&$gallery, $sqlWhere="", $sqlGroupBy="", $sqlOrderBy="", $sqlJoin="") {
		if (!is_object($gallery) || strtolower(get_class($gallery)) != 'gallery') {
			debugLogBacktrace('Bad gallery in instantiation of Photostream');
			$gallery = $gallery;
		}	
		$page = 1;
		$indexIntoFoundImages = 0;
		if (isset($_GET['page'])) {
			if (is_numeric($_GET['page'])) {
				if ($_GET['page'] >= 1) {
					$page = $_GET['page'];
				}
			}
		}
		
		$perPage = max(1, getOption('photostream_images_per_page'));
		$start = (($page * $perPage) - $perPage);
		$end = $start + $perPage;
		$selectCount = "0 AS album_count";
		
		// Add custom search / group by / ordering parameters
		// Also ensure security for unpublished images, and images in unpublished albums
		if (strlen($sqlWhere) > 0) {
			if (!zp_loggedin()) {
				$this->sqlWhere = " WHERE ($sqlWhere) AND i.`show` = 1 AND a.`show` = 1 ";
			} else {
				$this->sqlWhere = " WHERE ($sqlWhere) ";
			}
		} else if (!zp_loggedin()) {
			$this->sqlWhere = " WHERE i.`show` = 1 AND a.`show` = 1 ";
		}
		if (strlen($sqlGroupBy) > 0) {
			$this->sqlGroupBy = " GROUP BY $sqlGroupBy ";
			$selectCount = "count(i.albumid) AS album_count";
		}
		if (strlen($sqlOrderBy) > 0) {
			$this->sqlOrderBy = " ORDER BY $sqlOrderBy ";
		} else {
			// default for order by
			if (getOption('photostream_sort') == 'imageid') {
				$this->sqlOrderBy = " ORDER BY i.id DESC ";
			} else {
				$this->sqlOrderBy = " ORDER BY i.date DESC ";
			}			
		}
		if (strlen($sqlJoin) > 0) {
			$this->sqlJoin = " $sqlJoin ";
		}
		
		$sql = "SELECT i.*, a.folder AS folder, a.title AS album_title, a.show AS album_show, a.dynamic AS album_dynamic, $selectCount
			FROM " . prefix('images') . " i
			INNER JOIN " . prefix('albums') . " a ON i.albumid = a.id 
			$this->sqlJoin 
			$this->sqlWhere $this->sqlGroupBy $this->sqlOrderBy LIMIT $start, $perPage";
		$results = query_full_array($sql);
		
		// get total number of images in gallery
		$sql = "SELECT count(i.albumid) AS count FROM " . prefix('images') . "  i 
			INNER JOIN " . prefix('albums') . " a ON i.albumid = a.id 
			$this->sqlJoin 
			$this->sqlWhere $this->sqlGroupBy";
		$totalCounts = query_full_array($sql);
		
		// empty result set
		if (sizeof($totalCounts) == 0) {
			$totalCount = 0;
		// we have a group by in the SQL query...
		} else if (sizeof($totalCounts) > 1) {
			$totalCount = sizeof($totalCounts);
		// normal query
		} else if (is_numeric($totalCounts[0]['count'])) {
			$totalCount = $totalCounts[0]['count'];
		// some versions of Zenphoto need this?
		} else if (is_numeric($totalCounts['count'])) {
			$totalCount = $totalCounts['count'];
		}
		
		// keep track of total count as number, 
		// because size of images array is just # images on this page
		$this->totalCount = $totalCount;
		
		// how many images on this page?
		// when on last page, might be less than $perPage value
		if (sizeof($results) < $perPage) {
			$countPage = sizeof($results);
		} else {
			$countPage = $perPage;
		}
		
		// bake out found image data
		for ($i = 0; $i < $countPage; $i++) {
			
			// save composite filename into the list of all images
			$photostreamImageKey = $results[$i]['folder'] . "|||" . $results[$i]['filename'];
			$this->images[$i] = $photostreamImageKey;
			
			// save the DB results for use later on
			$this->data[$photostreamImageKey] = $results[$i];
			$this->data[$photostreamImageKey]['album-data']['folder'] = $results[$i]['folder'];
			$this->data[$photostreamImageKey]['album-data']['title'] = $results[$i]['album_title'];
			$this->data[$photostreamImageKey]['album-data']['show'] = $results[$i]['album_show'];
			$this->data[$photostreamImageKey]['album-data']['dynamic'] = $results[$i]['album_dynamic'];
			$this->data[$photostreamImageKey]['album-data']['count'] = $results[$i]['album_count'];
		}
	}
	
	/**
	 * Returns a of a slice of the images for this album. No sorting is carried out
	 *
	 * @param string $page  Which page of images should be returned. If zero, all images are returned.
	 * @param int $firstPageCount count of images that go on the album/image transition page
	 * @param string $sorttype optional sort type
	 * @param string $sortdirection optional sort direction
	 * @parem bool $care set to false if the order of the images does not matter
	 *
	 * @return array
	 */
	function getImages($page=0, $firstPageCount=0, $sorttype=null, $sortdirection=null, $care=true, $mine=NULL) {
		return $this->images;
	}
	
	/**
	 * Returns the number of images in this photostream
	 *
	 * @return int
	 */
	function getNumImages() {
		return $this->totalCount;
	}
	
	// overloaded functions inherited from Album
	// don't want them to do anything
	function save() {}
	function loadFileNames($dirs=false) {}
	function getAlbums($page=0, $sorttype=null, $sortdirection=null, $care=true, $mine=NULL) {}
}
?>