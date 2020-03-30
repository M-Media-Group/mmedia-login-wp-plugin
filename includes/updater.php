<?php
/**
 * Main Plugin Filters.
 */
defined('ABSPATH') or die('No script kiddies please!');

if (!class_exists('Smashing_Updater')) {
    wp_die('No plugin updater available. Contact M Media for help.');
}
$updater = new Smashing_Updater(mmedia_plugin_login_url);
$updater->set_username('M-Media-Group');
$updater->set_repository('mmedia-login-wp-plugin');
/*
$updater->authorize( 'abcdefghijk1234567890' ); // Your auth code goes here for private repos
 */
$updater->initialize();
