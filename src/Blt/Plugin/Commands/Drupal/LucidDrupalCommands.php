<?php

namespace Thafner\Lucid\Blt\Plugin\Commands\Drupal;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Robo\Commands\Drupal\ConfigCommand;
use Acquia\Blt\Robo\Exceptions\BltException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines commands in the "drupal" namespace.
 */
class LucidDrupalCommands extends BltTasks {
  /**
   * Imports configuration from the config directory.
   *
   * @command lucid:drupal:config:import
   * @aliases lucid:config:import
   *
   * @validateDrushConfig
   *
   * @throws \Robo\Exception\TaskException
   * @throws \Exception
   */
  public function import(InputInterface $input, OutputInterface $output) {
    $task = $this->taskDrush();

    $this->invokeHook('pre-config-import');

    // If exported site UUID does not match site active site UUID, set active
    // to equal exported.
    // @see https://www.drupal.org/project/drupal/issues/1613424
    $exported_site_uuid = $this->getExportedSiteUuid();
    if ($exported_site_uuid) {
      $task->drush("config:set system.site uuid $exported_site_uuid");
    }

    $task->drush("config-import");
    // Runs a second import to ensure splits are
    // both defined and imported.
    $task->drush("config-import");
    $task->drush("cache-rebuild");
    $result = $task->run();
    if (!$result->wasSuccessful()) {
      throw new BltException("Failed to import configuration!");
    }

    $this->lucidCheckConfigOverrides($input, $output);

    $result = $this->invokeHook('post-config-import');

    return $result;
  }

  /**
   * Sync remote database and update configuration.
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
      'lucid:drupal:update',
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
   * @aliases lucid:update
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
    $this->invokeCommand('lucid:drupal:config:import');
  }

  /**
   * Checks whether core config is overridden.
   *
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   * @throws \Robo\Exception\TaskException
   */
  protected function lucidCheckConfigOverrides(InputInterface $input, OutputInterface $output): void {
    if (!$this->getConfigValue('cm.allow-overrides') && !$this->getInspector()->isActiveConfigIdentical()) {
      $task = $this->taskDrush()
        ->drush("config-status");
      $result = $task->run();
      if (!$result->wasSuccessful()) {
        $io = new SymfonyStyle($input, $output);
        $io->warning("Configuration in the database MAY NOT match configuration on disk.");
      }
    }
  }

  /**
   * Returns the site UUID stored in exported configuration.
   *
   * @return null
   *   Mixed.
   */
  protected function getExportedSiteUuid() {
    $site_config_file = $this->getConfigValue('docroot') . '/' . $this->getConfigValue("cm.core.dirs.sync.path") . '/system.site.yml';
    if (file_exists($site_config_file)) {
      $site_config = Yaml::parseFile($site_config_file);
      return $site_config['uuid'];
    }

    return NULL;
  }
}
