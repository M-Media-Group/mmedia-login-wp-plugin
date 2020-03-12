<?php
/**
 * Main Plugin Filters.
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
