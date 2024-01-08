<?php

namespace Thafner\Lucid\Blt\Plugin\Commands\Base;

use Acquia\Blt\Robo\BltTasks;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Defines commands in the "custom" namespace.
 */
class LucidBaseCommands extends BltTasks {

  /**
   * Print "Hello world!" to the console.
   *
   * @command lucid:base:composer:install
   * @description This is an example command.
   */
  public function composerInstall(): void {
    $this->taskComposerInstall()
      ->dev()
      ->noInteraction()
      ->run();
  }

}
