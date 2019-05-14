<?php

namespace GuzzleOauth\Consumer;

use GuzzleOauth\BaseConsumerOauth2;
use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LinkedIn extends BaseConsumerOauth2 {

  public function __construct($baseUrl = '', $config = null) {

    $config->set('query_param_key', 'oauth2_access_token');
    $config->set('authorize_location', 'query');

    parent::__construct($baseUrl, $config);

    $this->addSubscriber(new LinkedInJsonPlugin());
  }

  public function getAuthorizeUrl($request_token, $callback_uri = NULL, $state = NULL) {

    // Change base url
    $old_base_url = $this->getBaseUrl();
    $base_url = 'https://www.linkedin.com';
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
    $base_url = 'https://www.linkedin.com';
    $this->getConfig()->set('base_url', $base_url);
    $this->setBaseUrl($base_url);

    $return = parent::getAccessToken($query_data, $request_token);

    // Revert base url
    $this->getConfig()->set('base_url', $old_base_url);
    $this->setBaseUrl($old_base_url);

    return $return;
  }

    /**
     * Retrieves the full name of the user.
     *
     * @param null $info the user info as an instance of Guzzle\Service\Resource\Model
     *
     * @return string the name.
     */
  public function getUserLabel($info = NULL) {
    if (empty($info)) {
      $info = $this->getUserInfo();
    }
    return $info->get('localizedFirstName') . ' ' . $info->get('localizedLastName');
  }

    /**
     * Retrieves the email address of the user
     * @param null $info
     *
     * @return mixed the email address, otherwise <code>FALSE</code>.
     */
  public function getUserEmail($info = NULL) {
    $response = $this->get('emailAddress?q=members&projection=(elements*(handle~))')->send();
    if($response->getStatusCode() !== 200 || empty($response)) {
      return FALSE;
    }
    $result = $response->json();
    return $result['elements'][0]['handle~'][$this->getConfig('param_user_email')];
  }

    /**
     * Retrieves the URL of the profile picture.
     *
     * @param null $info the user info as an instance of Guzzle\Service\Resource\Model
     *
     * @return mixed the url, otherwise <code>FALSE</code>.
     */
  public function getProfilePicture($info = NULL) {
    if (empty($info)) {
      $info = $this->getUserInfo();
    }

    if($profilePicture = (array) $info->get('profilePicture') && !empty($profilePicture)) {
        return $profilePicture['displayImage~']['elements'][0]['identifiers'][0]['identifier'];
    }

    return FALSE;
  }
}



/**
 * Set JSON header for LinkedIn
 */
class LinkedInJsonPlugin implements EventSubscriberInterface {

    public static function getSubscribedEvents() {
      return array(
        'request.before_send' => array('onRequestBeforeSend', -900)
      );
    }

    public function onRequestBeforeSend(Event $event) {
      $event['request']->setHeader(
        'x-li-format',
        'json'
      );
    }
}