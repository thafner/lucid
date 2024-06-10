<?php

namespace Thafner\Lucid\Robo\Plugin\Commands;

use Robo\Symfony\ConsoleIO;
use Robo\Task\CommandStack;
use Thafner\Lucid\Robo\LucidTasks;

class ConfigCommand extends LucidTasks
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
    $task = $this->taskDrush()
      ->stopOnFail()
      ->drush("config:import");
    $result = $task->run();
    if (!$result->wasSuccessful()) {
      throw new \Exception("Failed to import configuration.");
    }
  }

//  /**
//   * Run drush deploy task.
//   *
//   * @command lucid:deploy
//   *
//   */
//  function deploy(ConsoleIO $io)
//  {
//    $task = $this->exec(
//      './vendor/bin/drush deploy --no-interaction'
//    );
//    $result = $task->run();
//
//    if (!$result->wasSuccessful()) {}
//
//  }
}