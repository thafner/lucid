<?php

namespace Thafner\Lucid\Blt\Plugin\Traits;

use AsyncAws\S3\S3Client;
use AsyncAws\Core\Exception\Exception;
use Acquia\Blt\Robo\Exceptions\BltException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Robo\Result;
use Robo\ResultData;

trait DatabaseTrait {

  public function databaseDownload(string $site_name, string $bucket, string $key, InputInterface $input, OutputInterface $output): string|Result|ResultData
  {
    $io = new SymfonyStyle($input, $output);
    $io->section('Syncing database');
    $awsConfigDirPath = getenv('HOME') . '/.aws';
    $awsConfigFilePath = "$awsConfigDirPath/credentials";
    if (!is_dir($awsConfigDirPath) || !file_exists($awsConfigFilePath))
    {
      $creds_result = $this->configureAwsCredentials($awsConfigDirPath, $awsConfigFilePath, $input, $output);
      if ($creds_result->wasCancelled())
      {
        return Result::cancelled();
      }
    }

    $s3 = new S3Client([
      'region' => 'us-east-2',
    ]);

    $downloadFileName = 'cclerkdevDrupal9.sql.gz';

    if (file_exists($downloadFileName)) {
      $io->text([
        "Skipping download.",
        "Latest database dump file exists.",
        $downloadFileName,
      ]);
    } else {
      try {
        $result = $s3->getObject([
          'Bucket' => $bucket,
          'Key' => $key,
        ]);
      } catch (Exception $e) {
        $io->error([
          "There was an error downloading the database.",
          $e->getCode(),
          $e->getMessage(),
        ]);
        return 0;
      }
      $fp = fopen(getenv('LANDO_MOUNT'). '/' . $downloadFileName, 'wb');
      stream_copy_to_stream($result->getBody()->getContentAsResource(), $fp);
      $io->success("Database successfully downloaded.");
    }
    return $downloadFileName;
  }

  /**
   * Configure AWS credentials.
   *
   * @param string $awsConfigDirPath
   *   Path to the AWS configuration directory.
   * @param string $awsConfigFilePath
   *   Path to the AWS configuration file.
   */
  protected function configureAwsCredentials(string $awsConfigDirPath, string $awsConfigFilePath, InputInterface $input, OutputInterface $output): Result|ResultData
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