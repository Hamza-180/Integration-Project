<?php
require_once 'vendor/autoload.php';  // Zorg ervoor dat dit pad klopt

use PhpAmqpLib\Connection\AMQPStreamConnection;

function testRabbitMQConnection() {
    try {
        $connection = new AMQPStreamConnection('192.168.0.200', 5672, 'hamza', 'student1', 'myvhost');
        echo "Verbonden met RabbitMQ server.\n";

        // Test verzenden van een bericht
        $channel = $connection->channel();
        $channel->queue_declare('test_queue', false, false, false, false);
        
        $msg = new PhpAmqpLib\Message\AMQPMessage('Test bericht');
        $channel->basic_publish($msg, '', 'test_queue');
        echo "Bericht verzonden naar 'test_queue'.\n";

        // Test ontvangen van een bericht
        $callback = function($msg) {
            echo 'Ontvangen bericht: ', $msg->body, "\n";
        };

        $channel->basic_consume('test_queue', '', false, true, false, false, $callback);

        echo "Wachten op berichten. Druk CTRL+C om te stoppen.\n";

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    } catch (Exception $e) {
        echo "Fout bij het verbinden met RabbitMQ: ", $e->getMessage(), "\n";
    }
}

testRabbitMQConnection();
?>
