<?php

namespace Thafner\Lucid\Robo\Plugin\Commands;

use Robo\Symfony\ConsoleIO;
use Robo\Task\CommandStack;
use Thafner\Lucid\Robo\Plugin\Traits\DatabaseDownloadTrait;

class SyncCommand extends CommandStack
{
  use DatabaseDownloadTrait;

  /**
   * Copies remote db to local db for default site.
   *
   * @command lucid:sync-database
   *
   * @aliases sdb
   */
  function syncDatabase(ConsoleIO $io, $site = 'default')
  {
    $name = $this->getConfigValue('project.machine_name');
    $bucket = $this->getConfigValue('lucid.database.s3_bucket');
    $directory = $this->getConfigValue('lucid.database.s3_directory');
    $file = $this->getConfigValue('lucid.database.s3_filename');

    $io->title("Syncing database.");

    $database_file = $this->databaseDownload($name, $bucket, $directory, $file, $io);

    $io->section("Importing database into Drupal database.");

    $task = $this->exec("./vendor/bin/drush sql-drop");
    $task = $this->exec("./vendor/bin/drush sql:query --file={$database_file}");

    $result = $task->run();
    $result->stopOnFail();

    $io->success("Drupal database updated.");
  }
}