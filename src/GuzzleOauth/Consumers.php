<?php

namespace GuzzleOauth;

use GuzzleOauth\Service\Builder\ServiceBuilder;

class Consumers {

  public static function get($consumer, $config = array()) {

    //normalize
    $normalize = array('oauth_token' => 'token', 'oauth_token_secret' => 'token_secret');
    foreach ($normalize as $key => $normalized_key) {
      if (isset($config[$key])) {
        $config[$normalized_key] = $config[$key];
        unset($config[$key]);
      }
    }

    $builder = ServiceBuilder::factory(dirname(__FILE__) . '/consumers.json');

    // Merge params with config.
    $builder->mergeData($consumer, $config);

    // Return Consumer.
    return $builder->get($consumer);

  }
}
