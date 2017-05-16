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
        public function rp_user_settings( $user )
        {

            if ( current_user_can( 'edit_user', $user->ID ) ) {

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

        public function rp_save_user_settings( $user_id )
        {

            if ( !current_user_can( 'edit_user', $user_id ) ) {

                return false;

            }

            if ( isset( $_POST['randomize_password'] ) ) {

                $rp_post_option = sanitize_text_field( $_POST[ 'randomize_password' ] );

                update_user_meta( $user_id, 'randomize_password', $rp_post_option );

            } else {

                update_user_meta( $user_id, 'randomize_password', false );

            }

        }

        /*
         *
         * Password Generator - This method will generate a strong but random password.
         *
         */

        public function rp_generate_password( $length = 10 )
        {

            $characters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";

            $unique_password = substr( str_shuffle( $characters ), 0, $length );

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

            foreach($users as $user) {

                $rp_settings = get_user_meta($user->ID, 'randomize_password', true);

                if ( $rp_settings === 'on' ) {

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

    }

}

$rp = new Random_Password();

?>