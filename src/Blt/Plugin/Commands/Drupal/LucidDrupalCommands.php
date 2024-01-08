<?php

namespace Thafner\Lucid\Blt\Plugin\Commands\Drupal;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Robo\Exceptions\BltException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
  public function refresh(InputInterface $input, OutputInterface $output): void {
    $io = new SymfonyStyle($input, $output);
    $this->invokeCommands([
      'source:build:composer',
      'lucid:sync:db',
      'drupal:update',
    ]);

    $task = $this->taskDrush()
      ->stopOnFail()
      ->drush('user:login');
    $result = $task->run();
    if (!$result->wasSuccessful()) {
      throw new BltException("Failed to get login link.");
    }
    else {
      $io->success("Site is updated.");
    }
  }

  /**
   * Update current database to reflect the state of the Drupal file system.
   *
   * @command lucid:drupal:update
   *
   * @throws \Robo\Exception\TaskException
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  public function update(InputInterface $input, OutputInterface $output): void {
    $io = new SymfonyStyle($input, $output);
    $io->section("Updating Drupal database");
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

    $io->section("Updating Drupal config");
    $this->invokeCommand('drupal:config:import');
  }
}
