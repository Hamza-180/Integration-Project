<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

class FBWordPressReceiver
{
    public static function listen()
    {
        try {
            $connection = new AMQPStreamConnection('192.168.129.101', 5672, 'hamza', 'student1', 'myvhost');
            $channel = $connection->channel();

            $channel->queue_declare('wordpress_to_fossbilling', false, true, false, false);

            $callback = function ($msg) {
                $data = json_decode($msg->body, true);
                echo ' [x] Received ', $msg->body, "\n";  // Journalisation de la réception du message
                self::processMessage($data);
            };

            $channel->basic_consume('wordpress_to_fossbilling', '', false, true, false, false, $callback);

            echo " [*] Waiting for messages. To exit press CTRL+C\n";  // Journalisation de l'attente des messages

            while ($channel->is_consuming()) {
                $channel->wait();
            }

            $channel->close();
            $connection->close();
        } catch (\Exception $e) {
            error_log('FOSSBilling WordPress Integration: RabbitMQ listening error - ' . $e->getMessage());
        }
    }

    private static function processMessage($data)
    {
        echo 'Processing message: ' . print_r($data, true) . "\n";  // Journalisation du traitement du message

        $di = include '/var/www/fossbilling/di.php';
        $clientService = $di['mod_service']('client');

        switch ($data['type']) {
            case 'add_customer':
                self::addCustomerToFOSSBilling($clientService, $data['data']);
                break;
            case 'update_customer':
                self::updateCustomerInFOSSBilling($clientService, $data['data']);
                break;
            case 'delete_customer':
                self::deleteCustomerFromFOSSBilling($clientService, $data['data']);
                break;
            default:
                error_log('FOSSBilling WordPress Integration: Unknown message type - ' . $data['type']);
        }
    }

    private static function addCustomerToFOSSBilling($clientService, $customerData)
    {
        try {
            echo 'Adding customer to FOSSBilling: ' . print_r($customerData, true) . "\n";  // Journalisation de l'ajout du client
            $clientService->create($customerData);
        } catch (\Exception $e) {
            error_log('FOSSBilling WordPress Integration: Error creating customer - ' . $e->getMessage());
        }
    }

    private static function updateCustomerInFOSSBilling($clientService, $customerData)
    {
        try {
            echo 'Updating customer in FOSSBilling: ' . print_r($customerData, true) . "\n";  // Journalisation de la mise à jour du client
            $client = $clientService->findOneByEmail($customerData['email']);
            if ($client) {
                $clientService->update($client['id'], $customerData);
            }
        } catch (\Exception $e) {
            error_log('FOSSBilling WordPress Integration: Error updating customer - ' . $e->getMessage());
        }
    }

    private static function deleteCustomerFromFOSSBilling($clientService, $customerData)
    {
        try {
            echo 'Deleting customer from FOSSBilling: ' . print_r($customerData, true) . "\n";
