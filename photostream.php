<?php
/**
 * Photostream
 *
 * Enables you to replicate the photostream feature of Flickr
 *
 * @author Marcus Wong (wongm)
 * @package plugins
 */

$plugin_description = gettext("Enables you to replicate the photostream functionaility of Flickr. Supports custom SQL on the images: WHRERE, ORDER BY and GROUP BY.");
$plugin_author = "Marcus Wong (wongm)";
$plugin_version = '1.0.0'; 
$plugin_URL = "http://code.google.com/p/wongm-zenphoto-plugins/";
$option_interface = 'photostreamOptions';

require_once(dirname(__FILE__).'/photostream/class-photostream.php');
require_once(dirname(__FILE__).'/photostream/class-photostream-album.php');
require_once(dirname(__FILE__).'/photostream/class-photostream-image.php');
require_once(dirname(__FILE__).'/photostream/photostream-template-functions.php');

zp_register_filter('checkPageValidity', 'photostreamOptions::pageCount');

/**
 * Plugin option handling class
 *
 */
class photostreamOptions {
	
	static function pageCount($count, $gallery_page, $page) {
		if (stripSuffix($gallery_page) == 'photostream') {
    		return true;
		}
	}
	
	function photostreamOptions() {
		setOptionDefault('photostream_sort', "date");
		setOptionDefault('photostream_images_per_page', getOption('images_per_page'));
	}
	
	function getOptionsSupported() {
		$list = array(gettext("Date") => "date",gettext("Image ID") => "imageid");
		return array(	gettext('Images per page') => array('key' => 'photostream_images_per_page', 'type' => OPTION_TYPE_TEXTBOX,
										'order' => 2,
										'desc' => gettext("Images of images displayed on each photostream page.")),
									gettext('Sort type') => array('key' => 'photostream_sort', 'type' => OPTION_TYPE_RADIO, 'buttons' => $list,
										'order' => 1,
										'desc' => gettext("Default sort of the images in the photostream."))
		);
	}
}

/**
 * Returns the URL of the page number passed as a parameter
 *
 * @param int $page Which page is desired
 * @param int $total How many pages there are.
 * @return string
 */
function getPhotostreamPageURL($page, $total=null)
{
	global $_zp_gallery_page;

	$pg = substr($_zp_gallery_page, 0, -4);
	$pagination1 = '/page/'.$pg.'/';
	$pagination2 = 'p='.$pg.'&';
	
	if ($page > 0) {
		if ($page == 1) {
			// Just return the gallery base path for ZP_INDEX (no /page/x)
			if (empty($pagination2)) {
				return rewrite_path('/', '/');
			} else {
				return rewrite_path($pagination1, "/index.php?" . substr($pagination2, 0, -1));
			}
		} else if ($page > 1) {
			return rewrite_path($pagination1 . $page . "/", "/index.php?" . $pagination2 . 'page=' . $page);
		}
	}
}
?>