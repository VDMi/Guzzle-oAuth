<?php

namespace GuzzleOauth\Consumer;

use Guzzle\Common\Collection;
use GuzzleOauth\BaseConsumerOauth1;
use Guzzle\Service\Client;
use Guzzle\Service\Description\ServiceDescription;

class Twitter extends BaseConsumerOauth1 {

  public function getUserId($info = NULL) {
    // Twitter stores user id also in the token.
    // So we don't need to call user info.
    if ($user_id = $this->getConfig('user_id')) {
      return $user_id;
    }
    return parent::getUserId($info);
  }

  public function getRequestToken($callback_uri = NULL) {

    // Change base url
    $old_base_url = $this->getBaseUrl();
    $base_url = 'https://api.twitter.com';
    $this->getConfig()->set('base_url', $base_url);
    $this->setBaseUrl($base_url);

    $return = parent::getRequestToken($callback_uri);

    // Revert base url
    $this->getConfig()->set('base_url', $old_base_url);
    $this->setBaseUrl($old_base_url);

    return $return;
  }

  public function getAuthorizeUrl($request_token, $callback_uri = NULL, $state = NULL) {

    // Change base url
    $old_base_url = $this->getBaseUrl();
    $base_url = 'https://api.twitter.com';
    $this->getConfig()->set('base_url', $base_url);
    $this->setBaseUrl($base_url);

    $return = parent::getAuthorizeUrl($request_token, $callback_uri, $state);

    // Revert base url
    $this->getConfig()->set('base_url', $old_base_url);
    $this->setBaseUrl($old_base_url);

    return $return;
  }

  public function getAccessToken($query_data, $request_token) {

    // Change base url
    $old_base_url = $this->getBaseUrl();
    $base_url = 'https://api.twitter.com';
    $this->getConfig()->set('base_url', $base_url);
    $this->setBaseUrl($base_url);

    $return = parent::getAccessToken($query_data, $request_token);

    // Revert base url
    $this->getConfig()->set('base_url', $old_base_url);
    $this->setBaseUrl($old_base_url);

    return $return;
  }

}
