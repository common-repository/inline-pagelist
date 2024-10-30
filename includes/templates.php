<?php
/**
 * Alkivia PageList Plugin. Functions for Templates.
 *
 * @version		$Rev: 200886 $
 * @author		Jordi Canals
 * @copyright   Copyright (C) 2008, 2009, 2010 Jordi Canals
 * @license		GNU General Public License version 2
 * @link		http://alkivia.org
 * @package		Alkivia
 * @subpackage	Pagelist
 *

	Copyright 2008, 2009, 2010 Jordi Canals <devel@jcanals.cat>

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	version 2 as published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Template function to get a list of subpages from a given parent page or posts from a tag or category.
 * It's preferred to use ipagelist()
 *
 * @see ipagelist()
 * @param int $page_ID			Parent page ID from where to show child pages.
 * @param int $depth			Number of levels to show. 0 (default) for all levels.
 * @param string|boolean $title	The title to show. By default shows a default title.
 * 									- true or empty for a default title
 * 									- false or 0: hide the title.
 * @return void
 */
function ipagelist_show_pages ($page_ID, $depth = 0, $title = '')
{
	// For compatibility to 1.2
	if ( true === $title ) {
		$title = '';
	} elseif ( false === $title ) {
		$title = '0';
	}

	echo ak_get_object('pagelist')->getChildPages($page_ID, $depth, $title);
}

/**
 * Template function to show posts with specific tag
 * It's preferred to use ipagelist()
 *
 * @see ipagelist()
 * @param string $tag_name		Tag Name to show pages of.
 * @param int $number			Number of posts to show. Defaults to 5.
 * @param string|boolean $title	Title text to show. Defaults to 'Related Posts'.
 * 									- true or empty: default title
 * 									- false or 0: hide the title.
 * @return void
 */
function ipagelist_show_tag ( $tag_name, $number = 5, $title = '', $link = 1)
{
	if ( true === $title ) {
		$title = '';
	} elseif ( false === $title ) {
		$title = '0';
	}

	$list_options = array(	'showposts' => $number,
							'what_to_show' => 'posts',
							'nopaging' => 0,
							'post_status' => 'publish',
							'tag' => $tag_name);

	echo ak_get_object('pagelist')->getPosts($list_options, $title, $link);
}

/**
 * Template function to show posts with specific category
 * It's preferred to use ipagelist()
 *
 * @see ipagelist()
 * @param int $cat_ID		Category ID to show pages of.
 * @param int $number		Number of posts to show. Defaults to 5.
 * @param string|boolean $title	Title text to show. Defaults to 'Related Posts'.
 * 									- true or empty: default title
 * 									- false or 0: hide the title.
 * @return void
 */
function ipagelist_show_category ($cat_ID, $number = 5, $title = '', $link = 1)
{
	if ( true === $title ) {
		$title = '';
	} elseif ( false === $title ) {
		$title = '0';
	}

	$list_options = array(	'showposts' => $number,
							'what_to_show' => 'posts',
							'nopaging' => 0,
							'post_status' => 'publish',
							'cat' => $cat_ID);

	echo ak_get_object('pagelist')->getPosts($list_options, $title, $link);
}

/**
 * Generic function to show all kind of lists.
 * Values are readed from posts custom fields.
 * Parameters are defaults overriden by the custom fields values.
 *
 * @param int $depth			Page levels to show. Only used for pages.
 * @param int $number			Number of posts to show. Defaults to 5. Not used for pages.
 * @param string|boolean $title	Title text to show. Defaults to 'Related Posts'.
 * 									- true or empty: default title
 * 									- false or 0: hide the title.
 * @return void
 */
function ipagelist( $depth = 0, $number = 5, $title = '', $link = 1 ) {

	if ( $title === true ) {
		$title = '';
	} elseif ( $title === false ) {
		$title = '0';
	}
	$post_ID = get_the_ID();

	if ( $id = get_post_meta($post_ID, 'pagelist_category', true) )
		$call = 'cat';
	elseif ( $id = get_post_meta($post_ID, 'pagelist_tag', true) )
		$call = 'tag';
	elseif ( $id = get_post_meta($post_ID, 'pagelist_page', true) ) {
		$call = 'pag';
		if ( 'this' == strtolower($id) ) {
			$id = $post_ID;
		}
	} else
		return;


	$temp = get_post_meta($post_ID, 'pagelist_depth', true);
	if ( ! empty($temp) ) {
		$depth = ( 'all' == strtolower($temp) ) ? 0 : (int) $temp;
	}
	if ( $temp = get_post_meta($post_ID, 'pagelist_number', true) ) {
		$number = (int) $temp;
	}
	$temp = get_post_meta($post_ID, 'pagelist_title', true);
	if ( !empty($temp) ) {
		$title = ( 'hide' == strtolower($temp) ) ? '0' : $temp;
	}
	switch ( $call ) {
		case 'cat':
			ipagelist_show_category($id, $number, $title, $link);
			break;
		case 'tag':
			ipagelist_show_tag($id, $number, $title, $link);
			break;
		case 'pag':
			ipagelist_show_pages($id, $depth, $title, $link);
			break;
	}
}
