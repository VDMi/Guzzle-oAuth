<?php

namespace GuzzleOauth;

use Guzzle\Common\Collection;
use GuzzleOauth\Plugin\Oauth2\Oauth2Plugin;
use GuzzleOauth\BaseConsumerOauth;
use Guzzle\Http\Url;

class BaseConsumerOauth2 extends BaseConsumerOauth {

  protected $oauthPlugin;

  public function __construct($baseUrl = '', $config = null)
  {
    parent::__construct($baseUrl, $config);

    // Ensure that the Oauth2Plugin is attached to the client
    $this->oauthPlugin = new Oauth2Plugin($this->getConfig()->toArray());
    $this->addSubscriber($this->oauthPlugin);

    // Normalize scope
    $this->addScope($this->getConfig('scope'));
  }

  public static function factory($config = array())
  {
    $class_parts = explode('\\', get_called_class());
    $filename = dirname(__FILE__) . '/Consumer/' . array_pop($class_parts) . '.json';
    $default = array(
      'service_description_path' => $filename,
      'authorize_path' => 'oauth/authorize',
      'access_token_path' => 'oauth/access_token',
      'param_user_id' => 'uid',
      'param_user_type' => 'user',
      'param_user_label' => 'name',
      'param_user_email' => 'email',
      'scope_delimiter' => ',',
      'scope' => '',
    );

    // The following values are required when creating the client
    $required = array(
      'base_url',
      'authorize_path',
      'access_token_path',
      'param_user_id',
      'consumer_key',
      'consumer_secret',
      'scope_delimiter',
      'scope'
    );

    // Merge in default settings and validate the config
    $config = Collection::fromConfig($config, $default, $required);

    // Create a new client, PHP5.3 style
    $client = new static($config->get('base_url'), $config);

    return $client;
  }

  /**
   * Get a Request Token.
   * OAuth2 does not use request tokens.
   * We only use it to transport the callback_uri and to be consistent.
   */
  public function getRequestToken($callback_uri = NULL) {
    return array('callback_uri' => $callback_uri);
  }

  /**
   * Return a redirect url.
   */
  public function getAuthorizeUrl($request_token, $callback_uri = NULL, $state = NULL) {
    if (empty($callback_uri) && isset($request_token['callback_uri'])) {
      $callback_uri = $request_token['callback_uri'];
    }
    if (empty($state)) {
      $state = md5(mt_rand());
    }
    $params = array();
    if ($this->getConfig()->get('offline_access')) {
      $params += $this->getOfflineAccessParams();
    }

    $query = array(
      'response_type' => 'code',
      'client_id' => $this->getConfig('consumer_key'),
      'redirect_uri' => $callback_uri,
      'scope' => implode($this->getConfig('scope_delimiter'), $this->getScope()),
      'state' => $state,
    ) + $params;
    // authorize
    $url = Url::factory($this->getConfig('base_url'));
    $url->addPath($this->getConfig('authorize_path'));
    $url->setQuery($query);
    return (string)$url;
  }

  /**
   * Add offline access params.
   */
  protected function getOfflineAccessParams() {
    return array();
  }

  /**
   * Get a Access Token.
   */
  public function getAccessToken($query_data, $request_token) {

    $post = array(
      'client_id' => $this->getConfig('consumer_key'),
      'client_secret' => $this->getConfig('consumer_secret'),
      'redirect_uri' => $request_token['callback_uri'],
      'code' => $query_data['code'],
      'grant_type' => 'authorization_code',
    );
    //Get request token
    $response = $this->post($this->getConfig('access_token_path'), NULL, $post)->send();
    $access_token = array();
    parse_str($response->getBody(), $access_token);
    if (!isset($access_token['access_token'])) {
      // try to read json
      $access_token = $response->json();
    }
    $access_token['request_time'] = time();

    // Throw exception if there isn't a access token.
    if (!isset($access_token['access_token'])) {
      throw new \Exception('No access token found in response.');
    }

    if ($this->getConfig()->get('exchange_short_access_token')) {
      $access_token = $this->exchangeAccessToken($access_token);
    }

    // Return AccessToken
    return $this->normalizeAccessToken($access_token);
  }

  /**
   * Exchange shortlived token for a longlive one..
   */
  protected function exchangeAccessToken($access_token) {
    return $access_token;
  }

  /**
   * Normalize access token
   */
  protected function normalizeAccessToken($access_token) {
    if (!isset($access_token['token_type'])) {
      $access_token['token_type'] = 'Bearer';
    }
    if (!isset($access_token['expires_at']) && isset($access_token['expires_in'])) {
      $access_token['expires_at'] = $access_token['expires_in'] + $access_token['request_time'];
    }
    return $access_token;
  }

  /**
   * Set Token (and secret)
   */
  public function setToken($token, $token_secret = '') {
    $this->getOauthPlugin()->setToken($token, $token_secret);
    $this->getConfig()->set('access_token', $token);
    return $this;
  }

  /**
   * Set Token secret
   * Does not do anything, just here to be consistent.
   */
  public function setTokenType($token_secret) {
    return $this;
  }

  /**
   * Get Scope
   */
  public function getScope() {
    $scope = $this->getConfig('scope');
    if (empty($scope)) {
      $scope = array();
    }
    if (is_string($scope)) {
      $this->addScope($scope);
      $scope = $this->getConfig('scope');
    }
    return $scope;
  }

  /**
   *  Add Scope
   */
  public function addScope($extra_scope) {
    $scope = $this->getConfig('scope');
    if (empty($scope)) {
      $scope = array();
    }
    if (is_string($scope)) {
      $scope = preg_split('/\s,\s|\s,|,\s|,|\s/', $scope);
    }
    if (is_string($extra_scope)) {
      $extra_scope = preg_split('/\s,\s|\s,|,\s|,|\s/', $extra_scope);
    }
    $scope = array_unique(array_merge($scope, $extra_scope));
    foreach ($scope as $key => $value) {
      if (empty($value)) {
        unset($scope[$key]);
      }
    }
    $scope = array_values($scope);

    $this->getConfig()->set('scope', $scope);
    return $this;
  }

  /**
   *  Ensure Scope
   */
  public function ensureScope($scope) {
    $this->addScope($scope);
    return TRUE;
  }

  /**
   * Get Oauth Plugin.
   */
  public function getOauthPlugin() {
    return $this->oauthPlugin;
  }
}