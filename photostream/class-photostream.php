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
	
	/**
	 * Constructor for Photostream
	 *
	 * @param object &$gallery The parent gallery
	 * @return Album
	 */
	function Photostream(&$gallery, $sqlWhere="", $sqlGroupBy="", $sqlOrderBy="") {
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
		
		if (strlen($sqlWhere) > 0) {
			$this->sqlWhere = " WHERE ($sqlWhere) ";
		}
		if (strlen($sqlGroupBy) > 0) {
			$this->sqlGroupBy = " GROUP BY $sqlGroupBy ";
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
		
		$sql = "SELECT i.*, a.folder AS folder, a.title AS album_title, a.show AS album_show, a.dynamic AS album_dynamic
			FROM " . prefix('images') . " i
			INNER JOIN " . prefix('albums') . " a ON i.albumid = a.id 
			$this->sqlWhere $this->sqlGroupBy $this->sqlOrderBy LIMIT $start, $perPage";
		$results = query_full_array($sql);
		
		// no results - page is off the edge of the world
		if (sizeof($results) == 0) {
			return NULL;
		}
			
		// get total number of imges in gallery
		$sql = "SELECT count(i.albumid) AS count FROM " . prefix('images') . "  i 
					INNER JOIN " . prefix('albums') . " a ON i.albumid = a.id 
					$this->sqlWhere $this->sqlGroupBy";					
		$totalCounts = query_full_array($sql);
		
		// we have a group by in the SQL query...
		if (sizeof($totalCounts) > 1) {
			$totalCount = sizeof($totalCounts);
		// normal query
		} else if (is_numeric($totalCounts[0]['count'])) {
			$totalCount = $totalCounts[0]['count'];
		// some versions of Zenphoto need this?
		} else if (is_numeric($totalCounts['count'])) {
			$totalCount = $totalCounts['count'];
		}
		
		for ($i = 0; $i < $totalCount; $i++) {
			if ($i >= $start && $i < ($start + $perPage)) {
				$filename = $results[$indexIntoFoundImages]['filename'];
				
				// save the filename into the list of all images
				$this->images[$i] = $filename;
				
				// save the DB results for use later on
				$this->data[$filename] = $results[$indexIntoFoundImages];
				$this->data[$filename]['album-data']['folder'] = $results[$indexIntoFoundImages]['folder'];
				$this->data[$filename]['album-data']['title'] = $results[$indexIntoFoundImages]['album_title'];
				$this->data[$filename]['album-data']['show'] = $results[$indexIntoFoundImages]['album_show'];
				$this->data[$filename]['album-data']['dynamic'] = $results[$indexIntoFoundImages]['album_dynamic'];
				
				$indexIntoFoundImages++;
			} else {
				// placeholder text
				$this->images[$i] = null;
			}
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
	function getImages($page=0, $firstPageCount=0, $sorttype=null, $sortdirection=null, $care=true) {
		// Return the cut of images based on $page. Page 0 means show all.
		if ($page == 0) {
			return $this->images;
		} else {
			// Only return $firstPageCount images if we are on the first page and $firstPageCount > 0
			if (($page==1) && ($firstPageCount>0)) {
				$pageStart = 0;
				$images_per_page = $firstPageCount;

			} else {
				if ($firstPageCount>0) {
					$fetchPage = $page - 2;
				} else {
					$fetchPage = $page - 1;
				}
				$images_per_page = max(1, getOption('photostream_images_per_page'));
				$pageStart = $firstPageCount + $images_per_page * $fetchPage;
			}
			if (sizeof($this->images) > 0)
			{
				return array_slice($this->images, $pageStart , $images_per_page);
			}
			return $this->images;
		}
	}
	
	/**
	 * Returns the number of images in this album (not counting its subalbums)
	 *
	 * @return int
	 */
	function getNumImages() {
		if (is_null($this->images)) {
			return count($this->getImages(0,0,NULL,NULL,false));
		}
		return count($this->images);
	}
	
	// overloaded functions inherited from Album
	// don't want them to do anything
	function save() {}	
	function loadFileNames($dirs=false) {}	
	function getAlbums($page=0, $sorttype=null, $sortdirection=null, $care=true) {}
}
?>