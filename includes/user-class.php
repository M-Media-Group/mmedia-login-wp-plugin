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
    private $wp_session;

    public function __construct()
    {
        $options = get_option('wposso_options');
        $this->base_api_url = str_replace('/oauth/', '/api/', $options['server_url']);
        if (!session_id()) {
            session_start();
        }
        $this->wp_session = $_SESSION;
    }

    public static function is_logged_in_as_mmedia()
    {
        if (!isset($_COOKIE["m_media_access_token"])) {
            return false;
        }
        return true;
    }

    public function set_user_token($token = null)
    {
        if (!$token) {
            if (!self::is_logged_in_as_mmedia()) {
                return false;
            }
            $token = $_COOKIE["m_media_access_token"];
        }
        $this->access_token = $token;
        $this->set_user_info();
        return true;
    }

    private function set_user_info($token = null)
    {
        if (!$this->access_token) {
            $this->set_user_token($token);
        }
        if (isset($_SESSION['m_media_user'])) {
            $this->user = json_decode($_SESSION['m_media_user']);
        } else {
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

            $this->handle_response($response);
            if ($response['response']['code'] !== 200) {
                return false;
            }
            $this->user = json_decode($response['body']);
            $_SESSION['m_media_user'] = $response['body'];
            // setcookie('m_media_user', json_encode($response['body']), 0, '/', '', false, true); // expire with session
        }
        return true;
    }

    public function revoke_token()
    {
        if (!isset($_COOKIE["m_media_access_token"])) {
            return false;
        }

        $server_url = str_replace('/api/', '/oauth/tokens/' . $_COOKIE["m_media_access_token"], $this->base_api_url);

        setcookie('m_media_access_token', '', time() - 172800, '/'); // UNSET the access token cookie
        $_SESSION['m_media_user'] = null;

        // $response = wp_remote_request($server_url, [
        //     'method' => 'DELETE',
        //     'sslverify' => false,
        // ]);

        // if (is_wp_error($response)) {
        //     $error_message = $response->get_error_message();
        //     wp_die(var_dump($error_message));
        //     return false;
        // } elseif ($response['response']['code'] !== 200) {
        //     return false;
        // }
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

        $this->handle_response($response);

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

        $this->handle_response($response);

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
    public function get_user_file($file_id)
    {
        $files = $this->get_user_files();
        $i = array_search($file_id, array_column($files, 'id'));
        $element = ($i !== false ? $files[$i] : null);
        return $element;
    }

    public function get_user_notifications()
    {
        if (!$this->notifications) {
            $this->set_user_notifications();
        }
        return $this->notifications;
    }

    private function handle_response($response)
    {
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            // echo "Something went wrong: $error_message";
            return false;
        }

        $code = $response['response']['code'];

        if ($code === 401) {
            wp_logout();
            $redirect_url = site_url();
            wp_safe_redirect($redirect_url);
            exit;
        } elseif ($code == 200) {
            return true;
        } elseif ($code == 201) {
            return true;
        }
        return false;
    }
}

$m_media_user = new M_WPOSSO_User();
