<?php

namespace Thafner\Lucid\Blt\Plugin\Commands\Sync;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Robo\Exceptions\BltException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thafner\Lucid\Blt\Plugin\Traits\DatabaseTrait;

/**
 * Defines commands in the "sync" namespace.
 */
class LucidSyncCommands extends BltTasks {

  use DatabaseTrait;

  /**
   * Copies remote db to local db for default site.
   *
   * @command lucid:sync:db
   *
   * @aliases lsdb
   * @validateDrushConfig
   *
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  public function syncDb(InputInterface $input, OutputInterface $output) {
    $name = $this->getConfigValue('project.machine_name');
    $bucket = $this->getConfigValue('lucid.database.s3_bucket');
    $key = $this->getConfigValue('lucid.database.s3_key_prefix_string');

    $io = new SymfonyStyle($input, $output);
    $io->title("Syncing database.");

    $database_file = $this->databaseDownload($name, $bucket, $key, $input, $output);

    $io->section("Importing database into Drupal database.");
    $task = $this->taskDrush()
      ->drush('sql-drop')
      ->drush('sql:query --file=' . getenv('LANDO_MOUNT') . '/' . $database_file);
    $result = $task->run();
    $exit_code = $result->getExitCode();

    if ($exit_code) {
      throw new BltException("Unable to import database dump into Drupal database.");
    }
    else {
      $io->success("Drupal database updated.");
    }
  }

}