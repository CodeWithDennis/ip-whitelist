<?php

/*
Plugin Name: Whitelist
Description: A simple plugin to ip-whitelist IP addresses and block unauthorized visitors
Version: 1.0
Author: Dennis Elsinga
*/

class IPWhitelist
{

    /**
     * IP_Whitelist constructor.
     * Initializes the IPWhitelist object.
     * Adds necessary hooks to the WordPress environment.
     */

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_plugin_menu_item'));
        add_action('login_init', array($this, 'restriction'));
        add_action('template_redirect', array($this, 'restriction'));
        wp_enqueue_style('ip_whitelist_styles', plugin_dir_url(__FILE__) . 'css/styles.css');
    }

    /**
     * Adds a new menu item in the WordPress admin panel.
     *
     * @return void
     */

    public function add_plugin_menu_item(): void
    {

        add_menu_page(
            'Whitelist',
            'Whitelist',
            'manage_options',
            'whitelist',
            array($this, 'plugin_menu_callback'),
            'dashicons-unlock'
        );
    }

    /**
     * Callback function for the plugin menu page.
     * Handles the addition and deletion of IP addresses.
     * Displays the plugin interface.
     *
     * @return void
     */

    public function plugin_menu_callback(): void
    {
        // Handle addition of IP address
        if (isset($_POST['add'])) {
            $ip_address = sanitize_text_field($_POST['ip_address']);
            if (empty($ip_address)) {
                add_settings_error('ip_whitelist', 'empty_ip_address', __('IP address field cannot be empty.', 'ip-whitelist'));
            } elseif (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
                add_settings_error('ip_whitelist', 'invalid_ip_address', __('Invalid IP address format.', 'ip-whitelist'));
            } elseif (in_array($ip_address, $this->get_ip_addresses())) {
                add_settings_error('ip_whitelist', 'duplicate_ip_address', __('IP address is already in the whitelist.', 'ip-whitelist'));
            } else {
                $this->add($ip_address);
                add_settings_error('ip_whitelist', 'ip_address_added', __('IP address added to whitelist.', 'ip-whitelist'), 'updated');
            }
        }

        // Handle deletion of IP address
        if (isset($_POST['delete'])) {
            $ip_address = sanitize_text_field($_POST['delete']);
            if (!empty($ip_address)) $this->delete($ip_address);
        }

        // Display any errors associated with the "ip_whitelist" setting
        settings_errors('ip_whitelist');

        // Display the plugin interface
        $this->plugin_interface();
    }

    /**
     * Include the interface file, which contains the HTML for the IP ip-whitelist page.
     *
     * @return void
     */

    public function plugin_interface(): void
    {
        include_once('interface.php');
    }

    /**
     * Adds a new IP address to the list of IP addresses.
     *
     * @param string $ip_address The IP address to add.
     * @return void
     */

    public function add(string $ip_address): void
    {
        $ip_addresses = $this->get_ip_addresses();
        $ip_addresses[] = $ip_address;
        update_option('whitelisted-addresses', $ip_addresses);
    }

    /**
     * Deletes an IP address from the list of IP addresses.
     *
     * @param string $ip_address The IP address to delete.
     * @return void
     */

    public function delete(string $ip_address): void
    {
        $ip_addresses = $this->get_ip_addresses();
        $index = array_search($ip_address, $ip_addresses);
        if ($index !== false) {
            unset($ip_addresses[$index]);
            update_option('whitelisted-addresses', $ip_addresses);
        }
    }

    /**
     * Retrieves the IP addresses from the options table or an empty array if none are found.
     *
     * @return false|mixed|null An array of IP addresses or null if there is an error retrieving the option.
     */

    public function get_ip_addresses(): mixed
    {
        return get_option('whitelisted-addresses', []);
    }

    /**
     * Checks if the user's IP address is allowed and returns a 401 status code if not.
     *
     * @return void
     */

    public function restriction(): void
    {
        // Get the saved IP addresses from the options table
        $allowed_ips = $this->get_ip_addresses();
        
        // Check if the user's IP is in the allowed IPs array
        $user_ip = $_SERVER['REMOTE_ADDR'];
        if (!in_array($user_ip, $allowed_ips) && !empty($allowed_ips)) {
            status_header(401);
            die();
        }
    }

}

// Create a new instance of the IPWhitelist class to handle IP whitelisting
$new_whitelist = new IPWhitelist();