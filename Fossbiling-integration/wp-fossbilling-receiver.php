<?php
require_once(__DIR__ . '/vendor/autoload.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;

class FBWordPressReceiver
{
    public static function listen()
    {
        $connection = new AMQPStreamConnection('192.168.129.101', 5672, 'hamza', 'student1');
        $channel = $connection->channel();

        $channel->queue_declare('wp_to_fb_queue', false, true, false, false);

        $callback = function ($msg) {
            $data = json_decode($msg->body, true);
            self::processMessage($data);
        };

        $channel->basic_consume('wp_to_fb_queue', '', false, true, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    private static function processMessage($data)
    {
        $api = new \Box_Api_Admin();
        switch ($data['type']) {
            case 'add_customer':
                $api->client_create($data['data']);
                break;
            case 'update_customer':
                $api->client_update($data['data']);
                break;
            case 'delete_customer':
                $api->client_delete(['id' => $data['data']['id']]);
                break;
        }
    }
}

// Start listening for messages
FBWordPressReceiver::listen();
