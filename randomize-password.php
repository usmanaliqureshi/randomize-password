<?php
/**
 * Plugin Name: Randomize Password
 * Description: This plugin will help you to change the password for users depending on the schedule in settings (only set by administrators) of the plugin. It will add a new option on the profile page of each user to activate the schedule to randomly change their password.
 * Plugin URI: https://github.com/usmanaliqureshi/randomize-password
 * Author: Usman Ali Qureshi
 * Author URI: https://www.usmanaliqureshi.com
 * Contributors: usmanaliqureshi
 * Version: 1.0.0
 * License: GPL2
 * Text Domain: rp
 * Domain Path: /languages/
 */

/**
 *
 * Intruders aren't allowed.
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 *
 * Plugin Core Class
 *
 * @Class
 *
 */

if (!class_exists("Randomize_Password")) {

    class Randomize_Password
    {

        /**
         *
         * Holds the values to be used throughout the plugin
         *
         * @Property
         *
         */

        private $options;

        /**
         *
         * You know what this method is don't you :o ?
         *
         * @Method
         *
         */

        public function __construct()
        {

            $this->rp_initialize();

        }


        /**
         *
         * Initialization
         *
         * @Method
         *
         */

        public function rp_initialize()
        {

            /**
             * Hooks
             */

            register_activation_hook(__FILE__, array($this, 'rp_activation'));
            register_deactivation_hook(__FILE__, array($this, 'rp_deactivation'));

            /**
             * Filters
             */

            add_filter('cron_schedules', array($this, 'rp_add_custom_intervals'));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'rp_add_settings_link'));

            /**
             * Actions
             */

            add_action('rp_wp_schedule', array($this, 'rp_schedule_password'));

            /**
             * Showing Relevant Option on User Settings page.
             */

            add_action('show_user_profile', array($this, 'rp_user_settings'));
            add_action('edit_user_profile', array($this, 'rp_user_settings'));

            /**
             * Saving the new option we just created.
             */

            add_action('personal_options_update', array($this, 'rp_save_user_settings'));
            add_action('edit_user_profile_update', array($this, 'rp_save_user_settings'));

            /**
             *
             * Adding the page into the Settings' Menu
             *
             */

            if (is_admin()) {
                add_action('admin_menu', array($this, 'rp_plugin_page'));
                add_action('admin_init', array($this, 'rp_settings_page_init'));
                add_action('update_option_rp_options', array($this, 'rp_update_schedule'), 10, 2);
            }

        }

        /**
         *
         * Plugin Activation - This method will be executed when the plugin will be activated.
         *
         * @Method
         *
         */

        public function rp_activation()
        {

            $this->options = get_option('rp_options');

            $time_interval = ($this->options['time_interval'] ? $this->options['time_interval'] : 'weekly');

            $password_length = ($this->options['length_password'] ? $this->options['length_password'] : '8');

            if (!wp_next_scheduled('rp_schedule_password')) {

                wp_schedule_event(time(), $time_interval, 'rp_wp_schedule');

            }

            if (!isset($this->options['time_interval'])) {

                $defaults = array(

                    'time_interval' => $time_interval,

                    'length_password' => $password_length

                );

                update_option('rp_options', $defaults);

            }

        }

        /**
         *
         * Plugin Deactivation - This method will be executed when the plugin will be deactivated.
         *
         * @Method
         *
         */

        public function rp_deactivation()
        {

            wp_clear_scheduled_hook('rp_wp_schedule');

        }

        /**
         *
         * Adding custom time intervals - This method will add new time intervals like weekly and monthly which
         * are not present in WordPress cron intervals by default.
         *
         * @Method
         *
         */

        public function rp_add_custom_intervals($schedules)
        {

            $schedules['weekly'] = array(

                'interval' => 604800,

                'display' => __('Once Weekly', 'rp')

            );

            $schedules['fortnightly'] = array(

                'interval' => 1209600,

                'display' => __('Once Fortnightly', 'rp')

            );

            $schedules['monthly'] = array(

                'interval' => 2592000,

                'display' => __('Once a month', 'rp')

            );

            $schedules['quaterly'] = array(

                'interval' => 7776000,

                'display' => __('Once Quaterly', 'rp')

            );

            return $schedules;

        }

        /**
         *
         * Scheduling - This method will be added to the cron schedule hook
         *
         * @Method
         *
         */

        public function rp_schedule_password()
        {

            $this->rp_execute();

        }

        /**
         *
         * Randomize Password User Settings - This method will add Randomize Password's user related setting on the user profile page.
         *
         * @Method
         *
         */

        public function rp_user_settings($user)
        {

            if (current_user_can('edit_user', $user->ID)) {

                ?>

                <h3><?php esc_html_e('Randomize Password', 'rp'); ?></h3>

                <table class="form-table">

                    <tr>
                        <th>
                            <label for="randomize_password"><?php esc_html_e('Randomize', 'rp'); ?></label>
                        </th>

                        <td>
                            <?php $checked = get_the_author_meta('randomize_password', $user->ID); ?>
                            <input type="checkbox" name="randomize_password" id="randomize_password"
                                   class="regular-text" <?php echo ('on' === $checked) ? esc_attr('checked') : false; ?> />
                            <span class="description"><?php esc_html_e('&larr; Click to Activate the randomize password option', 'rp') ?>.</span>
                        </td>

                    </tr>

                </table>

                <?php

            }

        }

        /**
         *
         * Saving Randomize Password User Settings - This method will save the settings according to user's selection.
         *
         * @Method
         *
         */

        public function rp_save_user_settings($user_id)
        {

            if (!current_user_can('edit_user', $user_id)) {

                return false;

            }

            if (isset($_POST['randomize_password'])) {

                $rp_valid_value = "on";

                $rp_user_value = sanitize_text_field($_POST['randomize_password']);

                if ($rp_user_value != $rp_valid_value) {

                    wp_die('Invalid selection, please go back and try again.');

                } else {

                    update_user_meta($user_id, 'randomize_password', $rp_user_value);

                }

            } else {

                update_user_meta($user_id, 'randomize_password', false);

            }

        }

        /**
         *
         * Notification to the User with new generated password
         *
         * @Method
         *
         */

        public function rp_execute()
        {

            $this->options = get_option('rp_options');

            $users = get_users();

            $password_length = intval($this->options['length_password']);

            foreach ($users as $user) {

                $random_generated_password = wp_generate_password($password_length, true, true);

                $email = $user->user_email;

                $email_subject = "Randomize Pass";

                $email_body = "Hi " . $user->display_name . ",<br/><br/>";

                $email_body .= "Your password on <a href='" . get_bloginfo('url') . "' target='_blank'>" . get_bloginfo('name') . "</a> has been replaced by following randomly generated password <i><strong>" . $random_generated_password . "</strong></i><br/><br/>";

                $email_body .= "If needed, you can get in touch with the website administrator for more information.";

                $headers = array("Content-Type: text/html; charset=UTF-8");

                $rp_settings = get_user_meta($user->ID, 'randomize_password', true);

                if ($rp_settings === 'on') {

                    $this->rp_change_password($random_generated_password, $user->ID);

                    wp_mail($email, $email_subject, $email_body, $headers);

                }

            }

        }

        /**
         *
         * Update the user's password in WordPress
         *
         * @Method
         *
         */

        public function rp_change_password($password, $userid)
        {

            wp_set_password($password, $userid);

        }

        /**
         *
         * Adding an options page - Method
         *
         */

        public function rp_plugin_page()
        {

            add_options_page(

                'Randomize Password',

                'Randomize Password',

                'manage_options',

                'randomize_password_settings',

                array($this, 'rp_settings_page')

            );

        }

        /**
         *
         * Render the form with the options
         *
         * @Method
         *
         */

        public function rp_settings_page()
        {

            ?>

            <div class="wrap">

                <form id="rp_form" class="rp_form" method="post" action="options.php">

                    <?php

                    settings_fields('rp_option_group');

                    do_settings_sections('rp-setting-admin');

                    submit_button();

                    ?>

                </form>

            </div>

            <?php

        }

        /**
         *
         * Register and add settings for Randomize Password options
         *
         * @Method
         *
         */

        public function rp_settings_page_init()
        {

            register_setting(

                'rp_option_group',

                'rp_options',

                array($this, 'rp_sanitize_and_validate')

            );

            add_settings_section(

                'settings_randomize_password',

                __('Randomize Password Settings', 'rp'),

                array($this, 'rp_section_information'),

                'rp-setting-admin'

            );

            add_settings_field(

                'time_interval',

                __('Select Time Interval', 'rp'),

                array($this, 'select_time_interval'),

                'rp-setting-admin',

                'settings_randomize_password'

            );

            add_settings_field(

                'length_password',

                __('Password Length', 'rp'),

                array($this, 'select_password_length'),

                'rp-setting-admin',

                'settings_randomize_password'

            );
        }

        /**
         *
         * Display section information - Method
         *
         */

        public function rp_section_information()
        {

            ?>

            <h4><?php esc_html_e('Select your desired settings', 'rp'); ?></h4>

            <?php

        }

        /**
         *
         * Get the settings option array and display the drop-down options accordingly
         *
         * @Method
         *
         */

        public function select_time_interval()
        {

            $this->options = get_option('rp_options');

            ?>

            <select id="time_interval" name="rp_options[time_interval]">

                <option
                    value="hourly" <?php echo isset($this->options['time_interval']) ? (selected($this->options['time_interval'], 'hourly', false)) : (''); ?>>

                    <?php esc_html_e('Hourly', 'rp'); ?>

                </option>

                <option
                    value="daily" <?php echo isset($this->options['time_interval']) ? (selected($this->options['time_interval'], 'daily', false)) : (''); ?>>

                    <?php esc_html_e('Daily', 'rp'); ?>

                </option>

                <option
                    value="weekly" <?php echo isset($this->options['time_interval']) ? (selected($this->options['time_interval'], 'weekly', false)) : (''); ?>>

                    <?php esc_html_e('Weekly', 'rp'); ?>

                </option>

                <option
                    value="fortnightly" <?php echo isset($this->options['time_interval']) ? (selected($this->options['time_interval'], 'fortnightly', false)) : (''); ?>>

                    <?php esc_html_e('Fortnightly', 'rp'); ?>

                </option>

                <option
                    value="monthly" <?php echo isset($this->options['time_interval']) ? (selected($this->options['time_interval'], 'monthly', false)) : (''); ?>>

                    <?php esc_html_e('Monthly', 'rp'); ?>

                </option>

                <option
                    value="quaterly" <?php echo isset($this->options['time_interval']) ? (selected($this->options['time_interval'], 'quaterly', false)) : (''); ?>>

                    <?php esc_html_e('Quaterly', 'rp'); ?>

                </option>

            </select>

            <p class="rp-description"><?php esc_html_e('Choose how often you want to reset your password. If you
                choose <strong>Weekly</strong> then your password will be reset and sent after every 7 days.', 'rp'); ?></p>

            <?php

        }

        /**
         *
         * Get the settings option array and display the drop-down options accordingly
         *
         * @Method
         *
         */

        public function select_password_length()
        {

            $this->options = get_option('rp_options');

            ?>

            <select id="length_password" name="rp_options[length_password]">

                <option
                    value="8" <?php echo isset($this->options['length_password']) ? (selected($this->options['length_password'], 8, false)) : (''); ?>>

                    <?php esc_html_e('8 Characters', 'rp'); ?>

                </option>

                <option
                    value="10" <?php echo isset($this->options['length_password']) ? (selected($this->options['length_password'], 10, false)) : (''); ?>>

                    <?php esc_html_e('10 Characters', 'rp'); ?>

                </option>

                <option
                    value="12" <?php echo isset($this->options['length_password']) ? (selected($this->options['length_password'], 12, false)) : (''); ?>>

                    <?php esc_html_e('12 Characters', 'rp'); ?>

                </option>

            </select>

            <p class="rp-description"><?php esc_html_e('Select the length of your password. For Example, if you choose <strong>10
                    Characters</strong> then your password will be 10 characters long.', 'rp'); ?></p>

            <?php

        }

        /**
         *
         * Sanitizing and validating the options selected/submitted by the user
         *
         * @Method
         *
         */

        public function rp_sanitize_and_validate($rp_input) {

            $rp_new_input = array();

            if (isset($rp_input['time_interval'])) {

                $rp_time_valid_values = array(

                    'hourly',

                    'daily',

                    'weekly',

                    'fortnightly',

                    'monthly',

                    'quaterly'

                );

                if (in_array($rp_input['time_interval'], $rp_time_valid_values)) {

                    $rp_new_input['time_interval'] = sanitize_text_field($rp_input['time_interval']);

                } else {

                    wp_die("Invalid selection for Time Interval, please go back and try again.");

                }

            }

            if (isset($rp_input['length_password'])) {

                $rp_password_valid_length = array(

                    8,

                    10,

                    12

                );

                $rp_intval_password = intval($rp_input['length_password']);

                if (in_array($rp_intval_password, $rp_password_valid_length)) {

                    $rp_new_input['length_password'] = sanitize_text_field($rp_input['length_password']);

                } else {

                    wp_die('Invalid selection for Password Length, kindly go back and try again.');

                }

                return $rp_new_input;

            }

        }

        /**
         *
         * Updating the schedule according to the new interval selected by the user
         *
         * @Method
         *
         */

        public function rp_update_schedule($old_value, $new_value)
        {

            $existing_interval = $old_value['time_interval'];

            $updated_interval = $new_value['time_interval'];

            if ($existing_interval != $updated_interval) {

                wp_clear_scheduled_hook('rp_wp_schedule');

                wp_schedule_event(time(), $updated_interval, 'rp_wp_schedule');

            }

        }

        /**
         *
         * Adding Settings link to the plugin page.
         *
         * @Method
         *
         */

        public function rp_add_settings_link($links)
        {

            $rp_settings_link = array(

                '<a href="' . admin_url('options-general.php?page=randomize_password_settings') . '">Settings</a>',

            );

            return array_merge($links, $rp_settings_link);

        }

    }

}

$rp = new Randomize_Password();

?>