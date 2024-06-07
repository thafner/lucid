<?php

namespace Thafner\Lucid\Robo\Plugin\Commands;

use Robo\Symfony\ConsoleIO;
use Robo\Task\CommandStack;

class ConfigCommands extends CommandStack
{
  /**
   * Import base drush config.
   *
   * @command lucid:config-import
   *
   * @aliases cim
   */
  function configImport(ConsoleIO $io)
  {
    $io->say("Importing Drupal Configuration.");
    $task = $this->exec(
      './vendor/bin/drush config:import --no-interaction'
    );
    $result = $task->run();

    if (!$result->wasSuccessful()) {}
  }

  /**
   * Run drush deploy task.
   *
   * @command lucid:deploy
   *
   */
  function deploy(ConsoleIO $io)
  {
    $task = $this->exec(
      './vendor/bin/drush deploy --no-interaction'
    );
    $result = $task->run();

    if (!$result->wasSuccessful()) {}

  }
}