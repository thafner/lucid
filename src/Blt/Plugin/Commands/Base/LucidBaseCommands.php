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

  public function refresh(array $options = [
    'sync-public-files' => FALSE,
    'sync-private-files' => FALSE,
  ]): void {
    $commands = $this->getConfigValue('sync.commands');
    if ($options['sync-public-files'] || $this->getConfigValue('sync.public-files')) {
      $commands[] = 'lucid:sync:public-files';
    }
    if ($options['sync-private-files'] || $this->getConfigValue('sync.private-files')) {
      $commands[] = 'lucid:sync:private-files';
    }
    $this->invokeCommands($commands);
  }

}
