<?php

namespace Thafner\Lucid\Blt\Plugin\Commands\Drupal;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Robo\Exceptions\BltException;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines commands in the "drupal" namespace.
 */
class LucidDrupalCommands extends BltTasks {

  /**
   * Import Drupal configuration.
   *
   * @command lucid:drupal:config:import
   * @description This is an example command.
   */
  public function import() {
    $this->logConfig($this->getConfigValue('cm'), 'cm');
    $task = $this->taskDrush();

    $this->invokeHook('pre-config-import');

    $core_config_file = $this->getConfigValue('docroot') . '/' . $this->getConfigValue("cm.core.dirs.sync.path") . '/core.extension.yml';

    if (!file_exists($core_config_file)) {
      $this->logger->warning("BLT will NOT import configuration, $core_config_file was not found.");
      // This is not considered a failure.
      return 0;
    }

    // If exported site UUID does not match site active site UUID, set active
    // to equal exported.
    // @see https://www.drupal.org/project/drupal/issues/1613424
    $exported_site_uuid = $this->getExportedSiteUuid();
    if ($exported_site_uuid) {
      $task->drush("config:set system.site uuid $exported_site_uuid");
    }

    // Drush task explicitly to turn on config_split and check if it was
    // successfully enabled. Otherwise default to core-only.
    $check_task = $this->taskDrush();
    $check_task->drush("pm-enable")->arg('config_split');
    $result = $check_task->run();
    if (!$result->wasSuccessful()) {
      $this->logger->warning("BLT will NOT import configuration, $core_config_file was not found.");
    }
    $this->importConfig($task);

    $task->drush("cache-rebuild");
    $result = $task->run();
    if (!$result->wasSuccessful()) {
      throw new BltException("Failed to import configuration!");
    }

    $this->checkConfigOverrides();

    $result = $this->invokeHook('post-config-import');

    return $result;
  }

  /**
   * This will be called before the `custom:hello` command is executed.
   *
   * @hook command-event lucid:hello
   */
  public function preExampleHello(ConsoleCommandEvent $event) {
    $command = $event->getCommand();
    $this->say("preCommandMessage hook: The {$command->getName()} command is about to run!");
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

  /**
   * Import configuration using config_split module.
   *
   * @param mixed $task
   *   Drush task.
   */
  protected function importConfig($task): void {
    $task->drush("config-import");
    // Runs a second import to ensure splits are
    // both defined and imported.
    $task->drush("config-import");
  }

  /**
   * Checks whether core config is overridden.
   *
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   * @throws \Robo\Exception\TaskException
   */
  protected function checkConfigOverrides(): void {
    if (!$this->getConfigValue('cm.allow-overrides') && !$this->getInspector()->isActiveConfigIdentical()) {
      $task = $this->taskDrush()
        ->stopOnFail()
        ->drush("config-status");
      $result = $task->run();
      if (!$result->wasSuccessful()) {
        throw new BltException("Unable to determine configuration status.");
      }
      throw new BltException("Configuration in the database does not match configuration on disk. This indicates that your configuration on disk needs attention. Please read https://support.acquia.com/hc/en-us/articles/360034687394--Configuration-in-the-database-does-not-match-configuration-on-disk-when-using-BLT");
    }
  }

}
