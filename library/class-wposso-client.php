<?php

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Main Class.
 *
 * @author Justin Greer <justin@justin-greer.com>
 */
class WPOSSO_Client
{
    /** Version */
    public $version = '1.4.5';

    /** Server Instance */
    public static $_instance = null;

    /** Default Settings */
    protected $defualt_settings = [
        'client_id'             => '',
        'client_secret'         => '',
        'server_url'            => 'https://mmediagroup.fr/oauth/',
        'server_oauth_trigger'  => 'oauth',
        'server_auth_endpoint'  => 'authorize',
        'server_token_endpont'  => 'token',
        'server_user_endpoint'  => 'user',
        'redirect_to_dashboard' => 1,
    ];

    public function __construct()
    {
        add_action('init', [__CLASS__, 'includes']);
    }

    /**
     * populate the instance if the plugin for extendability.
     *
     * @return object plugin instance
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * plugin includes called during load of plugin.
     *
     * @return void
     */
    public static function includes()
    {
        require_once WPOSSO_FILE.'/includes/functions.php';
        require_once WPOSSO_FILE.'/includes/admin-options.php';
        require_once WPOSSO_FILE.'/includes/rewrites.php';
    }

    /**
     * Plugin Setup.
     */
    public function setup()
    {

        // Require parent plugin
        if (!class_exists('M_Media') and current_user_can('activate_plugins')) {
            // Stop activation redirect and show error
            wp_die('This plugin requires the M Media to be installed and active. <br><a href="'.admin_url('plugins.php').'">&laquo; Return to Plugins</a>');
        }

        // Set defaults
        $options = get_option('wposso_options');
        if (!isset($options['server_url'])) {
            update_option('wposso_options', $this->defualt_settings);
        }

        $this->install();
    }

    /**
     * Plugin Initializer.
     */
    public function plugin_init()
    {
        if (!class_exists('M_Media') and current_user_can('activate_plugins')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('"M Media" is a required dependant plugin for "Log in with M Media" to work.');
        }
        require_once WPOSSO_FILE.'/includes/updater.php';
    }

    /**
     * Plugin Install.
     */
    public function install()
    {
    }

    /**
     * Plugin Upgrade.
     */
    public function upgrade()
    {
    }
}

function _WPOSSO()
{
    return WPOSSO_Client::instance();
}

$GLOBAL['WPOSSO'] = _WPOSSO();
