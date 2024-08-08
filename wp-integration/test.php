<?php
require_once(__DIR__ . '/vendor/autoload.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('192.168.129.68', 5672, 'hamza', 'student1', '/');
$channel = $connection->channel();

// Déclarez l'échange si nécessaire
$channel->exchange_declare('fossbilling_to_wordpress', 'direct', false, true, false);

$messageBody = json_encode([
    'type' => 'customer_added',
    'data' => [
        'id' => 123,
        'email' => 'test@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe'
    ]
]);

$message = new AMQPMessage($messageBody, [
    'content_type' => 'application/json',
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
]);

// Publiez le message
$channel->basic_publish($message, 'fossbilling_to_wordpress', 'fb_to_wp');

echo "Message sent.\n";

$channel->close();
$connection->close();
?>
