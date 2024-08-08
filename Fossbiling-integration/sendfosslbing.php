<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('192.168.129.68', 5672, 'hamza', 'student1', '/');
$channel = $connection->channel();

$message = new AMQPMessage(json_encode(['type' => 'customer_added', 'data' => ['id' => 1, 'email' => 'test@example.com']]));
$channel->basic_publish($message, 'fossbilling_to_wordpress', 'fb_to_wp');

$channel->close();
$connection->close();
