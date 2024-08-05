<?php
require_once(__DIR__ . '/vendor/autoload.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;

class WPFOSSBillingReceiver {
    public static function listen() {
        try {
            $connection = new AMQPStreamConnection('192.168.129.101', 5672, 'hamza', 'student1', 'myvhost');
            $channel = $connection->channel();

            $channel->queue_declare('fb_to_wp_queue', false, true, false, false);

            $callback = function ($msg) {
                $data = json_decode($msg->body, true);
                self::processMessage($data);
            };

            $channel->basic_consume('fb_to_wp_queue', '', false, true, false, false, $callback);

            while ($channel->is_consuming()) {
                $channel->wait();
            }

            $channel->close();
            $connection->close();
        } catch (Exception $e) {
            error_log('FOSSBilling Integration: RabbitMQ listening error - ' . $e->getMessage());
        }
    }

    private static function processMessage($data) {
        switch ($data['type']) {
            case 'customer_added':
                self::addCustomerToWordPress($data['data']);
                break;
            case 'customer_updated':
                self::updateCustomerInWordPress($data['data']);
                break;
            case 'customer_deleted':
                self::deleteCustomerFromWordPress($data['data']);
                break;
            default:
                error_log('FOSSBilling Integration: Unknown message type - ' . $data['type']);
        }
    }

    private static function addCustomerToWordPress($customerData) {
        $user = get_user_by('email', $customerData['email']);
        if (!$user) {
            $userId = wp_create_user($customerData['email'], wp_generate_password(), $customerData['email']);
            if (!is_wp_error($userId)) {
                wp_update_user([
                    'ID' => $userId,
                    'first_name' => $customerData['first_name'],
                    'last_name' => $customerData['last_name'],
                ]);
                update_user_meta($userId, 'fossbilling_id', $customerData['id']);
            } else {
                error_log('FOSSBilling Integration: Error creating user - ' . $userId->get_error_message());
            }
        }
    }

    private static function updateCustomerInWordPress($customerData) {
        $user = get_users([
            'meta_key' => 'fossbilling_id',
            'meta_value' => $customerData['id'],
            'number' => 1,
        ]);

        if (!empty($user)) {
            $userId = $user[0]->ID;
            $result = wp_update_user([
                'ID' => $userId,
                'user_email' => $customerData['email'],
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'],
            ]);
            if (is_wp_error($result)) {
                error_log('FOSSBilling Integration: Error updating user - ' . $result->get_error_message());
            }
        }
    }

    private static function deleteCustomerFromWordPress($customerData) {
        $user = get_users([
            'meta_key' => 'fossbilling_id',
            'meta_value' => $customerData['id'],
            'number' => 1,
        ]);

        if (!empty($user)) {
            $result = wp_delete_user($user[0]->ID);
            if (!$result) {
                error_log('FOSSBilling Integration: Error deleting user with FOSSBilling ID ' . $customerData['id']);
            }
        }
    }
}

// Start listening for messages
WPFOSSBillingReceiver::listen();
?>