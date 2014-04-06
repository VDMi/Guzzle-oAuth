<?php

namespace GuzzleOauth\Plugin\Oauth2;

use Guzzle\Common\Event;
use Guzzle\Common\Collection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * OAuth2 signing plugin
 */
class Oauth2Plugin implements EventSubscriberInterface
{
    /**
     * @var Collection Configuration settings
     */
    protected $config;

    public function __construct($config)
    {
        $this->config = Collection::fromConfig($config, array(
            'query_param_key' => 'access_token',
            'authorize_location' => 'header',
            'token_type' => 'Bearer',
            'access_token' => '',
        ), array(
            'token_type', 'access_token', 'query_param_key', 'authorize_location'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            'request.before_send' => array('onRequestBeforeSend', -1000)
        );
    }

    /**
     * Request before-send event handler
     *
     * @param Event $event Event received
     * @return array
     */
    public function onRequestBeforeSend(Event $event)
    {
        if (!strlen($this->config['access_token'])) {
          return;
        }

        switch ($this->config['authorize_location']) {
          case 'query':
            $event['request']->getQuery()->set(
              $this->config['query_param_key'],
              $this->config['access_token']
            );
            break;

          default:
            $authorizationParams = array(
                'access_token'     => $this->config['access_token'],
                'token_type'       => $this->config['token_type'],
            );

            $event['request']->setHeader(
                'Authorization',
                $this->buildAuthorizationHeader($authorizationParams)
            );
            break;
        }
    }

    /**
     * Builds the Authorization header for a request
     *
     * @param array $authorizationParams Associative array of authorization parameters
     *
     * @return string
     */
    private function buildAuthorizationHeader($authorizationParams)
    {
        $authorizationString = $authorizationParams['token_type'] . ' '
          . $authorizationParams['access_token'];

        return $authorizationString;
    }

    public function setToken($token) {
      $this->config['access_token'] = $token;
    }

    public function setTokenType($token_type) {
      $this->config['token_type'] = $token_type;
    }

}
