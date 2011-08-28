<?php
/**
 * Photostream template functions
 *
 * @author Marcus Wong (wongm)
 * @package plugins
 */

 /**
 * Adds custom SQL to filter the results returned
 *
 */
function setCustomPhotostream($sqlWhere="", $sqlGroupBy="", $sqlOrderBy="") {
	global $_zp_gallery, $_zp_current_photostream;
	
	$_zp_current_photostream = new Photostream($_zp_gallery, $sqlWhere, $sqlGroupBy, $sqlOrderBy);
}

/**
 * Returns the number of images in the photostream.
 *
 * @return int
 */
function getNumPhotostreamImages() {
	global $_zp_gallery, $_zp_current_photostream;
	if (!is_object($_zp_current_photostream)) {
		$_zp_current_photostream = new Photostream($_zp_gallery);
	}
	return $_zp_current_photostream->getNumImages();
}

/**
 * Returns the next image on a page.
 * sets $_zp_current_image to the next image in the album.
 
 * Returns true if there is an image to be shown
 *
 * @return bool
 */
function next_photostream_image() {	
	global $_zp_images, $_zp_current_image, $_zp_current_album, $_zp_page,
				 $_zp_current_photostream;
	
	if (!is_object($_zp_current_photostream)) {
		$_zp_current_photostream = new Photostream($_zp_gallery);
	}
	
	if (is_null($_zp_images)) {
		set_context(ZP_INDEX | ZP_ALBUM);
		$_zp_images = $_zp_current_photostream->getImages($_zp_page);
		if (empty($_zp_images)) { return false; }
		$img = array_shift($_zp_images);
		$_zp_current_image = new PhotostreamImage($_zp_current_album, $img);
		save_context();
		add_context(ZP_IMAGE);
		return true;
	} else if (empty($_zp_images)) {
		$_zp_images = NULL;
		
		// reset this so correct pagaination occurs
		$_zp_current_album = $_zp_current_photostream;
		
		restore_context();
		return false;
	} else {
		$img = array_shift($_zp_images);
		$_zp_current_image = new PhotostreamImage($_zp_current_album, $img);
		return true;
	}
}
/**
 * Returns a piece of text indicating the current slice of the photostream being displayed. 
 * In format "X to Y".
 *
 * @return string
 */
function getNumberCurrentDisplayedRecords() {
	$lowerBound = (getOption('photostream_images_per_page') * (getCurrentPage() - 1)) + 1;
	$upperBound = $lowerBound + getOption('photostream_images_per_page') - 1;
	if ($upperBound > getNumPhotostreamImages()){
		$upperBound = getNumPhotostreamImages();
	}
	return "$lowerBound to $upperBound";
}

/**
 * Returns the number of pages for the current object
 *
 * @param bool $oneImagePage set to true if your theme collapses all image thumbs
 * or their equivalent to one page. This is typical with flash viewer themes
 *
 * @return int
 */
function getTotalPhotostreamPages() {
	$imageCount = max(1, getNumPhotostreamImages());
	$images_per_page = max(1, getOption('photostream_images_per_page'));
	$pageCount = ceil($imageCount / $images_per_page);
	return $pageCount;
}

/**
 * Returns true if there is a next page
 *
 * @return bool
 */
function hasPrevPhotostreamPage() { return (getCurrentPage() > 1); }

/**
 * Returns the URL of the previous page.
 *
 * @return string
 */
function getPrevPhotostreamPageURL()
{
	return getPhotostreamPageURL(getCurrentPage() - 1);
}

/**
 * Returns true if there is a next page
 *
 * @return bool
 */
function hasNextPhotostreamPage() { return (getCurrentPage() < getTotalPhotostreamPages()); }

/**
 * Returns the URL of the next page.
 *
 * @return string
 */
function getNextPhotostreamPageURL()
{
	return getPhotostreamPageURL(getCurrentPage() + 1);
}

/**
 * Prints the URL of the next page.
 *
 * @param string $text text for the URL
 * @param string $title Text for the HTML title
 * @param string $class Text for the HTML class
 * @param string $id Text for the HTML id
 */
function printNextPhotostreamPageLink($text, $title=NULL, $class=NULL, $id=NULL) {
	if (hasNextPage()) {
		printLink(getNextPhotostreamPageURL(), $text, $title, $class, $id);
	} else {
		echo "<span class=\"disabledlink\">$text</span>";
	}
}

/**
 * Returns the URL of the previous page.
 *
 * @param string $text The linktext that should be printed as a link
 * @param string $title The text the html-tag "title" should contain
 * @param string $class Insert here the CSS-class name you want to style the link with
 * @param string $id Insert here the CSS-ID name you want to style the link with
 */
function printPrevPhotostreamPageLink($text, $title=NULL, $class=NULL, $id=NULL) {
	if (hasPrevPage()) {
		printLink(getPrevPhotostreamPageURL(), $text, $title, $class, $id);
	} else {
		echo "<span class=\"disabledlink\">$text</span>";
	}
}

/**
 * Prints a page navigation including previous and next page links
 *
 * @param string $prevtext Insert here the linktext like 'previous page'
 * @param string $separator Insert here what you like to be shown between the prev and next links
 * @param string $nexttext Insert here the linktext like "next page"
 * @param string $class Insert here the CSS-class name you want to style the link with (default is "pagelist")
 * @param string $id Insert here the CSS-ID name if you want to style the link with this
 */
function printPhotostreamPageNav($prevtext, $separator, $nexttext, $class='pagenav', $id=NULL) {
	echo "<div" . (($id) ? " id=\"$id\"" : "") . " class=\"$class\">";
	printPrevPhotostreamPageLink($prevtext, gettext("Previous Page"));
	echo " $separator ";
	printNextPhotostreamPageLink($nexttext, gettext("Next Page"));
	echo "</div>\n";
}

/**
 * Prints a list of all pages.
 *
 * @param string $class the css class to use, "pagelist" by default
 * @param string $id the css id to use
 * @param int $navlen Number of navigation links to show (0 for all pages). Works best if the number is odd.
*/
function printPhotostreamPageList($class='pagelist', $id=NULL, $navlen=9) {
	printPhotostreamPageListWithNav(null, null, false, false, $class, $id, false, $navlen);
}

/**
 * Prints a full page navigation including previous and next page links with a list of all pages in between.
 *
 * @param string $prevtext Insert here the linktext like 'previous page'
 * @param string $nexttext Insert here the linktext like 'next page'
 * @param bool $oneImagePage set to true if there is only one image page as, for instance, in flash themes
 * @param string $nextprev set to true to get the 'next' and 'prev' links printed
 * @param string $class Insert here the CSS-class name you want to style the link with (default is "pagelist")
 * @param string $id Insert here the CSS-ID name if you want to style the link with this
 * @param bool $firstlast Add links to the first and last pages of you gallery
 * @param int $navlen Number of navigation links to show (0 for all pages). Works best if the number is odd.
 */
function printPhotostreamPageListWithNav($prevtext, $nexttext, $oneImagePage=false, $nextprev=true, $class='pagelist', $id=NULL, $firstlast=true, $navlen=9) {
	global $_zp_images;
	if (empty($_zp_images)) { return false; }
	$total = getTotalPhotostreamPages();
	$current = getCurrentPage();
	if ($total < 2) {
		$class .= ' disabled_nav';
	}
	if ($navlen == 0)
		$navlen = $total;
	$extralinks = 2;
	if ($firstlast) $extralinks = $extralinks + 2;
	$len = floor(($navlen-$extralinks) / 2);
	$j = max(round($extralinks/2), min($current-$len-(2-round($extralinks/2)), $total-$navlen+$extralinks-1));
	$ilim = min($total, max($navlen-round($extralinks/2), $current+floor($len)));
	$k1 = round(($j-2)/2)+1;
	$k2 = $total-round(($total-$ilim)/2);

	echo "<div" . (($id) ? " id=\"$id\"" : "") . " class=\"$class\">\n";
	echo "<ul class=\"$class\">\n";
	if ($nextprev) {
		echo "<li class=\"prev\">";
		printPrevPhotostreamPageLink($prevtext, gettext("Previous Page"));
		echo "</li>\n";
	}
	if ($firstlast) {
		echo '<li class="'.($current==1?'current':'first').'">';
		printLink(getPhotostreamPageURL(1, $total), 1, gettext("Page 1"));
		echo "</li>\n";
		if ($j>2) {
			echo "<li>";
			printLink(getPhotostreamPageURL($k1, $total), ($j-1>2)?'...':$k1, sprintf(ngettext('Page %u','Page %u',$k1),$k1));
			echo "</li>\n";
		}
	}
	for ($i=$j; $i <= $ilim; $i++) {
		echo "<li" . (($i == $current) ? " class=\"current\"" : "") . ">";
		if ($i == $current) {
			$title = sprintf(ngettext('Page %1$u (Current Page)','Page %1$u (Current Page)', $i),$i);
		} else {
			$title = sprintf(ngettext('Page %1$u','Page %1$u', $i),$i);
		}
		printLink(getPhotostreamPageURL($i, $total), $i, $title);
		echo "</li>\n";
	}
	if ($i < $total) {
		echo "<li>";
		printLink(getPhotostreamPageURL($k2, $total), ($total-$i>1)?'...':$k2, sprintf(ngettext('Page %u','Page %u',$k2),$k2));
		echo "</li>\n";
	}
	if ($firstlast && $i <= $total) {
		echo "\n  <li class=\"last\">";
		printLink(getPhotostreamPageURL($total, $total), $total, sprintf(ngettext('Page {%u}','Page {%u}',$total),$total));
		echo "</li>";
	}
	if ($nextprev) {
		echo "<li class=\"next\">";
		printNextPhotostreamPageLink($nexttext, gettext("Next Page"));
		echo "</li>\n";
	}
	echo "</ul>\n";
	echo "</div>\n";
}
?>