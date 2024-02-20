<?php

namespace Thafner\Lucid\Blt\Plugin\EnvironmentDetector;

use Acquia\Blt\Robo\Common\EnvironmentDetector;

class TugboatDetector extends EnvironmentDetector {

  /**
   * {@inheritdoc}
   */
  public static function getCiEnv() {
    return isset($_ENV['TUGBOAT_URL']) ? 'tugboat' : null;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCiSettingsFile(): string {
    $TUGBOAT_ROOT = getenv('TUGBOAT_ROOT');
    return sprintf("%s/vendor/thafner/lucid/settings/tugboat.settings.php", dirname($TUGBOAT_ROOT));
  }
}
