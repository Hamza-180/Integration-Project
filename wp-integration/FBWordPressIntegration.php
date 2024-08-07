<?php

class FBWordPressIntegration extends \Box\Mod\Module
{
    public function init()
    {
        $this->registerHooks();
    }

    protected function registerHooks()
    {
        $this->di['hooks']->on('after_client_signup', [$this, 'onAfterClientSignup']);
        $this->di['hooks']->on('after_client_update', [$this, 'onAfterClientUpdate']);
        $this->di['hooks']->on('after_client_delete', [$this, 'onAfterClientDelete']);
    }

    public function onAfterClientSignup(\Box\Event $event)
    {
        $params = $event->getParameters();
        $client = $this->di['db']->load('Client', $params['id']);
        $this->sendMessageToWP($client, 'customer_added');
    }

    public function onAfterClientUpdate(\Box\Event $event)
    {
        $params = $event->getParameters();
        $client = $this->di['db']->load('Client', $params['id']);
        $this->sendMessageToWP($client, 'customer_updated');
    }

    public function onAfterClientDelete(\Box\Event $event)
    {
        $params = $event->getParameters();
        $this->sendMessageToWP((object)['id' => $params['id']], 'customer_deleted');
    }

    protected function sendMessageToWP($client, $action)
    {
        $message = [
            'id' => $client->id,
            'first_name' => $client->first_name ?? '',
            'last_name' => $client->last_name ?? '',
            'email' => $client->email ?? '',
        ];

        try {
            FBWordPressSender::sendMessage($message, $action);
        } catch (Exception $e) {
            error_log('Error sending message to WordPress: ' . $e->getMessage());
        }
    }

    public static function getConfig()
    {
        return [
            'id'           => 'wp-integration',
            'type'         => 'mod',
            'name'         => 'WordPress Integration',
            'description'  => 'Integrates FOSSBilling with WordPress using RabbitMQ',
            'icon_url'     => 'icon.png',
            'homepage_url' => '',
            'author'       => 'Hamza',
            'author_url'   => '',
            'license'      => '',
            'version'      => '1.0',
        ];
    }
}
?>
