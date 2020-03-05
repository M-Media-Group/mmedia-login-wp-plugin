<?php
/**
 * File callback.php.
 *
 * @author Justin Greer <justin@justin-greer.com
 */
defined('ABSPATH') or die('No script kiddies please!');

// Redirect the user back to the home page if logged in.
if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

// Grab a copy of the options and set the redirect location.
$options = get_option('wposso_options');
$user_redirect = wpssoc_get_user_redirect_url();

// Check for custom redirect
if (!empty($_GET['redirect_uri'])) {
    $user_redirect = $_GET['redirect_uri'];
}

// Authenticate Check and Redirect
if (!isset($_GET['code'])) {
    $params = [
        //'oauth'         => 'authorize',
        'response_type' => 'code',
        'client_id'     => $options['client_id'],
        //'client_secret' => $options['client_secret'],
        'redirect_uri'  => site_url('?auth=sso'),
        'state'         => $user_redirect,
        'scope'         => '',
        //'allow_registration' => $options['allow_registration'],
    ];
    $params = http_build_query($params);

    wp_redirect('https://mmediagroup.fr/oauth/'.'authorize?'.$params);
    exit;
}

// Handle the callback from the server is there is one.
if (isset($_GET['code']) && !empty($_GET['code'])) {

    // If the state is present, let's redirect to that link.
    if (!empty($_GET['state'])) {
        $user_redirect = $_GET['state'];
    }

    $code = sanitize_text_field($_GET['code']);
    $server_url = 'https://mmediagroup.fr/oauth/'.'token';
    $response = wp_remote_post($server_url, [
        'method'      => 'POST',
        'timeout'     => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking'    => true,
        'headers'     => [],
        'body'        => [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $options['client_id'],
            'client_secret' => $options['client_secret'],
            'redirect_uri'  => site_url('?auth=sso'),
        ],
        'cookies'     => [],
        'sslverify'   => false,
    ]);

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        exit("Something went wrong: $error_message");
    }

    $tokens = json_decode($response['body']);
    if (isset($tokens->error)) {
        wp_die($tokens->error_description);
    }

    $server_url = 'https://mmediagroup.fr/api/user';
    $response = wp_remote_get($server_url, [
        'timeout'     => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking'    => true,
        'headers'     => ['Authorization' => 'Bearer '.$tokens->access_token,
            'Accept'                      => 'application/json', ],
        'sslverify'   => false,
    ]);

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        echo "Something went wrong: $error_message";
    }

    $user_info = json_decode($response['body']);

    //echo '<pre>'.print_r($user_info->email, true).'</pre>';
    //exit();

    //$user_id = username_exists( $user_info->user_login );

    if (!email_exists($user_info->email)) {
        if ($options['allow_registration']) {
            // Does not have an account... Register and then log the user in
            $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
            $user_id         = wp_insert_user([
    'user_pass'             => $random_password,   //(string) The plain-text user password.
    'user_login'            => $user_info->email,   //(string) The user's login username.
    'user_email'            => $user_info->email,   //(string) The user email address.
				'show_admin_bar_front'  => '',   //(string|bool) Whether to display the Admin Bar for the user on the site's front end. Default true.
    'role'                  => 'subscriber',   //(string) User's role.
        ]);

            if (isset($user_info->name)) {
                update_user_meta($user_id, 'first_name', $user_info->name);
            }

            if (isset($user_info->surname)) {
                update_user_meta($user_id, 'last_name', $user_info->surname);
            }

            // Trigger new user created action so that there can be modifications to what happens after the user is created.
            // This can be used to collect other information about the user.
            do_action('wpoc_user_created', $user_info, 1);

            wp_clear_auth_cookie();
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            if (is_user_logged_in()) {
                wp_safe_redirect($user_redirect);
                exit;
            }
        } else {
            wp_die('There is no account on this website associated with your M Media account.');
        }
    } else {

        // Already Registered... Log the User In
        $random_password = __('User already exists.  Password inherited.');
        $user = get_user_by('email', $user_info->email);

        if (isset($user_info->name)) {
            update_user_meta($user->ID, 'first_name', $user_info->name);
        }

        if (isset($user_info->surname)) {
            update_user_meta($user->ID, 'last_name', $user_info->surname);
        }

        // Trigger action when a user is logged in.
        // This will help allow extensions to be used without modifying the core plugin.
        do_action('wpoc_user_login', $user_info, 1);

        // User ID 1 is not allowed
        if ('1' === $user->ID) {
            wp_die('For security reasons, this user can not use Single Sign On');
        }

        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);

        if (is_user_logged_in()) {
            wp_safe_redirect($user_redirect);
            exit;
        }
    }

    exit('Single Sign On Failed. User mismatch or clash with existing data and SSO can not complete.');
}
