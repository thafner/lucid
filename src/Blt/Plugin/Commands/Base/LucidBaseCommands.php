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

  /**
   * Update current database to reflect the state of the Drupal file system.
   *
   * @command lucid:base:refresh
   * @aliases lucid:refresh lbr lr
   *
   * @throws \Robo\Exception\TaskException
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  public function refresh(): void {
    $this->invokeCommands([
      'lucid:base:composer:install',
      'lucid:sync:db',
      'lucid:drupal:update',
      'lucid:theme:frontend'
    ]);
  }

}
