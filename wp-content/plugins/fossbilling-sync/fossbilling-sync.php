<?php
/**
 * Plugin Name: FOSSBilling Sync
 * Description: Synchroniseer klantgegevens tussen WordPress en FOSSBilling via RabbitMQ.
 * Version: 1.0
 * Author: Jouw Naam
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Require Composer autoload
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Verbind met RabbitMQ
function connect_to_rabbitmq() {
    $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest', '/fossbilling');
    return $connection->channel();
}

// Synchroniseer klantgegevens naar FOSSBilling
function sync_customer_data($customer_id) {
    $channel = connect_to_rabbitmq();
    $msg_body = json_encode(array('action' => 'update_customer', 'customer_id' => $customer_id));
    $msg = new AMQPMessage($msg_body);
    $channel->basic_publish($msg, '', 'fossbilling_queue');
    $channel->close();
}

// Voeg een actie toe om klantgegevens te synchroniseren bij opslaan
add_action('save_post_customer', 'sync_customer_data', 10, 1);

// Maak een shortcode voor klantoverzicht
function customer_overview_shortcode() {
    // Haal klanten op (voorbeeld, je moet dit aanpassen aan jouw situatie)
    $args = array(
        'post_type' => 'customer',
        'posts_per_page' => -1
    );
    $query = new WP_Query($args);
    $output = '<table><tr><th>ID</th><th>Naam</th></tr>';
    while ($query->have_posts()) : $query->the_post();
        $output .= '<tr><td>' . get_the_ID() . '</td><td>' . get_the_title() . '</td></tr>';
    endwhile;
    $output .= '</table>';
    wp_reset_postdata();
    return $output;
}
add_shortcode('customer_overview', 'customer_overview_shortcode');
