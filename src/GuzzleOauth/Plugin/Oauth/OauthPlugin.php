<?php

namespace GuzzleOauth\Plugin\Oauth;

use Guzzle\Plugin\Oauth\OauthPlugin as OauthPluginOriginal;
use Guzzle\Common\Event;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Common\Collection;

/**
 * Adapted version.
 * - can use callback uri
 * - can set token
 */

class OauthPlugin extends OauthPluginOriginal
{
  // Add callback uri to original plugin.
  public function onRequestBeforeSend(Event $event)
  {
    $timestamp = $this->getTimestamp($event);
    $request = $event['request'];
    $nonce = $this->generateNonce($request);

    $authorizationParams = array(
        'oauth_callback'         => isset($this->config['callback_uri'])?$this->config['callback_uri']:NULL,
        'oauth_consumer_key'     => $this->config['consumer_key'],
        'oauth_nonce'            => $nonce,
        'oauth_signature'        => $this->getSignature($request, $timestamp, $nonce),
        'oauth_signature_method' => $this->config['signature_method'],
        'oauth_timestamp'        => $timestamp,
        'oauth_token'            => $this->config['token'],
        'oauth_version'          => $this->config['version'],
    );

    $request->setHeader(
        'Authorization',
        $this->buildAuthorizationHeader($authorizationParams)
    );

    return $authorizationParams;
  }

  private function buildAuthorizationHeader($authorizationParams)
  {
    $authorizationString = 'OAuth ';
    foreach ($authorizationParams as $key => $val) {
      if ($val) {
        $authorizationString .= $key . '="' . urlencode($val) . '", ';
      }
    }
    return substr($authorizationString, 0, -2);
  }

  public function getParamsToSign(RequestInterface $request, $timestamp, $nonce)
  {
    $params = new Collection(array(
      'oauth_consumer_key'     => $this->config['consumer_key'],
      'oauth_nonce'            => $nonce,
      'oauth_signature_method' => $this->config['signature_method'],
      'oauth_timestamp'        => $timestamp,
      'oauth_version'          => $this->config['version']
    ));

    // Filter out oauth_token during temp token step, as in request_token.
    if ($this->config['token'] !== false) {
      $params->add('oauth_token', $this->config['token']);
    }

    // Add call back uri
    if (isset($this->config['callback_uri']) && !empty($this->config['callback_uri'])) {
      $params->add('oauth_callback', $this->config['callback_uri']);
    }

    // Add query string parameters
    $params->merge($request->getQuery());

    // Add POST fields to signing string
    if (!$this->config->get('disable_post_params') &&
        $request instanceof EntityEnclosingRequestInterface &&
        (string) $request->getHeader('Content-Type') == 'application/x-www-form-urlencoded') {

        $params->merge($request->getPostFields());
    }

    // Sort params
    $params = $params->getAll();
    ksort($params);

    return $params;
  }

  public function setToken($token, $token_secret = '') {
    $this->config['token'] = $token;
    if (strlen($token_secret)) {
      $this->config['token_secret'] = $token_secret;
    }
    return $this;
  }

  public function setTokenSecret($token_secret) {
    $this->config['token_secret'] = $token_secret;
    return $this;
  }

  public function setCallbackUri($callback_uri) {
    $this->config['callback_uri'] = $callback_uri;
    return $this;
  }

}
