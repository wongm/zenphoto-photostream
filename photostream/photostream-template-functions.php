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
function setCustomPhotostream($sqlWhere="", $sqlGroupBy="", $sqlOrderBy="", $sqlJoin="") {
	global $_zp_gallery, $_zp_current_photostream, $_zp_images;
	
	// reset the image collection to enable multiple calls from the same page
	$_zp_images = null;
	
	// now create actual Photostream
	$_zp_current_photostream = new Photostream($_zp_gallery, $sqlWhere, $sqlGroupBy, $sqlOrderBy, $sqlJoin);
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
		$photostreamImageKey = array_shift($_zp_images);
		$_zp_current_image = new PhotostreamImage($_zp_current_album, $photostreamImageKey);
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
		$photostreamImageKey = array_shift($_zp_images);
		$_zp_current_image = new PhotostreamImage($_zp_current_album, $photostreamImageKey);
		return true;
	}
}

/**
 * Returns the title of the album that contains the current image.
 *
 * @return string
 */
function getAlbumTitleForPhotostreamImage($locale = NULL) {
	global $_zp_current_image;
	
	// if we aren't a photostream, use the album title via ZenPhoto core fuinctionality
	$defaultTitle = $_zp_current_image->getAlbum()->getTitle();
	if (strlen($defaultTitle) > 0)
	{
    	return $defaultTitle;
	}
	
	$albumTitle = $_zp_current_image->getAlbum()->title;
	if ($locale !== 'all') {
		$albumTitle = get_language_string($albumTitle, $locale);
	}
	return unTagURLs($albumTitle);
}

/**
 * Returns the folder name of the album that contains the current image.
 *
 * @return string
 */
function getAlbumFolderForPhotostreamImage() {
	global $_zp_current_image;
	return $_zp_current_image->getAlbumName();
}

/**
 * Returns a piece of text indicating the current slice of the photostream being displayed. 
 * In format "X to Y".
 *
 * @return string
 */
function getNumberCurrentDisplayedRecords($prefix=NULL, $suffix=NULL) {
	$lowerBound = (getOption('photostream_images_per_page') * (getCurrentPage() - 1)) + 1;
	$upperBound = $lowerBound + getOption('photostream_images_per_page') - 1;
	// reset upper value when on last page
	if ($upperBound > getNumPhotostreamImages()){
		$upperBound = getNumPhotostreamImages();
	}
	// prevent display if page number is too high
	if ($upperBound > $lowerBound) {
		return "$prefix$lowerBound to $upperBound$suffix";
	}
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
	$imageCount = getNumPhotostreamImages();
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
		printLinkHTML(getNextPhotostreamPageURL(), $text, $title, $class, $id);
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
		printLinkHTML(getPrevPhotostreamPageURL(), $text, $title, $class, $id);
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
	$current = getCurrentPage();
	$total = max(1,getTotalPhotostreamPages());
	$nav = getPhotostreamPageNavList($oneImagePage, $navlen, $firstlast, $current, $total);
	if (count($nav) < 4) {
		$class .= ' disabled_nav';
	}
	?>
	<div <?php if ($id) echo ' id="$id"'; ?> class="<?php echo $class; ?>">
		<ul class="<?php echo $class; ?>">
			<?php
			$prev = $nav['prev'];
			unset($nav['prev']);
			$next = $nav['next'];
			unset($nav['next']);
			if ($nextprev) {
				?>
				<li class="prev">
					<?php
					if ($prev) {
						printLinkHTML($prev, html_encode($prevtext), gettext('Previous Page'));
					} else {
						?>
						<span class="disabledlink"><?php echo html_encode($prevtext); ?></span>
						<?php
					}
					?>
				</li>
				<?php
			}
			$last = NULL;
			if ($firstlast) {
				?>
				<li class="<?php if($current==1) echo 'current'; else echo 'first'; ?>">
				<?php
				if($current == 1) {
					echo '1';
				} else {
					printLinkHTML($nav[1], 1, gettext("Page 1"));
				}
				?>
				</li>
				<?php
				$last = 1;
				unset($nav[1]);
			}
			foreach ($nav as $i=>$link) {
					$d = $i - $last;
				if ($d > 2) {
					?>
					<li>
						<?php
						$k1 = $i - (int) (($i - $last) / 2);
						printLinkHTML(getPhotostreamPageURL($k1, $total), '...', sprintf(ngettext('Page %u','Page %u',$k1),$k1));
						?>
					</li>
					<?php
				} else if ($d == 2) {
					?>
					<li>
						<?php
						$k1 = $last+1;
						printLinkHTML(getPhotostreamPageURL($k1, $total), $k1, sprintf(ngettext('Page %u','Page %u',$k1),$k1));
						?>
					</li>
					<?php
				}
				?>
				<li<?php if ($current==$i) echo ' class="current"'; ?>>
				<?php
				if ($i == $current) {
					echo $i;
				} else {
					$title = sprintf(ngettext('Page %1$u','Page %1$u', $i),$i);
					printLinkHTML($link, $i, $title);
				}
				?>
				</li>
				<?php
				$last = $i;
				unset($nav[$i]);
				if ($firstlast && count($nav) == 1) {
					break;
				}
			}
			if ($firstlast) {
				foreach ($nav as $i=>$link) {
					$d = $i - $last;
					if ($d > 2) {
						$k1 = $i - (int) (($i - $last) / 2);
						?>
						<li>
							<?php printLinkHTML(getPhotostreamPageURL($k1, $total), '...', sprintf(ngettext('Page %u','Page %u',$k1),$k1)); ?>
						</li>
						<?php
					} else if ($d == 2) {
						$k1 = $last+1;
						?>
						<li>
							<?php printLinkHTML(getPhotostreamPageURL($k1, $total), $k1, sprintf(ngettext('Page %u','Page %u',$k1),$k1)); ?>
						</li>
						<?php
					}
					?>
					<li class="last<?php if ($current == $i) echo ' current'; ?>">
						<?php
						if($current == $i) {
							echo $i;
						} else {
							printLinkHTML($link, $i, sprintf(ngettext('Page %u','Page %u',$i),$i));
						}
						?>
					</li>
				<?php
				}
			}
			if ($nextprev) {
				?>
				<li class="next">
					<?php
					if ($next) {
						printLinkHTML($next, html_encode($nexttext), gettext('Next Page'));
					} else {
						?>
						<span class="disabledlink"><?php echo html_encode($nexttext); ?></span>
						<?php
					}
					?>
				</li>
				<?php
			}
			?>
		</ul>
	</div>
	<?php
}

/**
 * returns a page nav list.
 *
 * @param bool $oneImagePage set to true if there is only one image page as, for instance, in flash themes
 * @param int $navlen Number of navigation links to show (0 for all pages). Works best if the number is odd.
 * @param bool $firstlast Add links to the first and last pages of you gallery
 * @param int $current the current page
 * @param int $total total number of pages
 *
 */
function getPhotostreamPageNavList($oneImagePage, $navlen, $firstlast, $current, $total) {
	$result = array();
	if (hasPrevPhotostreamPage()) {
		$result['prev'] = getPrevPhotostreamPageURL();
	} else {
		$result['prev'] = NULL;
	}
	if ($firstlast) {
		$result[1] = getPhotostreamPageURL(1, $total);
	}

	if ($navlen == 0) {
		$navlen = $total;
	}
	$extralinks = 2;
	if ($firstlast) $extralinks = $extralinks + 2;
	$len = floor(($navlen-$extralinks) / 2);
	$j = max(round($extralinks/2), min($current-$len-(2-round($extralinks/2)), $total-$navlen+$extralinks-1));
	$ilim = min($total, max($navlen-round($extralinks/2), $current+floor($len)));
	$k1 = round(($j-2)/2)+1;
	$k2 = $total-round(($total-$ilim)/2);

	for ($i=$j; $i <= $ilim; $i++) {
		$result[$i] = getPhotostreamPageURL($i, $total);
	}
	if ($firstlast) {
		$result[$total] = getPhotostreamPageURL($total, $total);
	}
	if (hasNextPhotostreamPage()) {
		$result['next'] = getNextPhotostreamPageURL();
	} else {
		$result['next'] = NULL;
	}
	return $result;
}
?>