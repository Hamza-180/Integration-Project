require_once(__DIR__ . '/vendor/autoload.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class WPFOSSBillingSender {
    private static function getConnection() {
        try {
            return new AMQPStreamConnection('192.168.129.101', 5672, 'hamza', 'student1', 'myvhost');
        } catch (Exception $e) {
            error_log('FOSSBilling Integration: RabbitMQ connection error - ' . $e->getMessage());
            return null;
        }
    }

    private static function getApiKey() {
        return get_option('fossbilling_api_key');
    }

    public static function sendMessage($data, $type) {
        try {
            $connection = self::getConnection();
            if (!$connection) {
                throw new Exception('Could not establish RabbitMQ connection');
            }
            $channel = $connection->channel();

            $message = new AMQPMessage(json_encode([
                'type' => $type,
                'data' => $data
            ]));

            $channel->basic_publish($message, '', 'wordpress_to_fossbilling');

            $channel->close();
            $connection->close();
        } catch (Exception $e) {
            error_log('FOSSBilling Integration: Error sending message - ' . $e->getMessage());
        }
    }

    public static function addCustomer($first_name, $last_name, $email) {
        self::sendMessage([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email
        ], 'add_customer');
    }

    public static function getCustomers() {
        $api_url = 'http://192.168.129.101/admin/client';
        $api_key = self::getApiKey();

        if (empty($api_key)) {
            error_log('FOSSBilling Integration: API key is not set');
            return [];
        }

        $response = wp_remote_post($api_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($api_key . ':'),
            ],
            'body' => json_encode([
                'per_page' => 100,
                'page' => 1,
            ]),
        ]);

        if (is_wp_error($response)) {
            error_log('FOSSBilling Integration: Error fetching customers - ' . $response->get_error_message());
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['result']) || !is_array($data['result'])) {
            error_log('FOSSBilling Integration: Unexpected response format from FOSSBilling API');
            return [];
        }

        $customers = [];
        foreach ($data['result'] as $client) {
            $customers[] = [
                'id' => $client['id'],
                'first_name' => $client['first_name'],
                'last_name' => $client['last_name'],
                'email' => $client['email'],
            ];
        }

        return $customers;
    }
}
