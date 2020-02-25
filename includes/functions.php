<?php

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Auto SSO for users that are not logged in.
 */
add_filter('template_redirect', 'auto_sso_init', 11);
function auto_sso_init($template)
{

    // If the user is not logged in, load in the SSO client in a child IFrame instead of redirect
    if (!is_user_logged_in()) {
        $options = get_option('wposso_options');
        if (isset($options['auto_sso']) && $options['auto_sso'] == 1) {

            /*
             * @todo Build options instead of just a last query. This will be able to handle the pass of the iframe
             */
            global $wp;
            $last_page = home_url($wp->request);

            $params = [
                //'oauth'         => 'authorize',
                'response_type' => 'code',
                'client_id'     => $options['client_id'],
                'client_secret' => $options['client_secret'],
                'redirect_uri'  => site_url('?auth=sso'),
                'state'         => urlencode($last_page),
            ];
            $redirect = add_query_arg($params, 'https://mmediagroup.fr/oauth/');

            wp_redirect($redirect);
            exit;
        }
    }
}

/**
 * Main Functions.
 *
 * @author Justin Greer <justin@justin-greer.com>
 */

/**
 * Function wp_sso_login_form_button.
 *
 * Add login button for SSO on the login form.
 *
 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/login_form
 */
function wp_sso_login_form_button()
{
    ?>
    <a style="color:#FFF; width:100%; background-color: #eb4647; border-color:#eb4647; text-align:center;" class="button button-primary button-large"
       href="<?php echo site_url('?auth=sso'); ?>">Log in with M Media</a>
    <div style="clear:both;"></div>
	<?php
}

add_action('login_message', 'wp_sso_login_form_button');

/**
 * Login Button Shortcode.
 *
 * @param [type] $atts [description]
 *
 * @return [type] [description]
 */
function single_sign_on_login_button_shortcode($atts)
{
    $a = shortcode_atts([
        'type'   => 'primary',
        'title'  => 'Login with M Media',
        'class'  => 'sso-button',
        'target' => '_blank',
        'text'   => 'Login with M Media',
    ], $atts);

    return '<a class="'.$a['class'].'" href="'.site_url('?auth=sso').'" title="'.$a['title'].'" target="'.$a['target'].'">'.$a['text'].'</a>';
}

add_shortcode('sso_button', 'single_sign_on_login_button_shortcode');

/**
 * Get user login redirect. Just in case the user wants to redirect the user to a new url.
 */
function wpssoc_get_user_redirect_url()
{
    $options = get_option('wposso_options');
    $user_redirect_set = $options['redirect_to_dashboard'] == '1' ? get_dashboard_url() : site_url();
    $user_redirect = apply_filters('wpssoc_user_redirect_url', $user_redirect_set);

    return $user_redirect;
}
