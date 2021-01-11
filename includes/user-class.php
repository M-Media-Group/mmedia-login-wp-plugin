<?php

defined('ABSPATH') or die('No script kiddies please!');
/**
 * Class M_WPOSSO_User.
 *
 * @author Justin Greer <justin@justin-greer.com
 */
class M_WPOSSO_User
{
    public $user;
    public $files;
    public $notifications;
    private $access_token;
    private $base_api_url;

    public function __construct($token = null)
    {
        $options = get_option('wposso_options');
        $this->base_api_url = str_replace('/oauth/', '/api/', $options['server_url']);
        $this->set_user_token($token);
        $this->set_user_info();
    }

    public static function is_logged_in_as_mmedia()
    {
        if (!isset($_COOKIE["m_media_access_token"])) {
            return false;
        }
        return true;
    }

    private function set_user_token($token = null)
    {
        if (!$token) {
            if (!self::is_logged_in_as_mmedia()) {
                return false;
            }
            $token = $_COOKIE["m_media_access_token"];
        }
        $this->access_token = $token;
        return true;
    }

    private function set_user_info($token = null)
    {
        if (!$this->access_token) {
            $this->set_user_token($token);
        }
        $server_url = $this->base_api_url . 'user';
        $response = wp_remote_get($server_url, [
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => ['Authorization' => 'Bearer ' . $this->access_token,
                'Accept' => 'application/json'],
            'sslverify' => false,
        ]);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            // echo "Something went wrong: $error_message";
            return false;
        }
        $this->user = json_decode($response['body']);
        return true;
    }

    public function get_user_info($token = null)
    {
        if (!$this->user) {
            $this->set_user_info($token);
        }
        return $this->user;
    }

    private function set_user_files($token = null)
    {
        if (!$this->access_token) {
            if (!$this->set_user_token($token)) {
                return false;
            }
        }
        $server_url = $this->base_api_url . 'files?paginate=false&visibility=public&user=' . $this->user->id;
        $response = wp_remote_get($server_url, [
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => ['Authorization' => 'Bearer ' . $this->access_token,
                'Accept' => 'application/json'],
            'sslverify' => false,
        ]);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            // echo "Something went wrong: $error_message";
            return false;
        }

        $this->files = json_decode($response['body']);
        return true;
    }

    private function set_user_notifications($token = null)
    {
        if (!$this->access_token) {
            if (!$this->set_user_token($token)) {
                return false;
            }
        }
        $server_url = $this->base_api_url . 'users/' . $this->user->id . '/notifications';
        $response = wp_remote_get($server_url, [
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => ['Authorization' => 'Bearer ' . $this->access_token,
                'Accept' => 'application/json'],
            'sslverify' => false,
        ]);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            // echo "Something went wrong: $error_message";
            return false;
        }

        $this->notifications = json_decode($response['body']);
        return true;
    }

    public function get_user_files()
    {
        if (!$this->files) {
            $this->set_user_files();
        }
        return $this->files;
    }

    public function get_user_notifications()
    {
        if (!$this->notifications) {
            $this->set_user_notifications();
        }
        return $this->notifications;
    }
}
