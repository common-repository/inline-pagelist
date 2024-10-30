<?php
/**
 * Plugins related functions and classes.
 *
 * @version		$Rev: 200886 $
 * @author		Jordi Canals
 * @copyright   Copyright (C) 2008, 2009, 2010 Jordi Canals
 * @license		GNU General Public License version 2
 * @link		http://alkivia.org
 * @package		Alkivia
 * @subpackage	Framework
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

require_once ( AK_CLASSES . '/abstract/module.php' );

/**
 * Abtract class to be used as a plugin template.
 * Must be implemented before using this class and it's recommended to prefix the class to prevent collissions.
 * There are some special functions that have to be declared in implementations to perform main actions:
 * 		- pluginActivate (Protected) Actions to run when activating the plugin.
 * 		- pluginDeactivate (Protected) Actions to run when deactivating the plugin.
 * 		- pluginUpdate (Protected) Actions to update the plugin to a new version. (Updating version on DB is done after this).
 * 						Takes plugin running version as a parameter.
 *		- pluginsLoaded (Protected) Runs at 'plugins_loaded' action hook.
 *		- registerWidgets (Protected) Runs at 'widgets_init' action hook.
 *
 * @author		Jordi Canals
 * @package		Alkivia
 * @subpackage	Framework
 * @link		http://wiki.alkivia.org/framework/classes/plugin
 *
 * @uses		plugins.php
 */
abstract class akPluginAbstract extends akModuleAbstract
{
	/**
	 * Class constructor.
	 * Calls the implementated method 'startUp' if it exists. This is done at plugins loading time.
	 * Prepares admin menus by seting an action for the implemented method '_adminMenus' if it exists.
	 *
	 * @param string $mod_file	Full main plugin's filename (absolute to root).
	 * @param string $ID  Plugin short name (known as plugin ID).
	 * @return spostsPlugin|false	The plugin object or false if not compatible.
	 */
	public function __construct( $mod_file, $ID = '' )
	{
        parent::__construct('plugin', $ID, $mod_file);

		if ( $this->isCompatible() ) {
			// Activation and deactivation hooks.
			register_activation_hook($this->mod_file, array($this, 'activate'));
			register_deactivation_hook($this->mod_file, array($this, 'deactivate'));

			add_action('plugins_loaded', array($this, 'update'));
		}
	}

	/**
	 * Fires on plugin activation.
	 * @return void
	 */
	protected function pluginActivate () {}

	/**
	 * Fires on plugin deactivation.
	 * @return void
	 */
	protected function pluginDeactivate () {}

	/**
	 * Updates the plugin to a new version.
	 * @param string $version Old plugin version.
	 * @return void
	 */
	protected function pluginUpdate ( $version ) {}

	/**
	 * Fires when plugins have been loaded.
	 * @return void
	 */
	protected function pluginsLoaded () {}

	/**
	 * Fires on Widgets init.
	 * @return void
	 */
	protected function registerWidgets () {}

	/**
	 * Activates the plugin. Only runs on first activation.
	 * Saves the plugin version in DB, and calls the 'pluginActivate' method.
	 *
	 * @uses do_action() Calls 'ak_activate_<modID>_plugin' action hook.
	 * @hook register_activation_hook
	 * @access private
	 * @return void
	 */
	final function activate()
	{
        $this->pluginActivate();

        // Save options and version
		$this->cfg->saveOptions($this->ID);
		add_option($this->ID . '_version', $this->mod_data['Version']);

		do_action('ak_activate_' . $this->ID . '_plugin');
	}

	/**
	 * Deactivates the plugin.
	 *
	 * @uses do_action() Calls 'ak_deactivate_<modID>_plugin' action hook.
	 * @hook register_deactivation_hook
	 * @access private
	 * @return void
	 */
	final function deactivate()
	{
	    $this->pluginDeactivate();
		do_action('ak_deactivate_' . $this->ID . '_plugin');
	}

	/**
	 * Init the plugin (In action 'plugins_loaded')
	 * Here whe call the 'update' and 'init' functions. This is done after the plugins are loaded.
	 * Also the plugin version and settings are updated here.
	 *
	 * @hook action plugins_loaded
	 * @access private
	 * @return void
	 */
	final function update()
	{
		// First, check if the plugin needs to be updated.
		if ( $this->needs_update ) {
			$version = get_option($this->ID . '_version');
			$this->pluginUpdate($version);

			$this->cfg->saveOptions($this->ID);
			update_option($this->ID . '_version', $this->mod_data['Version']);

			do_action('ak_' . $this->ID . '_updated');
		}

		$this->pluginsLoaded();
	}

	/**
	 * Inits the widgets (In action 'widgets_init')
	 * Before loading the widgets, we check that standard sidebar is present.
	 *
	 * @hook action 'widgets_init'
	 * @return void
	 */
	final function widgetsInit()
	{
		if (    class_exists('WP_Widget') && function_exists('register_widget') && function_exists('unregister_widget') ) {
			$this->registerWidgets();
		} else {
			add_action('admin_notices', array($this, 'noSidebarWarning'));
		}
	}

	/**
	 * Checks if the plugin is compatible with the current WordPress version.
	 * If it's not compatible, sets an admin warning.
	 *
	 * @return boolean	Plugin is compatible with this WordPress version or not.
	 */
	final protected function isCompatible()
	{
		global $wp_version;

		if ( version_compare($wp_version, $this->mod_data['Requires'] , '>=') ) {
			return true;
		} elseif ( ! has_action('admin_notices', array($this, 'noCompatibleWarning')) ) {
			add_action('admin_notices', array($this, 'noCompatibleWarning'));
		}

		return false;
	}

	/**
	 * Shows a warning message when the plugin is not compatible with current WordPress version.
	 * This is used by calling the action 'admin_notices' in isCompatible()
	 *
	 * @hook action admin_notices
	 * @access private
	 * @return void
	 */
	final function noCompatibleWarning()
	{
		$this->loadTranslations(); // We have not loaded translations yet.

		echo '<div class="error"><p><strong>' . __('Warning:', 'akfw') . '</strong> '
			. sprintf(__('The active plugin %s is not compatible with your WordPress version.', 'akfw'),
				'&laquo;' . $this->mod_data['Name'] . ' ' . $this->mod_data['Version'] . '&raquo;')
			. '</p><p>' . sprintf(__('WordPress %s is required to run this plugin.', 'akfw'), $this->mod_data['Requires'])
			. '</p></div>';
	}

	/**
	 * Shows an admin warning when not using the WordPress standard sidebar.
	 * This is done by calling the action 'admin_notices' in isStandardSidebar()
	 *
	 * @hook action admin_notices
	 * @access private
	 * @return void
	 */
	final function noSidebarWarning()
	{
		$this->loadTranslations(); // We have not loaded translations yet.

		echo '<div class="error"><p><strong>' . __('Warning:', $this->ID) . '</strong> '
			. __('Standard sidebar functions are not present.', $this->ID) . '</p><p>'
			. sprintf(__('It is required to use the standard sidebar to run %s', $this->ID),
				'&laquo;' . $this->mod_data['Name'] . ' ' . $this->mod_data['Version'] . '&raquo;')
			. '</p></div>';
	}
}
