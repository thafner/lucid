<?php

namespace Thafner\Lucid\Blt\Plugin\Commands\Sync;

use Acquia\Blt\Robo\BltTasks;
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

    $this->databaseDownload($name, $bucket, $key, $input, $output);



  }


  /**
   * Synchronize local env from remote (remote --> local).
   *
   * Copies remote db to local db, re-imports config, and executes db updates
   * for each multisite.
   *
   * @param array $options
   *   Array of CLI options.
   *
   * @command lucid:sync:default:site
   * @aliases ls lucid:sync lucid:sync:default sync sync:refresh
   */
  public function sync(array $options = [
    'sync-public-files' => FALSE,
    'sync-private-files' => FALSE,
  ]) {
    $commands = $this->getConfigValue('sync.commands');
    if ($options['sync-public-files'] || $this->getConfigValue('sync.public-files')) {
      $commands[] = 'lucid:sync:public-files';
    }
    if ($options['sync-private-files'] || $this->getConfigValue('sync.private-files')) {
      $commands[] = 'lucid:sync:private-files';
    }
    $this->invokeCommands($commands);
  }

  /**
   * Copies public remote files to local machine.
   *
   * @command drupal:sync:public-files
   *
   * @aliases dsf sync:files drupal:sync:files
   *
   * @validateDrushConfig
   *
   * @todo Support multisite.
   */
  public function syncPublicFiles() {
    $remote_alias = '@' . $this->getConfigValue('drush.aliases.remote');
    $site_dir = $this->getConfigValue('site');

    $task = $this->taskDrush()
      ->alias('')
      ->uri('')
      ->drush('rsync')
      ->arg($remote_alias . ':%files/')
      ->arg($this->getConfigValue('docroot') . "/sites/$site_dir/files")
      ->option('exclude-paths', implode(':', $this->getConfigValue('sync.exclude-paths')));

    $result = $task->run();

    return $result;
  }

  /**
   * Copies private remote files to local machine.
   *
   * @command drupal:sync:private-files
   *
   * @aliases dspf
   *
   * @validateDrushConfig
   */
  public function syncPrivateFiles() {
    $remote_alias = '@' . $this->getConfigValue('drush.aliases.remote');
    $site_dir = $this->getConfigValue('site');
    $private_files_local_path = $this->getConfigValue('repo.root') . "/files-private/$site_dir";

    $task = $this->taskDrush()
      ->alias('')
      ->uri('')
      ->drush('rsync')
      ->arg($remote_alias . ':%private/')
      ->arg($private_files_local_path)
      ->option('exclude-paths', implode(':', $this->getConfigValue('sync.exclude-paths')));

    $result = $task->run();

    return $result;
  }





}
