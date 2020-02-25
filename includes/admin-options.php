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

    public static function init()
    {
        add_action('admin_init', [new self(), 'admin_init']);
        add_action('admin_menu', [new self(), 'add_page'], 11);
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
//         add_options_page( 'Log in with M Media', 'Log in with M Media', 'manage_options', 'wposso_settings', array(
        //             $this,
        //             'options_do_page'
        //         ) );
        add_submenu_page('mmedia_main_menu', 'Log in with M Media',
            'Log in with M Media', 'manage_options', 'm_media_login', [
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
        $options = get_option($this->option_name); ?>

        <div class="wrap">
			<div class="align-center-mmedia" style="text-align: center;padding-top:15px;">
        <img src="<?php echo plugins_url('mmedia/images/m.svg'); ?>" height="75">
        <p style="font-weight: 500;">We make websites and handle your marketing.</p>
    </div>
    <div class="card">
        <h3>Step 1</h3>
        <p>Obtain a Client ID and Secret from M Media.</p>
        <p>Your redirect URL is <strong><?php echo site_url('?auth=sso'); ?></strong></p>
    </div>
    <div class="card">
        <h3>Step 2</h3>
        <p>Input your Client ID and Secret.</p>    <form method="post" action="options.php">
                        <?php settings_fields('wposso_options'); ?>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">Client ID</th>
                                <td>
                                    <input type="text" name="<?php echo $this->option_name ?>[client_id]" min="10"
                                           value="<?php echo $options['client_id']; ?>"/>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">Client Secret</th>
                                <td>
                                    <input type="text" name="<?php echo $this->option_name ?>[client_secret]" min="10"
                                           value="<?php echo $options['client_secret']; ?>"/>
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
                            <input type="submit" class="button button-mmedia" value="<?php _e('Save Changes')?>"/>
                        </p>

                </form>
    </div>
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
        $input['redirect_to_dashboard'] = isset($input['redirect_to_dashboard']) ? $input['redirect_to_dashboard'] : 0;

        return $input;
    }
}

WPOSSO_Admin::init();
