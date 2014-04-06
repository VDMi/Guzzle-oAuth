<?php

namespace GuzzleOauth\Consumer;

use GuzzleOauth\BaseConsumerOauth2;
use Guzzle\Http\Client;

class Facebook extends BaseConsumerOauth2 {

  /**
   * Change base url.
   */
  public function getAuthorizeUrl($request_token, $callback_uri = NULL, $state = NULL) {

    // Change base url
    $old_base_url = $this->getBaseUrl();
    $base_url = 'https://www.facebook.com';
    $this->getConfig()->set('base_url', $base_url);
    $this->setBaseUrl($base_url);

    $return = parent::getAuthorizeUrl($request_token, $callback_uri, $state);

    // Revert base url
    $this->getConfig()->set('base_url', $old_base_url);
    $this->setBaseUrl($old_base_url);

    return $return;
  }

  /**
   * Facebook does it differently... again!
   */
  protected function normalizeAccessToken($access_token) {
    if (!isset($access_token['expires_in']) && isset($access_token['expires'])) {
      $access_token['expires_in'] = $access_token['expires'];
      unset($access_token['expires']);
    }
    return parent::normalizeAccessToken($access_token);
  }

  protected function exchangeAccessToken($access_token) {
    $params = array(
      'client_id' => $this->getConfig('consumer_key'),
      'client_secret' => $this->getConfig('consumer_secret'),
      'grant_type' => 'fb_exchange_token',
      'fb_exchange_token' => $access_token['access_token'],
    );
    try {
      $request = $this->get('oauth/access_token');
      $request->getQuery()->replace($params);
      $response = $request->send();
      $replace_token = array();
      parse_str($response->getBody(), $replace_token);
      $replace_token['request_time'] = time();
      return $replace_token;
    } catch(Exception $e) { }
    return $access_token;
  }

  public function getConnectedAccounts($info = NULL) {
    $items = parent::getConnectedAccounts($info);
    if (empty($info)) {
      $info = $this->getUserInfo();
    }
    $accounts = $this->getAllAccountInfo()->get('data');
    foreach ($accounts as $key => $account) {
      $item = array(
        'account_id' => $account[$this->getConfig('param_user_id')],
        'account_label' => $account[$this->getConfig('param_user_label')],
        'account_type' => 'page',
        'access_token' => array('access_token' => $account['access_token']),
        'expires' => 0,
      );
      $items[] = $item;
    }
    return $items;
  }
}