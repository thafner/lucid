<?php

namespace Thafner\Lucid\Blt\Plugin\Commands\Sync;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Robo\Exceptions\BltException;
use AsyncAws\S3\S3Client;
use Robo\Exception\TaskException;
use Robo\Result;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Defines commands in the "sync" namespace.
 */
class LucidSyncCommands extends BltTasks {

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
    $io = new SymfonyStyle($input, $output);
    $io->title('Syncing database from S3 bucket for ' . $name . '.');



    $awsConfigDirPath = getenv('HOME') . '/.aws';
    $awsConfigFilePath = "$awsConfigDirPath/credentials";
    if (!is_dir($awsConfigDirPath) || !file_exists($awsConfigFilePath)) {
      $result = $this->configureAwsCredentials($awsConfigDirPath, $awsConfigFilePath, $input, $output);
      if ($result->wasCancelled()) {
        return Result::cancelled();
      }
    }

    $s3 = new S3Client([
      'region' => 'us-east-2',
    ]);

    $downloadFileName = 'cclerkdevDrupal9.sql.gz';

    if (file_exists($downloadFileName)) {
      $this->say("Skipping download. Latest database dump file exists >>> $downloadFileName");
    } else {
      $bucket = $this->getConfigValue('lucid.database.s3_bucket');
      $key = $this->getConfigValue('lucid.database.s3_key_prefix_string');
      $result = $s3->getObject([
        'Bucket' => $bucket,
        'Key' => $key,
      ]);
      $metadata = json_decode($result->getBody()->getContentAsString());
      $this->say("Database dump file downloaded >>> $metadata");
    }
    return $downloadFileName;

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

  /**
   * Configure AWS credentials.
   *
   * @param string $awsConfigDirPath
   *   Path to the AWS configuration directory.
   * @param string $awsConfigFilePath
   *   Path to the AWS configuration file.
   */
  protected function configureAwsCredentials(string $awsConfigDirPath, string $awsConfigFilePath, InputInterface $input, OutputInterface $output): Result
  {
    $io = new SymfonyStyle($input, $output);
    $yes = $io->confirm('AWS S3 credentials not detected. Do you wish to configure them?');
    if (!$yes) {
      return Result::cancelled();
    }

    if (!is_dir($awsConfigDirPath)) {
      $this->_mkdir($awsConfigDirPath);
    }

    if (!file_exists($awsConfigFilePath)) {
      $this->_touch($awsConfigFilePath);
    }

    $awsKeyId = $this->ask("AWS Access Key ID:");
    $awsSecretKey = $this->askHidden("AWS Secret Access Key:");
    return $this->taskWriteToFile($awsConfigFilePath)
      ->line('[default]')
      ->line("aws_access_key_id = $awsKeyId")
      ->line("aws_secret_access_key = $awsSecretKey")
      ->run();
  }



}
