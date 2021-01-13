<?php

defined('ABSPATH') or die('No script kiddies please!');
class M_Media_login
{
    /**
     * Static property to hold our singleton instance
     *
     */
    public static $instance = false;
    private $m_media_user;
    /**
     * This is our constructor
     *
     * @return void
     */
    private function __construct(M_WPOSSO_User $m_media_user)
    {
        global $pagenow;

        $this->m_media_user = $m_media_user;
        register_activation_hook(__FILE__, array($this, 'm_media_install'));
        register_deactivation_hook(__FILE__, array($this, 'm_media_uninstall'));
        add_filter('template_redirect', array($this, 'auto_sso_init'), 11);
        add_action('login_message', array($this, 'wp_sso_login_form_button'));
        add_shortcode('sso_button', array($this, 'single_sign_on_login_button_shortcode'));
        add_action('wp_logout', array($this, 'session_logout'));
        add_action('wp_ajax_m_media_insert_attachment_from_id', array($this, 'm_media_insert_attachment_from_id'));
        if (($pagenow == 'media-new.php' || $pagenow == 'upload.php' || $pagenow == 'post-new.php' || $pagenow == 'post.php') && $this->m_media_user->is_logged_in_as_mmedia()) {
            add_action('post-upload-ui', array($this, 'm_media_get_files'));
        }
    }

    /**
     * If an instance exists, this returns it.  If not, it creates one and
     * retuns it.
     *
     * @return M_Media
     */

    public static function getInstance(M_WPOSSO_User $m_media_user)
    {
        if (!self::$instance) {
            self::$instance = new self($m_media_user);
        }

        return self::$instance;
    }

    /**
     * Auto SSO for users that are not logged in.
     */
    public function auto_sso_init($template)
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
                    'client_id' => $options['client_id'],
                    'client_secret' => $options['client_secret'],
                    'redirect_uri' => site_url('?auth=sso'),
                    'state' => urlencode($last_page),
                ];
                $redirect = add_query_arg($params, $options['server_url']);

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
     * public function wp_sso_login_form_button.
     *
     * Add login button for SSO on the login form.
     *
     * @link https://codex.wordpress.org/Plugin_API/Action_Reference/login_form
     */
    public function wp_sso_login_form_button()
    {
        ?>
    <a style="color:#FFF; width:100%; background-color: #eb4647; border-color:#eb4647; text-align:center;" class="button button-primary button-large"
       href="<?php echo site_url('?auth=sso'); ?>">Log in with M Media</a>
    <div style="clear:both;"></div>
	<?php
    }

    /**
     * Login Button Shortcode.
     *
     * @param [type] $atts [description]
     *
     * @return [type] [description]
     */
    public function single_sign_on_login_button_shortcode($atts)
    {
        $a = shortcode_atts([
            'type' => 'primary',
            'title' => 'Login with M Media',
            'class' => 'sso-button',
            'target' => '_blank',
            'text' => 'Login with M Media',
        ], $atts);

        return '<a class="' . $a['class'] . '" href="' . site_url('?auth=sso') . '" title="' . $a['title'] . '" target="' . $a['target'] . '">' . $a['text'] . '</a>';
    }

    /**
     * Get user login redirect. Just in case the user wants to redirect the user to a new url.
     */
    public static function wpssoc_get_user_redirect_url()
    {
        $options = get_option('wposso_options');
        $user_redirect_set = $options['redirect_to_dashboard'] == '1' ? get_dashboard_url() : site_url();
        $user_redirect = apply_filters('wpssoc_user_redirect_url', $user_redirect_set);

        return $user_redirect;
    }

    public function session_logout()
    {
        $this->m_media_user->revoke_token();
        // session_destroy();
    }

    /**
     * Add a message box to the media uploader to
     * clearly show when new uploads are protected
     *
     * @since 0.2
     */
    public function m_media_get_files()
    {
        $files = $this->m_media_user->get_user_files();
        $options = get_option('wposso_options'); ?>
    <div>
    <br>
    <p>or</p>
    <h2 class="upload-instructions drop-instructions"><?php _e('Upload a file from your M Media account')?></h2>
    <?php
$i = 0;
        foreach ($files as $file) {
            ?>

<div class="card align-center-mmedia">
    <img src="<?php echo $file->url; ?>" height="145">
    <h3><?php echo $file->name; ?></h3>
    <a style="display:inline-block;" class="button button-upload-from-mmedia" href="<?php echo esc_attr(admin_url('admin-post.php')); ?>?action=m_media_insert_attachment_from_id&file_id=<?php echo $file->id; ?>" data-file-id="<?php echo $file->id; ?>"><?php _e('Upload to your site')?></a>
</div>

  <?php if (++$i == 25) {
                break;
            }
        } ?>
    <a class="button button-mmedia" target="_BLANK" href="<?php echo str_replace('/oauth/', '/files/create', $options['server_url']); ?>"><?php _e('Upload more files to M Media')?></a>
</div>

        <script type="text/javascript" >


        jQuery(document).ready(function($) {
          var switchAndReload = function() {

            // get wp outside iframe

            var wp = parent.wp;

            // switch tabs (required for the code below)

            $('button.media-menu-item#menu-item-browse').click();
            wp.media.frame.setState('insert');

            // refresh

            if( wp.media.frame.content.get() !== null) {
                wp.media.frame.content.get().collection.props.set({ignore: (+ new Date())});
                wp.media.frame.content.get().options.selection.reset();
            } else {
                wp.media.frame.library.props.set ({ignore: (+ new Date())});
            }
        };

    $("a.button-upload-from-mmedia").click(function(e) {
       e.preventDefault();
       $("a.button-upload-from-mmedia, #__wp-uploader-id-1, #html-upload").attr('disabled', true);

        var data = {
            'action': 'm_media_insert_attachment_from_id',
            'file-id': $(this).data('file-id'),
        };

        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        jQuery.post(ajaxurl, data, function(response) {
            alert('File was uploaded!');

            // alert('Got this from the server: ' + response);
            $("a.button-upload-from-mmedia, #__wp-uploader-id-1, #html-upload").attr('disabled', false);
            switchAndReload();
        });
    });
});
    </script>
  <?php
    }

    public function get_attachment_exists_by_guid($guid)
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID FROM $wpdb->posts
        WHERE guid = '$guid'
        AND post_type = 'attachment'"
            )
        );
    }

    /**
     * Insert an attachment from an URL address.
     *
     * @param  String $url
     * @param  Int    $parent_post_id
     * @return Int    Attachment ID
     */
    public function m_media_insert_attachment_from_id($file_id = null)
    {
        if (!$file_id) {
            // $file = new stdClass();
            $file_id = $_POST['file-id'];
        }

        // Make sure that file is a valid M Media file
        if (!$file = $this->m_media_user->get_user_file($file_id)) {
            wp_die('No such file exists');
            return false;
        }
        // get_page_by_title( pathinfo( 'https://www.example.com/file.jpg' )['filename'], "OBJECT", 'attachment' );

        if ($this->get_attachment_exists_by_guid($file->url)) {
            return false;
        }

        // If the goal is to transfer to the actual server, use this commented-out code. For now we are simply lnking to the external file.

        // if (!class_exists('WP_Http')) {
        //     include_once ABSPATH . WPINC . '/class-http.php';
        // }
        // $http = new WP_Http();
        // $response = $http->request($file->url, ['sslverify' => false]);
        // // die(var_dump($response));
        // if ($response['response']['code'] != 200) {
        //     return false;
        // }

        // $upload = wp_upload_bits(basename($file->url), null, $response['body']);
        // if (!empty($upload['error'])) {
        //     return false;
        // }
        // $file_path = $upload['file'];    $wp_upload_dir = wp_upload_dir();

        $file_path = $file->url;
        $file_name = basename($file_path);
        $file_type = wp_check_filetype($file_name, null);
        $attachment_title = sanitize_file_name(pathinfo($file_name, PATHINFO_FILENAME));

        $post_info = array(
            // 'guid' => $wp_upload_dir['url'] . '/' . $file_name,
            'guid' => $file_path,
            'post_mime_type' => $file_type['type'],
            'post_title' => $attachment_title,
            'post_content' => '',
            'post_status' => 'inherit',
        );

        // Create the attachment
        $attach_id = wp_insert_attachment($post_info, $file_path, $parent_post_id);

        // Include image.php
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Define attachment metadata
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        // $attach_data['m_media_file_id'] = $file->id;

        // Assign metadata to attachment
        wp_update_attachment_metadata($attach_id, $attach_data);

        add_post_meta($attach_id, '_m_media_file_id', $file_id, true);

        // wp_set_post_lock($attach_id);

        echo $attach_id;

        wp_die();
    }
}

$m_media_login = M_Media_login::getInstance($m_media_user);
