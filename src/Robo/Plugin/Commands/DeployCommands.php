<?php

namespace Thafner\Lucid\Robo\Plugin\Commands;

use Robo\Result;
use Robo\Tasks;
use Thafner\Lucid\Robo\Plugin\Traits\SlackNotifierTrait;

/**
 * Robo commands related to continuous integration.
 */
class DeployCommands extends Tasks
{
    use SlackNotifierTrait;

    /**
     * RoboFile constructor.
     */
    public function __construct()
    {
        // Treat this command like bash -e and exit as soon as there's a failure.
        $this->stopOnFail();
    }

    /**
     * Run a Drupal 8/9 deployment.
     *
     * @param string $appDirPath
     *   The app directory path.
     * @param string $siteName
     *   The Drupal site shortname. Optional.
     * @param string $docroot
     *   The Drupal document root directory. Optional.
     *
     */
    public function build(
        string $appDirPath,
        string $siteName = 'default',
        string $docroot = 'web',
    ): Result {
        $result = $this->taskExecStack()
            ->dir("$appDirPath/$docroot/sites/$siteName")
            ->exec("$appDirPath/vendor/bin/drush deploy --yes")
            // Import the latest configuration again. This includes the latest
            // configuration_split configuration. Importing this twice ensures
            // that the latter command enables and disables modules based upon
            // the most up-to-date configuration. Additional information and
            // discussion can be found here:
            // https://github.com/drush-ops/drush/issues/2449#issuecomment-708655673
            ->exec("$appDirPath/vendor/bin/drush config:import --yes")
            ->run();
        return $result;
    }
}
