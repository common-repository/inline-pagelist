<?php
/**
 * Alkivia PageList Plugin.
 * Small plugin to include inline pages or posts menus.
 * You can use it to have a list of pages or posts inside a post.
 * All you need is the top page id, the tag or the category id.
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

require_once( AK_CLASSES . '/abstract/plugin.php');

/**
 * Class plistPageList.
 * Manages the main plugin functionallity.
 *
 * @author	Jordi Canals
 * @package	PageList
 * @link	http://alkivia.org
 */
class inlinePageList extends akPluginAbstract
{

	/**
	 * Initializes the plugin.
	 * Sets the filters to run the plugin.
	 *
	 * @return void
	 */
	protected function moduleLoad ()
	{
        require_once( PLIST_PATH . '/includes/templates.php' );
		add_shortcode('ipagelist', array($this, 'pagelistShortCode'));

		// The content filter is deprecated since 2.0
	    add_filter('the_content', array($this, 'contentFilter'));
	}

	/**
	 * Shortcode for the content.
	 * Use ipagelist on the content with the proper paramenters.
	 *
	 * @since 2.0
	 * @link http://wiki.alkivia.org/pagelist/tags
	 *
	 * @param array $atr Atributes from the shortcode.
	 * @return string Content to display.
	 */
	public function pagelistShortCode ( $atts )
	{
	    $defaults = array('cat' => 0,
	                      'page' => 0,
	                      'tag' => '',
	                      'depth' => 0,    // Used only for pages.
	                      'title' => '',
	                      'num' => 5,      // Not used on pages.
	                      'link' => 1,     // Link loist title to page, category or tag
	                );

        extract(shortcode_atts($defaults, $atts), EXTR_SKIP);

        if ( 0 != $page ) {
            $out = $this->getChildPages($page, $depth, $title, $link);
        } elseif ( 0 != $cat ) {
            $out = $this->getPosts("cat={$cat}&showposts={$num}", $title, $link);
        } elseif ( ! empty($tag) ) {
            $out = $this->getPosts("tag={$tag}&showposts={$num}", $title, $link);
        } else {
            $out = '';
        }

	    return $out;
	}

	/**
	 * Function to get the pages from given parameters.
	 *
	 * @param int $page_ID	Parent page ID
	 * @param int $depth	Levels to show. 0 (default) for all levels.
	 * @param string $title	The title to show for the list. Empty for a default title, '0' to disble the title.
	 * @return string		Formated list of pages.
	 */
	public function getChildPages ( $page_ID, $depth = 0, $title = '', $link = 1 )
	{
		$args = array(	'title_li' => '',
						'child_of' => $page_ID,
						'depth' => $depth,
						'echo' => 0,
						'sort_column' => apply_filters('ak_pagelist_page_sort_column', 'menu_order, post_title'),
						'sort_order' => apply_filters('ak_pagelist_page_order', 'ASC')
					);

		$pages = wp_list_pages($args);
		if ( empty($pages) ) {
			$content = '';
		} else {
			$content = '<div id="inline_pagelist">';

			if ( '0' == $title ) {
				$title = '';
			} elseif ( empty($title) ) {
				$title = get_the_title($page_ID);
			}
			if ( ! empty($title) ) {
				if ( $page_ID == get_the_ID() || ! $link ) {
					$content .= "<p><strong>{$title}</strong></p>";
				} else {
					$content .= "<p><a href='". get_permalink($page_ID) ."'><strong>{$title}</strong></a></p>";
				}
			}

			$content .= '<ul>';
			$content .= preg_replace('|(<li class=.*current_page_item.*)<a href.*>(.*)</a>|', '$1$2', $pages);
			$content .= '</ul></div>';
		}

		return $content;
	}

	/**
	 * Creates the posts list from a set of query parameters.
	 *
	 * @param array|string $params Query Options.
	 * @param string $title	Title for the list.
	 * @return string List content.
	 */
	public function getPosts ( $params, $title = '', $link = 1 )
	{
		$this->savePost();

		$defaults = array(  'cat' => 0,
		                    'tag' => '',
							'order' => 'DESC',
							'orderby' => 'date',
		                    'post__not_in' => array(get_the_ID())
		            );
        $args = wp_parse_args($params, $defaults);

        $args['order'] = apply_filters('ak_pagelist_post_order', $args['order']);
        $args['orderby'] = apply_filters('ak_pagelist_post_orderby', $args['orderby']);

		$url = '';
		if ( 0 != $args['cat'] ) {
			$parent = $args['cat'];

			while ( 0 != $parent ) {
				$category = get_category($parent);
				$url = '/' . $category->slug . $url;
				$parent = $category->parent;
			}
			$base = get_option('category_base');
			if ( ! $base ) {
				$base = 'category';
			}
			$url = get_bloginfo('url') . "/{$base}{$url}";

		} elseif ( ! empty($args['tag']) ) {
			$base = get_option('tag_base');
			if ( ! $base ) {
				$base = 'tag';
			}
			$url = get_bloginfo('url') . "/{$base}/{$args['tag']}";
		}

		$content = '<div id="inline_pagelist">';

		if ( '0' == $title ) {
			$title = '';
		} elseif ( empty($title) ) {
			$title = __('Related Posts', $this->ID);
		}
		if ( ! empty($title) ) {
		    if ( $link ) {
			    $content .= "<p><a href='{$url}'><strong>{$title}</strong></a></p>";
		    } else {
			    $content .= "<p><strong>{$title}</strong></p>";
		    }
		}

		$q = new WP_Query($args);
		if ( $q->have_posts() ) {
			$content .= '<ul>';
			while ( $q->have_posts() ) {
				$q->the_post();

				$content .= '<li><a href="';
				$content .= get_permalink();
				$content .= '">';
				if ( get_the_title() ) {
					$content .= get_the_title();
				} else {
					$content .= get_the_ID();
				}
				$content .= '</a></li>';
			}
			$content .= '</ul>';
		}
		$content .= '</div>';

		$this->restorePost();
		return $content;
	}

	/**
	 * Content filter to replace tags.
	 *
	 * @deprecated since 2.0
	 * @hook filter 'the_content'
	 *
	 * @param string $content Post content
	 * @return string The post content with the tag replacements (Which includes the list of pages or posts)
	 */
	function contentFilter( $content )
	{
		// This is to prevent replacements in plugin documentation pages.
		if ( $meta = get_post_meta(get_the_ID(), 'pagelist_bypass', true) ) {
			return $content;
		}

		$pattern = "|\[pagelist (.+)\]|";
		$content = preg_replace_callback($pattern, array($this, '_pagesCB'), $content);

		$pattern = "|\[taglist (.+)\]|";
		$content = preg_replace_callback($pattern, array($this, '_tagsCB'), $content);

		$pattern = "|\[catlist (.+)\]|";
		$content = preg_replace_callback($pattern, array($this, '_catCB'), $content);

		return $content;
	}

	/**
	 * Callback for pagelist replacements.
	 *
	 * @deprecated since 2.0
	 * @hook callback for _contentFilter()
	 *
	 * @param array $matches The matched tag in post content. Index 1 have the three options as a comma separated string:
	 * 		- 0: ID for the post
	 * 		- 1: Levels to show. Default 0 (All levels)
	 * 		- 2: Show title. 0 = No (Default), 1 = Yes.
	 * @return string The formated list of pages.
	 */
	function _pagesCB( $matches ) {
		$args = explode(',', $matches[1]);

		$id			= (int) $args[0];							// Parent page ID
		$depth		= ( isset($args[1]) ) ? (int) $args[1] : 0;	// All levels by default
		$title		= ( isset($args[2]) ) ? $args[2] : '';		// Title to show

		return $this->getChildPages($id, $depth, $title);
	}

	/**
	 * Callback for taglist replacements.
	 *
	 * @deprecated since 2.0
	 * @hook callback for _contentFilter()
	 *
	 * @param array $matches The matched tag in post content. Index 1 have the three options as a comma separated string:
	 * 		- 0: Tag name.
	 * 		- 1: Number of posts to show. Defaults to 5.
	 * 		- 2: Title. If set, the title for the list, If not "Related Posts" will be shown, If "0" no title will be shown.
	 * @return string The formated list of posts.
	 */
	function _tagsCB( $matches ) {
		$args = explode(',', $matches[1]);

		$tag	= $args[0];														// Tag Name
		$number	= ( isset($args[1]) && !empty($args[1]) ) ? (int) $args[1] : 5;	// 5 posts by default.
		$title	= ( isset($args[2]) ) ? $args[2] : ''; 							// Show title.

		$list_options = array(	'showposts' => $number,
								'what_to_show' => 'posts',
								'nopaging' => 0,
								'post_status' => 'publish',
								'tag' => $tag);

		return $this->getPosts($list_options, $title);
	}

	/**
	 * Callback for catlist replacements.
	 *
	 * @deprecated since 2.0
	 * @hook callback for _contentFilter()
	 *
	 * @param array $matches The matched tag in post content. Index 1 have the three options as a comma separated string:
	 * 		- 0: Category ID.
	 * 		- 1: Number of posts to show. Defaults to 5.
	 * 		- 2: Title. If set, the title for the list, If not "Related Posts" will be shown, If "0" no title will be shown.
	 * @return string The formated list of posts.
	 */
	function _catCB( $matches ) {
		$args = explode(',', $matches[1]);

		$cat_ID	= (int) $args[0];												// Category ID
		$number	= ( isset($args[1]) && !empty($args[1]) ) ? (int) $args[1] : 5;	// 5 posts by default.
		$title	= ( isset($args[2]) ) ? $args[2] : ''; 							// Show title.

		$list_options = array(	'showposts' => $number,
								'what_to_show' => 'posts',
								'nopaging' => 0,
								'post_status' => 'publish',
								'cat' => $cat_ID);

		return $this->getPosts($list_options, $title);
	}
}
