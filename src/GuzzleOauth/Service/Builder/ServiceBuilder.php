<?php

namespace GuzzleOauth\Service\Builder;

use Guzzle\Service\Builder\ServiceBuilder as ServiceBuilderOriginal;
use Guzzle\Common\Collection;

class ServiceBuilder extends ServiceBuilderOriginal {

  public function mergeData($consumer, $config = array()) {

    // Merge params with config.
    $this->builderConfig[$consumer]['params'] = Collection::fromConfig(
      $this->builderConfig[$consumer]['params'],
      $config
    )->toArray();

    return $this;

  }
}
