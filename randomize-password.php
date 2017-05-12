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
 * Intruders are not allowed.
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

            register_activation_hook(__FILE__, array($this, 'rp_activation'));

            register_deactivation_hook(__FILE__, array($this, 'rp_deactivation'));

            add_action('rp_add_schedule', array($this, 'rp_schedule_password'));

        }

        /*
         *
         * Plugin Activation - Method
         *
         */

        public function rp_activation()
        {

            if (!wp_next_scheduled('rp_schedule_password')) {

                wp_schedule_event(time(), 'daily', 'rp_add_schedule');

            }

        }

        /*
         *
         * Plugin Deactivation - Method
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


        }

        /*
         *
         * Strong Random Password Generator - Method
         *
         */

        public function rp_generate_password()
        {


        }

        /*
         *
         * Notification to the User with new generated password - Method
         *
         */

        public function rp_notify_users()
        {


        }

        /*
         *
         * Update the user's password in WordPress - Method
         *
         */

        public function rp_change_passwords()
        {


        }

    }

}

$rp = new Random_Password();
$rp;

?>