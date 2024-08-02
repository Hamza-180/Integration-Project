<?php
require_once 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function sendMessage($connection, $messageBody) {
    try {
        $channel = $connection->channel();
        $channel->queue_declare('test_queue', false, false, false, false);
        
        $message = new AMQPMessage($messageBody);
        $channel->basic_publish($message, '', 'test_queue');
        echo "Bericht verzonden: $messageBody\n";

        $channel->close();
    } catch (Exception $e) {
        echo "Fout bij het verzenden van het bericht: ", $e->getMessage(), "\n";
    }
}

$connection = connectRabbitMQ();
if ($connection) {
    sendMessage($connection, 'Test bericht');
    $connection->close();
}
