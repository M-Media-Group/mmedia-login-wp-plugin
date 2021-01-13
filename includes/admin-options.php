<?php

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class WPOSSO_Admin.
 *
 * @author Justin Greer <justin@justin-greer.com
 */
class WPOSSO_Admin
{
    protected $option_name = 'wposso_options';
    private $m_media_user;

    public function __construct(M_WPOSSO_User $m_media_user)
    {
        $this->m_media_user = $m_media_user;
    }

    /**
     * [admin_init description].
     *
     * @return [type] [description]
     */
    public function admin_init()
    {
        register_setting('wposso_options', $this->option_name, [$this, 'validate']);
    }

    /**
     * [add_page description].
     */
    public function add_page()
    {

        add_submenu_page('m_media_main_menu', 'Notifications',
            $this->m_media_user->get_user_unread_notification_count() ? sprintf('Notifications <span class="awaiting-mod">%d</span>', $this->m_media_user->get_user_unread_notification_count()) : 'Notifications',
            'publish_pages', 'm_media_notifications', [
                $this,
                'options_do_notifications_page',
            ]);

        add_submenu_page('m_media_main_menu', 'Log in settings',
            'Log in settings', 'manage_options', 'm_media_login', [
                $this,
                'options_do_page',
            ]);
    }

    /**
     * [options_do_page description].
     *
     * @return [type] [description]
     */
    public function options_do_page()
    {

        $options = get_option($this->option_name);
        // $m_media_user = new M_WPOSSO_User();

        ?>

        <div class="wrap">
		    <div class="align-center-mmedia" style="text-align: center;padding-top:15px;">
				<img src="<?php echo plugins_url('mmedia/images/m.svg'); ?>" height="75">
				<p style="font-weight: 500;">We make websites and handle your marketing.</p>
		    </div>
		    <?php if (!$this->m_media_user->is_logged_in_as_mmedia()) {?>
		    <div class="card">
			<h3>Step 1</h3>
			<p>Obtain a Client ID and Secret from M Media.</p>
			<p>Your redirect URL is <strong><?php echo site_url('?auth=sso'); ?></strong></p>
		    </div>
		    <div class="card">
			<h3>Step 2</h3>
			<p>Input your Client ID and Secret.</p>
			<form method="post" action="options.php">
			    <?php settings_fields('wposso_options');?>
				<table class="form-table">
				    <tr valign="top">
					<th scope="row">Client ID</th>
					<td>
					    <input type="text" name="<?php echo $this->option_name ?>[client_id]" min="10" value="<?php echo $options['client_id']; ?>" />
					</td>
				    </tr>

				    <tr valign="top">
					<th scope="row">Client Secret</th>
					<td>
					    <input type="text" name="<?php echo $this->option_name ?>[client_secret]" min="10" value="<?php echo $options['client_secret']; ?>" />
					</td>
				    </tr>
					<tr valign="top">
						<th scope="row">Allow new M Media users to register on this site</th>
						<td>
						    <input type="checkbox"
							   name="<?php echo $this->option_name ?>[allow_registration]"
							   value="1" <?php echo isset($options['allow_registration']) ? 'checked="checked"' : ''; ?> />
						</td>
					    </tr>

				    <!--  <tr valign="top">
						<th scope="row">Redirect to the dashboard after signing in</th>
						<td>
						    <input type="checkbox"
							   name="<?php echo $this->option_name ?>[redirect_to_dashboard]"
							   value="1" <?php echo $options['redirect_to_dashboard'] == 1 ? 'checked="checked"' : ''; ?> />
						</td>
					    </tr> -->
				</table>

				<p class="submit">
				    <input type="submit" class="button button-mmedia" value="<?php _e('Save Changes')?>" />
				</p>

			</form>
		    </div>
		    <div class="card">
			<h3>Need help?</h3>
			<p>Read the M Media plugin guide on our Help Center.</p>
			<a class="button" href="https://blog.mmediagroup.fr/post/log-in-with-m-media-wordpress-plugin/?utm_source=wordpress&utm_medium=plugin&utm_campaign=<?php echo get_site_url(); ?>&utm_content=tab_login_with_mmedia">Read the plugin guide</a>
		    </div>
		<?php } else {?>
			<div class="card">
			<h3>Everything is connected!</h3>
			<p>You're all done - there's nothing to do here - just remember to keep this plugin activated! If you want, you can read the M Media plugin guide on our Help Center.</p>
			<a class="button" href="https://blog.mmediagroup.fr/post/log-in-with-m-media-wordpress-plugin/?utm_source=wordpress&utm_medium=plugin&utm_campaign=<?php echo get_site_url(); ?>&utm_content=tab_login_with_mmedia">Read the plugin guide</a>
		    </div>
		<?php }?>
	 	</div>
		<?php
}

    /**
     * [options_do_page description].
     *
     * @return [type] [description]
     */
    public function options_do_notifications_page()
    {

        $options = get_option($this->option_name);
        // $m_media_user = new M_WPOSSO_User();

        ?>

        <div class="wrap">
		    <div class="align-center-mmedia" style="text-align: center;padding-top:15px;">
				<img src="<?php echo plugins_url('mmedia/images/m.svg'); ?>" height="75">
				<p style="font-weight: 500;">M Media unread notifications</p>
		    </div>
		    		    <?php if ($this->m_media_user->is_logged_in_as_mmedia()) {
            if ($this->m_media_user->get_user_unread_notification_count()) {
                $notifications = $this->m_media_user->get_user_unread_notifications();
                ?>

				<?php foreach ($notifications as $notification) {
                    ?>
            <div class="card">
            	<h3 style="margin-bottom: 0;"><?php echo $notification->data->title; ?></h3>
                 <small><?php echo human_time_diff(strtotime($notification->created_at)); ?> ago</small>
				 <p style="white-space: pre-wrap;"><?php echo $notification->data->message; ?></p>
		    </div>
		    <?php }echo "<div style='text-align:center;'><a class='button' href='https://mmediagroup.fr/notifications'>Mark notifications as read on M Media</a></div>";
            } else {
                echo "<div class='card'><h3>You've caught up with all your notifications!</h3><a class='button' href='https://mmediagroup.fr/notifications' target='_BLANK'>See past notifications on M Media</a></div>";
            }
        } else {
            echo "<div class='card'>You are not currently logged in via M Media. Log in with M Media to get access to your notifications.</div>";
        }?>
	 	</div>
		<?php
}

    /**
     * Settings Validation.
     *
     * @param [type] $input [description]
     *
     * @return [type] [description]
     */
    public function validate($input)
    {
        $options = get_option($this->option_name);
        $input['redirect_to_dashboard'] = isset($input['redirect_to_dashboard']) ? $input['redirect_to_dashboard'] : 0;
        $input['server_url'] = isset($options['server_url']) ? $options['server_url'] : 'https://mmediagroup.fr/oauth/';

        return $input;
    }

}

$WPOSSO_Admin = new WPOSSO_Admin($m_media_user);

add_action('admin_init', [$WPOSSO_Admin, 'admin_init']);
add_action('admin_menu', [$WPOSSO_Admin, 'add_page'], 11);