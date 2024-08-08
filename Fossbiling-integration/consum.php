<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQConsumer
{
    public function listen()
    {
        $connection = new AMQPStreamConnection('192.168.129.68', 5672, 'hamza', 'student1', '/');
        $channel = $connection->channel();

        // Assurez-vous que la queue et l'exchange sont correctement déclarés
        $channel->queue_declare('wordpress_to_fossbilling', false, true, false, false);
        $channel->exchange_declare('fossbilling_to_wordpress', 'direct', false, true, false);
        $channel->queue_bind('wordpress_to_fossbilling', 'fossbilling_to_wordpress', 'fb_to_wp');

        // Consommation des messages
        $callback = function ($msg) {
            echo 'Received message: ', $msg->body, "\n";
            $data = json_decode($msg->body, true);
            FBWordPressReceiver::processMessage($data);
        };

        $channel->basic_consume('wordpress_to_fossbilling', '', false, true, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
