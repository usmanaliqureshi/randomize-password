<?php
/**
 * Plugin Name: Randomize Password
 * Description: A good plugin to schedule your new password after a set of days.
 * Plugin URI: https://github.com/usmanaliqureshi/randomize-password
 * Author: Usman Ali Qureshi
 * Author URI: https://www.usmanaliqureshi.com
 * Contributors: usmanaliqureshi
 * Version: 1.0.0
 * License: GPL2
 */

/*
 *
 * Intruders aren't allowed.
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
 *
 * Plugin Core Class
 *
 */

if (!class_exists(Random_Password)) {

    class Random_Password
    {

        /**
         * Holds the values to be used throughout the plugin
         */
        private $options;

        public function __construct()
        {

            $this->rp_initialize();

        }


        /*
         *
         * Initialization - Method
         *
         */

        public function rp_initialize()
        {

            /*
             * Hooks
             */

            register_activation_hook(__FILE__, array($this, 'rp_activation'));
            register_deactivation_hook(__FILE__, array($this, 'rp_deactivation'));

            /*
             * Actions
             */

            add_action('rp_add_schedule', array($this, 'rp_schedule_password'));

            /*
             * Showing Relevant Option on User Settings page.
             */

            add_action('show_user_profile', array($this, 'rp_user_settings'));
            add_action('edit_user_profile', array($this, 'rp_user_settings'));

            /*
             * Saving the new option we just created.
             */

            add_action('personal_options_update', array($this, 'rp_save_user_settings'));
            add_action('edit_user_profile_update', array($this, 'rp_save_user_settings'));

            /*
             *
             * Adding the page into the Settings' Menu
             *
             */

            if (is_admin()) {
                add_action('admin_menu', array($this, 'rp_plugin_page'));
                add_action('admin_init', array($this, 'rp_settings_page_init'));
            }

        }

        /*
         *
         * Plugin Activation - This method will be executed when the plugin will be activated.
         *
         */

        public function rp_activation()
        {

            if (!wp_next_scheduled('rp_schedule_password')) {

                wp_schedule_event(time(), 'hourly', 'rp_add_schedule');

            }

        }

        /*
         *
         * Plugin Deactivation - This method will be executed when the plugin will be deactivated.
         *
         */

        public function rp_deactivation()
        {

            wp_clear_scheduled_hook('rp_add_schedule');

        }

        /*
         *
         * Scheduling Method
         *
         */

        public function rp_schedule_password()
        {

            $this->rp_notify_users();

        }

        /*
         *
         * Randomize Password User Settings - This method will add Randomize Password's user related setting on the user profile page.
         *
         */
        public function rp_user_settings($user)
        {

            if (current_user_can('edit_user', $user->ID)) {

                ?>

                <h3>Randomize Password</h3>

                <table class="form-table">

                    <tr>
                        <th>
                            <label for="randomize_password">Randomize</label>
                        </th>

                        <td>
                            <?php $checked = get_the_author_meta('randomize_password', $user->ID); ?>
                            <input type="checkbox" name="randomize_password" id="randomize_password"
                                   class="regular-text" <?php echo ('on' === $checked) ? esc_attr('checked') : false; ?> />
                            <span class="description">Check to Activate the randomize password option.</span>
                        </td>

                    </tr>

                </table>

                <?php

            }

        }

        /*
         *
         * Saving Randomize Password User Settings - This method will save the settings according to user's selection.
         *
         */

        public function rp_save_user_settings($user_id)
        {

            if (!current_user_can('edit_user', $user_id)) {

                return false;

            }

            if (isset($_POST['randomize_password'])) {

                $rp_post_option = $_POST['randomize_password'];

                update_user_meta($user_id, 'randomize_password', $rp_post_option);

            } else {

                update_user_meta($user_id, 'randomize_password', false);

            }

        }

        /*
         *
         * Password Generator - This method will generate a strong but random password.
         *
         */

        public function rp_generate_password($length = 10)
        {

            $characters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";

            $unique_password = substr(str_shuffle($characters), 0, $length);

            return $unique_password;

        }

        /*
         *
         * Notification to the User with new generated password - Method
         *
         */

        public function rp_notify_users()
        {

            $random_generated_password = $this->rp_generate_password(10);

            $users = get_users();

            foreach ($users as $user) {

                $rp_settings = get_user_meta($user->ID, 'randomize_password', true);

                if ($rp_settings === 'on') {

                    $this->rp_change_password($random_generated_password, $user->ID);

                    wp_mail($user->user_email, 'Randomize Password', $random_generated_password);

                }

            }

        }

        /*
         *
         * Update the user's password in WordPress - Method
         *
         */

        public function rp_change_password($password, $userid)
        {

            wp_set_password($password, $userid);

        }

        /*
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

        /*
         *
         * Render the form with the options - Method
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

        /*
         *
         * Register and add settings for Randomize Password options - Method
         *
         */

        public function rp_settings_page_init()
        {

            register_setting(

                'rp_option_group',

                'rp_options'

            );

            add_settings_section(

                'settings_randomize_password',

                'Randomize Password Settings',

                array($this, 'rp_section_information'),

                'rp-setting-admin'

            );

            add_settings_field(

                'time_interval',

                'Select Time Interval',

                array($this, 'select_time_interval'),

                'rp-setting-admin',

                'settings_randomize_password'

            );

            add_settings_field(

                'length_password',

                'Password Length',

                array($this, 'select_password_length'),

                'rp-setting-admin',

                'settings_randomize_password'

            );
        }

        /*
         *
         * Print the Section text
         *
         */

        public function rp_section_information()
        {

            print '<h4>Select your desired settings</h4>';

        }

        /*
         *
         * Get the settings option array and display the drop-down options accordingly
         *
         */

        public function select_time_interval()
        {

            $this->options = get_option('rp_options');

            ?>

            <select id="time_interval" name="rp_options[time_interval]">

                <option value="hourly" <?php echo isset($this->options['time_interval']) ? (selected($this->options['time_interval'], 'hourly', false)) : (''); ?>>

                    <?php echo 'Hourly'; ?>

                </option>

                <option value="daily" <?php echo isset($this->options['time_interval']) ? (selected($this->options['time_interval'], 'daily', false)) : (''); ?>>

                    <?php echo 'Daily'; ?>

                </option>

                <option value="weekly" <?php echo isset($this->options['time_interval']) ? (selected($this->options['time_interval'], 'weekly', false)) : (''); ?>>

                    <?php echo 'Weekly'; ?>

                </option>

                <option value="monthly" <?php echo isset($this->options['time_interval']) ? (selected($this->options['time_interval'], 'monthly', false)) : (''); ?>>

                    <?php echo 'Monthly'; ?>

                </option>

            </select>

            <p class="rp-description">Choose how often you want to reset your password. If you choose<strong>Weekly</strong> then your password will be reset and sent after every 7 days.</p>

            <?php

        }

        /*
         *
         * Get the settings option array and display the drop-down options accordingly
         *
         */

        public function select_password_length()
        {

            $this->options = get_option('rp_options');

            ?>

            <select id="length_password" name="rp_options[length_password]">

                <option value="8" <?php echo isset($this->options['length_password']) ? (selected($this->options['length_password'], '8', false)) : (''); ?>>

                    <?php echo '8 Characters'; ?>

                </option>

                <option value="10" <?php echo isset($this->options['length_password']) ? (selected($this->options['length_password'], '10', false)) : (''); ?>>

                    <?php echo '10 Characters'; ?>

                </option>

                <option value="12" <?php echo isset($this->options['length_password']) ? (selected($this->options['length_password'], '12', false)) : (''); ?>>

                    <?php echo '12 Characters'; ?>

                </option>

            </select>

            <p class="rp-description">Select the length of your password. For Example, if you choose <strong>10 Characters</strong> then your password will be 10 characters long.</p>

            <?php

        }

    }

}

$rp = new Random_Password();

?>