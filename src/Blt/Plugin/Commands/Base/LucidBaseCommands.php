<?php

namespace Thafner\Lucid\Blt\Plugin\Commands\Base;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Robo\Exceptions\BltException;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Defines commands in the "custom" namespace.
 */
class LucidBaseCommands extends BltTasks {

  /**
   * Print "Hello world!" to the console.
   *
   * @command lucid:base:composer:install
   * @description This is an example command.
   */
  public function composerInstall(): void {
    $this->taskComposerInstall()
      ->dev()
      ->noInteraction()
      ->run();
  }

  /**
   * Imports configuration from the config directory according to cm.strategy.
   *
   * @replace-command drupal:config:import
   *
   * @validateDrushConfig
   *
   * @throws \Robo\Exception\TaskException
   * @throws \Exception
   */
  public function import() {
    $strategy = $this->getConfigValue('cm.strategy');

    if ($strategy === 'none') {
      $this->logger->warning("CM strategy set to none in blt.yml. BLT will NOT import configuration.");
      // Still clear caches to regenerate frontend assets and such.
      return $this->taskDrush()->drush("cache-rebuild")->run();
    }

    $this->logConfig($this->getConfigValue('cm'), 'cm');
    $task = $this->taskDrush();

    $this->invokeHook('pre-config-import');

    // If using core-only or config-split strategies, first check to see if
    // required config is exported.
    if (in_array($strategy, ['core-only', 'config-split'])) {
      $test = $this->getConfigValue("cm.core.dirs.sync.path");
      $core_config_file = $this->getConfigValue("cm.core.dirs.sync.path") . '/core.extension.yml';

      if (!file_exists($core_config_file)) {
        $this->logger->warning("BLT will NOT import configuration, $core_config_file was not found.");
        // This is not considered a failure.
        return 0;
      }
    }

    // If exported site UUID does not match site active site UUID, set active
    // to equal exported.
    // @see https://www.drupal.org/project/drupal/issues/1613424
    $exported_site_uuid = $this->getExportedSiteUuid();
    if ($exported_site_uuid) {
      $task->drush("config:set system.site uuid $exported_site_uuid");
    }

    switch ($strategy) {
      case 'core-only':
        $this->importCoreOnly($task);
        break;

      case 'config-split':
        // Drush task explicitly to turn on config_split and check if it was
        // successfully enabled. Otherwise default to core-only.
        $check_task = $this->taskDrush();
        $check_task->drush("pm-enable")->arg('config_split');
        $result = $check_task->run();
        if (!$result->wasSuccessful()) {
          $this->logger->warning('Import strategy is config-split, but the config_split module does not exist. Falling back to core-only.');
          $this->importCoreOnly($task);
          break;
        }
        $this->importConfigSplit($task);
        break;
    }

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
   * Update current database to reflect the state of the Drupal file system.
   *
   * @command lucid:base:refresh
   * @aliases lucid:refresh lbr lr
   *
   * @throws \Robo\Exception\TaskException
   * @throws \Acquia\Blt\Robo\Exceptions\BltException
   */
  public function refresh(): void {
    $this->invokeCommands([
      'lucid:base:composer:install',
      'lucid:sync:db',
      'lucid:drupal:update',
      'lucid:theme:frontend'
    ]);
  }



}
