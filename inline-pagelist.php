<?php
/*
Plugin Name: Alkivia PageList
Plugin URI: http://alkivia.org/wordpress/pagelist
Description: Small plugin to include inline pages menus. You can use it to have a list of pages or posts inside a post. All you need is the top page id, the tag or the category.
Version: 2.0
Author: Jordi Canals
Author URI: http://alkivia.org
 */

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

define ( 'PLIST_PATH', dirname(__FILE__) );

/**
 * Sets an admin warning regarding required PHP version.
 * Is called in action 'admin_notices'
 *
 * @hook action 'admin_notices'
 * @return void
 */
function _plist_php_warning() {

	$data = get_plugin_data(__FILE__);
	load_plugin_textdomain('pagelist', false, basename(dirname(__FILE__)) .'/lang');

	echo '<div class="error"><p><strong>' . __('Warning:', 'pagelist') . '</strong> '
		. sprintf(__('The active plugin %s is not compatible with your PHP version.', 'pagelist') .'</p><p>',
			'&laquo;' . $data['Name'] . ' ' . $data['Version'] . '&raquo;')
		. sprintf(__('%s is required for this plugin.', 'pagelist'), 'PHP 5.2 ')
		. '</p></div>';
}

// Check required PHP version.
if ( version_compare(PHP_VERSION, '5.2.0', '<') ) {
	// Send an armin warning
	add_action('admin_notices', '_plist_php_warning');
} else {
    require_once ( PLIST_PATH . '/framework/loader.php');
    require_once ( PLIST_PATH . '/includes/pagelist.php');

    ak_create_object('pagelist', new inlinePageList(__FILE__, 'pagelist'));
}
