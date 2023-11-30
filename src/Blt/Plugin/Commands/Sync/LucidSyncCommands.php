<?php

namespace Thafner\Lucid\Blt\Plugin\Commands\Sync;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Robo\Exceptions\BltException;
//use AsyncAws\S3\S3Client;
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
    $io = new SymfonyStyle($input, $output);
    $io->title('Syncing database from S3 bucket.');
    //
    //    $awsConfigDirPath = getenv('HOME') . '/.aws';
    //    $awsConfigFilePath = "$awsConfigDirPath/credentials";
    //    if (!is_dir($awsConfigDirPath) || !file_exists($awsConfigFilePath)) {
    //      $result = $this->configureAwsCredentials($awsConfigDirPath, $awsConfigFilePath);
    //      if ($result->wasCancelled()) {
    //        return Result::cancelled();
    //      }
    //    }

    //    $s3 = new S3Client([
    //      'region' => $this->s3RegionForSite($siteName),
    //    ]);
    //    $objects = $s3->listObjectsV2($this->s3BucketRequestConfig($siteName));
    //    $objects = iterator_to_array($objects);
    //    if (count($objects) == 0) {
    //      throw new TaskException($this, "No database dumps found for '$siteName'.");
    //    }
    //    // Ensure objects are sorted by last modified date.
    //    usort(
    //      array: $objects,
    //      /** @var \AsyncAws\S3\ValueObject\AwsObject $a */
    //      callback: fn($a, $b) => $a->getLastModified()->getTimestamp() <=> $b->getLastModified()->getTimestamp(),
    //        );
    //    /** @var \AsyncAws\S3\ValueObject\AwsObject $latestDatabaseDump */
    //    $latestDatabaseDump = array_pop(array: $objects);
    //    $dbFilename = $latestDatabaseDump->getKey();
    //    $downloadFileName = $this->sanitizeFileNameForWindows($dbFilename);
    //
    //    if (file_exists($downloadFileName)) {
    //      $this->say("Skipping download. Latest database dump file exists >>> $downloadFileName");
    //    } else {
    //      $result = $s3->getObject([
    //        'Bucket' => $this->s3BucketForSite($siteName),
    //        'Key' => $dbFilename,
    //      ]);
    //      stream_copy_to_stream(
    //        from: $result->getBody()->getContentAsResource(),
    //        to: fopen($downloadFileName, 'wb'),
    //            );
    //      $this->say("Database dump file downloaded >>> $downloadFileName");
    //    }
    //    return $downloadFileName;
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
   * @command drupal:sync:default:site
   * @aliases ds drupal:sync drupal:sync:default sync sync:refresh
   */
  public function sync(array $options = [
    'sync-public-files' => FALSE,
    'sync-private-files' => FALSE,
  ]) {
    $commands = $this->getConfigValue('sync.commands');
    if ($options['sync-public-files'] || $this->getConfigValue('sync.public-files')) {
      $commands[] = 'drupal:sync:public-files';
    }
    if ($options['sync-private-files'] || $this->getConfigValue('sync.private-files')) {
      $commands[] = 'drupal:sync:private-files';
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
   * Iteratively copies remote db to local db for each multisite.
   *
   * @command drupal:sync:db:all-sites
   * @aliases dsba sync:all:db
   */
  public function syncDbAllSites() {
    $exit_code = 0;
    $multisites = $this->getConfigValue('multisites');

    $this->printSyncMap($multisites);
    $continue = $this->confirm("Continue?");
    if (!$continue) {
      return $exit_code;
    }

    foreach ($multisites as $multisite) {
      $this->say("Refreshing site <comment>$multisite</comment>...");
      $this->switchSiteContext($multisite);
      $result = $this->syncDb();
      if (!$result->wasSuccessful()) {
        $this->logger->error("Could not sync database for site <comment>$multisite</comment>.");
        throw new BltException("Could not sync database.");
      }
    }

    return $exit_code;
  }



  /**
   * Print sync map.
   *
   * @param array $multisites
   *   Array of multisites.
   */
  protected function printSyncMap(array $multisites) {
    $this->say("Sync operations be performed for the following drush aliases:");
    $sync_map = [];
    foreach ($multisites as $multisite) {
      $this->switchSiteContext($multisite);
      $sync_map[$multisite]['local'] = '@' . $this->getConfigValue('drush.aliases.local');
      $sync_map[$multisite]['remote'] = '@' . $this->getConfigValue('drush.aliases.remote');
      $this->say("  * <comment>" . $sync_map[$multisite]['remote'] . "</comment> => <comment>" . $sync_map[$multisite]['local'] . "</comment>");
    }
    $this->say("To modify the set of aliases for syncing, set the values for drush.aliases.local and drush.aliases.remote in docroot/sites/[site]/blt.yml");
  }

}
