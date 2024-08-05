<?php
/*
Plugin Name: FOSSBilling Integration
Description: Integrates WordPress with FOSSBilling using RabbitMQ
Version: 1.0
Author: Hamza
*/

require_once(__DIR__ . '/wp-fossbilling-sender.php');
require_once(__DIR__ . '/wp-fossbilling-receiver.php');

// Add menu items
add_action('admin_menu', 'fossbilling_integration_menu');
add_action('admin_init', 'fossbilling_integration_settings_init');

function fossbilling_integration_menu() {
    add_menu_page('FOSSBilling Customers', 'FOSSBilling Customers', 'manage_options', 'fossbilling-customers', 'fossbilling_customers_page');
    add_submenu_page('fossbilling-customers', 'Settings', 'Settings', 'manage_options', 'fossbilling-settings', 'fossbilling_settings_page');
}

function fossbilling_integration_settings_init() {
    register_setting('fossbilling_integration', 'fossbilling_api_key');
    add_settings_section('fossbilling_integration_section', 'FOSSBilling API Settings', null, 'fossbilling-settings');
    add_settings_field('fossbilling_api_key', 'API Key', 'fossbilling_api_key_callback', 'fossbilling-settings', 'fossbilling_integration_section');
}

function fossbilling_api_key_callback() {
    $api_key = get_option('fossbilling_api_key');
    echo "<input type='text' name='fossbilling_api_key' value='" . esc_attr($api_key) . "' />";
}

function fossbilling_settings_page() {
    ?>
    <div class="wrap">
        <h1>FOSSBilling Integration Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('fossbilling_integration');
            do_settings_sections('fossbilling-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function fossbilling_customers_page() {
    ?>
    <div class="wrap">
        <h1>FOSSBilling Customers</h1>
        <div id="fossbilling-customers-list"></div>
        <h2>Add New Customer</h2>
        <form id="add-customer-form">
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <button type="submit">Add Customer</button>
        </form>
    </div>
    <script>
    jQuery(document).ready(function($) {
        function loadCustomers() {
            $.ajax({
                url: ajaxurl,
                data: { action: 'get_fossbilling_customers' },
                success: function(response) {
                    $('#fossbilling-customers-list').html(response);
                }
            });
        }

        loadCustomers();

        $('#add-customer-form').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'add_fossbilling_customer',
                    first_name: $('input[name="first_name"]').val(),
                    last_name: $('input[name="last_name"]').val(),
                    email: $('input[name="email"]').val()
                },
                success: function(response) {
                    alert('Customer added successfully');
                    loadCustomers();
                }
            });
        });
    });
    </script>
    <?php
}

add_action('wp_ajax_get_fossbilling_customers', 'get_fossbilling_customers');
add_action('wp_ajax_add_fossbilling_customer', 'add_fossbilling_customer');

function get_fossbilling_customers() {
    $customers = WPFOSSBillingSender::getCustomers();
    if (!empty($customers)) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        foreach ($customers as $customer) {
            echo '<tr>';
            echo '<td>' . esc_html($customer['id']) . '</td>';
            echo '<td>' . esc_html($customer['first_name'] . ' ' . $customer['last_name']) . '</td>';
            echo '<td>' . esc_html($customer['email']) . '</td>';
            echo '<td>';
            echo '<button class="edit-customer" data-id="' . esc_attr($customer['id']) . '">Edit</button>';
            echo '<button class="delete-customer" data-id="' . esc_attr($customer['id']) . '">Delete</button>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo 'No customers found.';
    }
    wp_die();
}

function add_fossbilling_customer() {
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);

    WPFOSSBillingSender::addCustomer($first_name, $last_name, $email);
    wp_die();
}

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', 'fossbilling_enqueue_admin_scripts');

function fossbilling_enqueue_admin_scripts($hook) {
    if ('toplevel_page_fossbilling-customers' !== $hook) {
        return;
    }
    wp_enqueue_script('jquery');
}

// Activation, deactivation, and uninstall hooks
register_activation_hook(__FILE__, 'fossbilling_integration_activate');
register_deactivation_hook(__FILE__, 'fossbilling_integration_deactivate');
register_uninstall_hook(__FILE__, 'fossbilling_integration_uninstall');

function fossbilling_integration_activate() {
    if (!class_exists('PhpAmqpLib\Connection\AMQPStreamConnection')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('This plugin requires the php-amqplib library. Please run "composer install" in the plugin directory.');
    }
}

function fossbilling_integration_deactivate() {
    // Perform any cleanup tasks here
}

function fossbilling_integration_uninstall() {
    // Remove any plugin-related data from the database
    delete_option('fossbilling_api_key');
}
?>