<?php
require_once(__DIR__ . '/vendor/autoload.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;

try {
    $connection = new AMQPStreamConnection('192.168.129.101', 5672, 'hamza', 'student1', 'myvhost');
    echo "Connected to RabbitMQ!";
    $connection->close();
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
