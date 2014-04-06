<?php

namespace GuzzleOauth;

use Guzzle\Common\Collection;
use GuzzleOauth\Plugin\Oauth\OauthPlugin;
use GuzzleOauth\BaseConsumerOauth;
use Guzzle\Http\Url;

class BaseConsumerOauth1 extends BaseConsumerOauth {

  protected $oauthPlugin;

  public function __construct($baseUrl = '', $config = null)
  {
    parent::__construct($baseUrl, $config);

    // Ensure that the OauthPlugin is attached to the client
    $this->oauthPlugin = new OauthPlugin($this->getConfig()->toArray());
    $this->addSubscriber($this->oauthPlugin);
  }

  public static function factory($config = array())
  {
    $class_parts = explode('\\', get_called_class());
    $filename = dirname(__FILE__) . '/Consumer/' . array_pop($class_parts) . '.json';
    $default = array(
      'service_description_path' => $filename,
      'request_token_path' => 'oauth/request_token',
      'authorize_path' => 'oauth/authorize',
      'access_token_path' => 'oauth/access_token',
      'param_user_id' => 'uid',
      'param_user_type' => 'user',
      'param_user_label' => 'name',
      'param_user_email' => 'email',
    );

    // The following values are required when creating the client
    $required = array(
      'base_url',
      'request_token_path',
      'authorize_path',
      'consumer_key',
      'consumer_secret',
    );

    // Merge in default settings and validate the config
    $config = Collection::fromConfig($config, $default, $required);

    // Create a new client, PHP5.3 style
    $client = new static($config->get('base_url'), $config);

    return $client;
  }

  /**
   * Get a Request Token.
   */
  public function getRequestToken($callback_uri = NULL) {

    // set callback_uri
    $this->getOauthPlugin()->setCallbackUri($callback_uri);

    //Get request token
    $response = $this->get($this->getConfig('request_token_path'))->send();
    $request_token = array();
    parse_str($response->getBody(), $request_token);

    // Throw exception if the callback isn't confirmed
    if (!isset($request_token['oauth_callback_confirmed']) || !in_array($request_token['oauth_callback_confirmed'], array(true, "true"))) {
      throw new \Exception("There was an error regarding the callback confirmation");
    }

    // Return RequestToken
    return $request_token;
  }

  /**
   * Return a authorize url.
   */
  public function getAuthorizeUrl($request_token, $callback_uri = NULL, $state = NULL) {
    $request_token = array(
      'oauth_token' => is_array($request_token) ? $request_token['oauth_token'] : $request_token,
    );
    // authorize
    $url = Url::factory($this->getConfig('base_url'));
    $url->addPath($this->getConfig('authorize_path'));
    $url->setQuery($request_token);
    return (string)$url;
  }

  /**
   * Get a Access Token.
   */
  public function getAccessToken($query_data, $request_token) {

    $this->setToken($query_data['oauth_token']);
    $post = array(
      'oauth_verifier' => $query_data['oauth_verifier'],
    );
    //Get request token
    $response = $this->post($this->getConfig('access_token_path'), NULL, $post)->send();
    $access_token = array();
    parse_str($response->getBody(), $access_token);

    // Throw exception if the callback isn't confirmed
    if (!isset($access_token['oauth_token']) || !isset($access_token['oauth_token_secret'])) {
      throw new \Exception('No access token found in response.');
    }

    // Return RequestToken
    return $access_token;
  }

  public function setToken($token, $token_secret = '') {
    $this->getOauthPlugin()->setToken($token, $token_secret);
    $this->getConfig()->set('token', $token);
    if (strlen($token_secret)) {
      $this->getConfig()->set('token_secret', $token_secret);
    }
    return $this;
  }

  public function setTokenSecret($token_secret) {
    $this->getOauthPlugin()->setTokenSecret($token_secret);
    $this->getConfig()->set('token_secret', $token_secret);
    return $this;
  }

  /**
   * Get Oauth Plugin.
   */
  public function getOauthPlugin() {
    return $this->oauthPlugin;
  }
}