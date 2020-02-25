<?php
/**
 * Plugin Name: Log in with M Media
 * Plugin URI: https://mmediagroup.fr
 * Version: 1.0.3
 * Description: Provides Simple Single Sign On integration with M Media. Based off of the original work by http://dash10.digital
 * Author: M Media
 * Author URI: https://mmediagroup.fr
 * License: GPL2.
 *
 * This program is GLP but; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of.
 */
defined('ABSPATH') or die('No script kiddies please!');

if (!class_exists('Smashing_Updater')) {
    include_once plugins_url('mmedia/updater.php');
}
$updater = new Smashing_Updater(__FILE__);
$updater->set_username('M-Media-Group');
$updater->set_repository('mmedia-login-wp-plugin');
/*
$updater->authorize( 'abcdefghijk1234567890' ); // Your auth code goes here for private repos
 */
$updater->initialize();

if (!defined('WPOSSO_FILE')) {
    define('WPOSSO_FILE', plugin_dir_path(__FILE__));
}

// Require the main plugin clas
require_once WPOSSO_FILE.'/library/class-wposso-client.php';

add_action('admin_menu', [new WPOSSO_Client(), 'plugin_init']);
register_activation_hook(__FILE__, [new WPOSSO_Client(), 'setup']);
register_activation_hook(__FILE__, [new WPOSSO_Client(), 'upgrade']);
