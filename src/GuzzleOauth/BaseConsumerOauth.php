<?php

namespace GuzzleOauth;

use Guzzle\Service\Client;
use Guzzle\Service\Description\ServiceDescription;

class BaseConsumerOauth extends Client {

  public function __construct($baseUrl = '', $config = null)
  {
    parent::__construct($baseUrl, $config);

    // Set the description for this Service.
    $filename = $config->get('service_description_path');
     if (is_file($filename)) {
      $this->setDescription(ServiceDescription::factory($filename));
    }
  }
  public function getUserId($info = NULL) {
    if (empty($info)) {
      $info = $this->getUserInfo();
    }
    return $info->get($this->getConfig('param_user_id'));
  }

  public function getUserLabel($info = NULL) {
    if (empty($info)) {
      $info = $this->getUserInfo();
    }
    return $info->get($this->getConfig('param_user_label'));
  }

  public function getUserEmail($info = NULL) {
    if (empty($info)) {
      $info = $this->getUserInfo();
    }
    return $info->get($this->getConfig('param_user_email'));
  }

  public function getConnectedAccounts($info = NULL) {
    if (empty($info)) {
      $info = $this->getUserInfo();
    }
    $item = array(
      'account_id' => $this->getUserId($info),
      'account_label' => $this->getUserLabel($info),
      'account_type' => 'user',
    );
    return array($item);
  }
}