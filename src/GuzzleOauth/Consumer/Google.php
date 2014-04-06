<?php

namespace GuzzleOauth\Consumer;

use GuzzleOauth\BaseConsumerOauth2;

class Google extends BaseConsumerOauth2 {

  /**
   * Google can return an array of emails.
   * We only give back the first one, since we request one e-mail address
   */
  public function getUserEmail($info = NULL) {
    if (empty($info)) {
      $info = $this->getUserInfo();
    }
    $emails = $info->get('emails');
    if (is_array($emails) && count($emails)) {
      $email = reset($emails);
      return $email['value'];
    }
  }

  public function getAuthorizeUrl($request_token, $callback_uri = NULL, $state = NULL) {

    // Change base url
    $old_base_url = $this->getBaseUrl();
    $base_url = 'https://accounts.google.com';
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
    $base_url = 'https://accounts.google.com';
    $this->getConfig()->set('base_url', $base_url);
    $this->setBaseUrl($base_url);

    $return = parent::getAccessToken($query_data, $request_token);

    // Revert base url
    $this->getConfig()->set('base_url', $old_base_url);
    $this->setBaseUrl($old_base_url);

    return $return;
  }

  /**
   * Add offline access params.
   */
  protected function getOfflineAccessParams() {
    return array('access_type' => 'offline');
  }
}