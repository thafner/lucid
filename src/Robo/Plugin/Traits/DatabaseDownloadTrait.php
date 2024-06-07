<?php

namespace Thafner\Lucid\Robo\Plugin\Traits;

use AsyncAws\S3\S3Client;
use Robo\Exception\TaskException;
use Robo\Result;
use Robo\ResultData;
use AsyncAws\Core\Exception\Exception;

/**
 * Trait to provide database download functionality to Robo commands.
 */
trait DatabaseDownloadTrait
{
  public function databaseDownload(string $site_name, string $bucket, string $directory, string $file): string|Result|ResultData
  {
    $this->io()->title('database download.');
    $this->io()->say('Syncing database');
    $awsConfigDirPath = getenv('HOME') . '/.aws';
    $awsConfigFilePath = "$awsConfigDirPath/credentials";
    if (!is_dir($awsConfigDirPath) || !file_exists($awsConfigFilePath))
    {
      $creds_result = $this->configureAwsCredentials($awsConfigDirPath, $awsConfigFilePath);
      if ($creds_result->wasCancelled())
      {
        return Result::cancelled();
      }
    }

    $s3 = new S3Client([
      'region' => 'us-east-2',
    ]);

    $downloadFileLocation = dirname(DRUPAL_ROOT) . '/' . $file;
    // When drush operates against a gzipped file the file is unzipped.
    $downloadFileLocationOpened = dirname(DRUPAL_ROOT) . '/' . rtrim($file, '.gz');

    if (file_exists($downloadFileLocation) || file_exists($downloadFileLocationOpened)) {
      $this->io()->say("Skipping download. Latest database dump file exists.");

      if (file_exists($downloadFileLocation)) {
        return $downloadFileLocation;
      }
      elseif (file_exists($downloadFileLocationOpened)) {
        return $downloadFileLocationOpened;
      }
    } else {
      try {
        $result = $s3->getObject([
          'Bucket' => $bucket,
          'Key' => $directory . $file,
        ]);
      } catch (Exception $e) {
        $this->io()->error([
          "There was an error downloading the database.",
          $e->getCode(),
          $e->getMessage(),
        ]);
        return 0;
      }
      $fp = fopen($downloadFileLocation, 'wb');
      stream_copy_to_stream($result->getBody()->getContentAsResource(), $fp);
      $this->io()->success("Database successfully downloaded.");
    }

    return $downloadFileLocation;
  }

  /**
   * Configure AWS credentials.
   *
   * @param string $awsConfigDirPath
   *   Path to the AWS configuration directory.
   * @param string $awsConfigFilePath
   *   Path to the AWS configuration file.
   */
  protected function configureAwsCredentials(string $awsConfigDirPath, string $awsConfigFilePath): Result|ResultData
  {
    $yes = $this->io()->confirm('AWS S3 credentials not detected. Do you wish to configure them?');
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

//    /**
//     * Default S3 region.
//     *
//     * @var string
//     */
//    protected $s3DefaultRegion = 'us-east-2';
//
//    /**
//     * Download the latest database dump for the site.
//     *
//     * @param string $siteName
//     *   The site name.
//     *
//     * @aliases dbdl
//     *
//     * @throws \Robo\Exception\TaskException
//     */
//    public function LucidDatabaseDownload(string $siteName = 'default'): string|\Robo\Result
//    {
//        $this->io()->title('database download.');
//
//        $awsConfigDirPath = getenv('HOME') . '/.aws';
//        $awsConfigFilePath = "$awsConfigDirPath/credentials";
//        if (!is_dir($awsConfigDirPath) || !file_exists($awsConfigFilePath)) {
//            $result = $this->configureAwsCredentials($awsConfigDirPath, $awsConfigFilePath);
//            if ($result->wasCancelled()) {
//                return Result::cancelled();
//            }
//        }
//
//        $s3 = new S3Client([
//            'region' => $this->s3RegionForSite($siteName),
//        ]);
//        $objects = $s3->listObjectsV2($this->s3BucketRequestConfig($siteName));
//        $objects = iterator_to_array($objects);
//        if (count($objects) == 0) {
//            throw new TaskException($this, "No database dumps found for '$siteName'.");
//        }
//        // Ensure objects are sorted by last modified date.
//        usort(
//            array: $objects,
//            /** @var \AsyncAws\S3\ValueObject\AwsObject $a */
//            callback: fn($a, $b) => $a->getLastModified()->getTimestamp() <=> $b->getLastModified()->getTimestamp(),
//        );
//        /** @var \AsyncAws\S3\ValueObject\AwsObject $latestDatabaseDump */
//        $latestDatabaseDump = array_pop(array: $objects);
//        $dbFilename = $latestDatabaseDump->getKey();
//        $downloadFileName = $this->sanitizeFileNameForWindows($dbFilename);
//
//        if (file_exists($downloadFileName)) {
//            $this->say("Skipping download. Latest database dump file exists >>> $downloadFileName");
//        } else {
//            $result = $s3->getObject([
//                'Bucket' => $this->s3BucketForSite($siteName),
//                'Key' => $dbFilename,
//            ]);
//            stream_copy_to_stream(
//                from: $result->getBody()->getContentAsResource(),
//                to: fopen($downloadFileName, 'wb'),
//            );
//            $this->say("Database dump file downloaded >>> $downloadFileName");
//        }
//        return $downloadFileName;
//    }
//
//    /**
//     * Configure AWS credentials.
//     *
//     * @param string $awsConfigDirPath
//     *   Path to the AWS configuration directory.
//     * @param string $awsConfigFilePath
//     *   Path to the AWS configuration file.
//     */
//    protected function LucidConfigureAwsCredentials(string $awsConfigDirPath, string $awsConfigFilePath): Result
//    {
//        $yes = $this->io()->confirm('AWS S3 credentials not detected. Do you wish to configure them?');
//        if (!$yes) {
//            return Result::cancelled();
//        }
//
//        if (!is_dir($awsConfigDirPath)) {
//            $this->_mkdir($awsConfigDirPath);
//        }
//
//        if (!file_exists($awsConfigFilePath)) {
//            $this->_touch($awsConfigFilePath);
//        }
//
//        $awsKeyId = $this->ask("AWS Access Key ID:");
//        $awsSecretKey = $this->askHidden("AWS Secret Access Key:");
//        return $this->taskWriteToFile($awsConfigFilePath)
//            ->line('[default]')
//            ->line("aws_access_key_id = $awsKeyId")
//            ->line("aws_secret_access_key = $awsSecretKey")
//            ->run();
//    }
//
//    /**
//     * Build S3 request configuration from sites config.
//     *
//     * @param string $siteName
//     *   The site name.
//     */
//    protected function s3BucketRequestConfig(string $siteName): array
//    {
//        $s3ConfigArray = ['Bucket' => $this->s3BucketForSite($siteName)];
//        try {
//            $s3KeyPrefix = $this->getSiteConfigItem('database_s3_key_prefix_string', $siteName);
//            $this->say("'$siteName' S3 Key prefix: '$s3KeyPrefix'");
//            $s3ConfigArray['Prefix'] = $s3KeyPrefix;
//        } catch (TaskException) {
//            $this->say("No S3 Key prefix found for $siteName.");
//        }
//        return $s3ConfigArray;
//    }
//
//    /**
//     * Get S3 Bucket for site.
//     *
//     * @param string $siteName
//     *   The site name.
//     *
//     * @throws \Robo\Exception\TaskException
//     */
//    protected function s3BucketForSite(string $siteName): string
//    {
//        if (!is_string($bucket = $this->getSiteConfigItem('database_s3_bucket', $siteName))) {
//            throw new TaskException($this, "database_s3_bucket value not set for '$siteName'.");
//        }
//        $this->say("'$siteName' S3 bucket: $bucket");
//        return $bucket;
//    }
//
//    /**
//     * Get S3 region for site.
//     *
//     * @param string $siteName
//     *   The site name.
//     */
//    protected function s3RegionForSite(string $siteName): string
//    {
//        try {
//            $region = $this->getSiteConfigItem('database_s3_region', $siteName);
//            $this->say("'$siteName' database_s3_region set to $region.");
//        } catch (TaskException) {
//            // Set default region if one is not set.
//            $defaultRegion = $this->s3DefaultRegion;
//            $this->say("'$siteName' database_s3_region not set. Defaulting to $defaultRegion.");
//            $region = $defaultRegion;
//        }
//        return $region;
//    }
//
//    /**
//     * Sanitizes a file name for the Windows file system.
//     *
//     * @param string $fileName
//     *   The file name to sanitize.
//     */
//    public function sanitizeFileNameForWindows(string $fileName): string
//    {
//        if (PHP_OS_FAMILY === 'Windows') {
//            $fileName = preg_replace(
//                '~
//                # File system reserved characters.
//                # @link https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
//                [<>:"/\\\|?*]|
//                # Control characters.
//                # @link https://docs.microsoft.com/en-gb/windows/win32/fileio/naming-a-file
//                [\x00-\x1F]|
//                # Non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN.
//                [\x7F\xA0\xAD]
//                ~x',
//                '_',
//                $fileName
//            );
//        }
//        return $fileName;
//    }
//
//    /**
//     * Delete the specified database.
//     */
//    protected function deleteDatabase(string $dbPath): Result
//    {
//        $this->say("Deleting $dbPath");
//        return $this->taskExec('rm')->args($dbPath)->run();
//    }
}
