<?php

namespace Thafner\Lucid\Blt\Plugin\Commands\Theme;

use Acquia\Blt\Robo\BltTasks;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Defines commands in the "custom" namespace.
 */
class LucidThemeCommands extends BltTasks {

  /**
   * Runs all frontend targets.
   *
   * @command lucid:theme:frontend
   */
  public function frontend() {
    $this->invokeCommands([
      'source:build:frontend-reqs',
      'source:build:frontend-assets',
    ]);
  }

  /**
   * Executes source:build:frontend-assets target hook.
   *
   * @command lucid:theme:frontend-assets
   */
  public function assets() {
    return $this->invokeHook('frontend-assets');
  }

  /**
   * Executes source:build:frontend-reqs target hook.
   *
   * @command lucid:theme:frontend-reqs
   */
  public function reqs() {
    return $this->invokeHook('frontend-reqs');
  }

}
