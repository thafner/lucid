<?php

namespace Thafner\Lucid\Blt\Plugin\Commands\Drupal;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Robo\Exceptions\BltException;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines commands in the "drupal" namespace.
 */
class LucidDrupalCommands extends BltTasks {

  /**
   * Update current database to reflect the state of the Drupal file system.
   *
   * @command lucid:drupal:refresh
   * @aliases lucid:refresh ldr lr
   *
   * @throws \Robo\Exception\TaskException
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  public function refresh(): void {

    $this->invokeCommands([
      'lucid:base:composer:install',
      'lucid:sync:db',
      'drupal:update',
    ]);
  }

  /**
   * Update current database to reflect the state of the Drupal file system.
   *
   * command lucid:drupal:update
   *
   * @throws \Robo\Exception\TaskException
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  public function update(): void {
    $task = $this->taskDrush()
      ->stopOnFail()
      // Execute db updates.
      // This must happen before configuration is imported. For instance, if you
      // add a dependency on a new extension to an existing configuration file,
      // you must enable that extension via an update hook before attempting to
      // import the configuration. If a db update relies on updated
      // configuration, you should import the necessary configuration file(s) as
      // part of the db update.
      ->drush("updb");

    $result = $task->run();
    if (!$result->wasSuccessful()) {
      throw new BltException("Failed to execute database updates!");
    }

    $this->invokeCommand('drupal:config:import');
  }
}
