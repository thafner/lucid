<?php

namespace Thafner\Lucid\Robo\Plugin\Commands;

use DrupalFinder\DrupalFinder;
use Robo\Exception\TaskException;
use Robo\Result;
use Robo\ResultData;
use Robo\Tasks;
use Symfony\Component\Yaml\Yaml;
use Thafner\Lucid\Robo\Plugin\Enums\LocalDevEnvironmentTypes;
use Thafner\Lucid\Robo\Plugin\Traits\DatabaseDownloadTrait;
use Thafner\Lucid\Robo\Plugin\Traits\DrupalVersionTrait;
use Thafner\Lucid\Robo\Plugin\Traits\SitesConfigTrait;
use Thafner\Lucid\Robo\Task\Discovery\Alternatives;

/**
 * Robo commands related to changing development modes.
 */
class DevelopmentModeCommands extends Tasks
{
    use DatabaseDownloadTrait;
    use SitesConfigTrait;
    use DrupalVersionTrait;

    /**
     * Drupal root directory.
     *
     * @var string
     */
    protected string $drupalRoot;

    /**
     * Composer vendor directory.
     *
     * @var string
     */
    protected string $vendorDirectory;

    /**
     * Path to front-end development services path.
     *
     * @var string
     */
    protected string $devServicesPath;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        // Treat this command like bash -e and exit as soon as there's a failure.
        $this->stopOnFail();

        // Find Drupal root path.
        $drupalFinder = new DrupalFinder();
        $drupalFinder->locateRoot(getcwd());
        $this->drupalRoot = $drupalFinder->getDrupalRoot();
        $this->vendorDirectory = $drupalFinder->getVendorDir();
        $this->devServicesPath = "$this->drupalRoot/sites/fe.development.services.yml";
    }

    /**
     * Refreshes a development environment.
     *
     * Completely refreshes a development environment including running 'composer install', downloading
     * a database dump, importing it, running deployment commands, disabling front-end caches, and
     * providing a login link.
     *
     * @param string $siteName
     *   The Drupal site name.
     * @option db
     *   Provide a database dump instead of relying on the latest available.
     * @option environment-type
     *   Specify alternative (supported) environment type. See LocalDevEnvironmentTypes enum.
     *
     * @aliases magic
     */
    public function devRefresh(
        string $siteName = 'default',
        array $options = ['db' => '', 'environment-type' => 'ddev'],
    ): Result {
        ['db' => $dbPath, 'environment-type' => $environmentType] = $options;
        return $this->devRefreshDrupal(
            environmentType: LocalDevEnvironmentTypes::from($environmentType),
            siteName: $siteName,
            databasePath: $dbPath,
        );
    }

//    /**
//     * Refresh database on Tugboat.
//     */
//    public function databaseRefreshTugboat(): ResultData
//    {
//        $this->io()->title('refresh tugboat databases.');
//        $resultData = new ResultData();
//
//        foreach (array_keys($this->getAllSitesConfig()) as $siteName) {
//            $dbPath = '';
//            try {
//                $dbPath = $this->databaseDownload($siteName);
//            } catch (TaskException $e) {
//                $this->yell("$siteName: No database configured. Download/import skipped.");
//                $resultData->append($e->getMessage());
//                // @todo: Should we run a site-install by default?
//                continue;
//            }
//            if (!is_string($dbPath) || $dbPath === '') {
//                $this->yell("'$siteName' database path not found.");
//                $resultData->append("'$siteName' database path not found.");
//                continue;
//            }
//            $dbName = $siteName === 'default' ? 'tugboat' : $siteName;
//            $taskResult = $this->task(Alternatives::class, 'mariadb', ['mysql'])->run();
//            if (!$taskResult->wasSuccessful()) {
//                $resultData->append($taskResult);
//                continue;
//            }
//            $dbDriver = $taskResult->getData()['path'];
//            $taskResult = $this->taskExec($dbDriver)
//                ->option('-h', 'mariadb')
//                ->option('-u', 'tugboat')
//                ->option('-ptugboat')
//                ->option('-e', "drop database if exists $dbName; create database $dbName;")
//                ->run();
//            $resultData->append($taskResult);
//            $this->io()->section("import $siteName database.");
//            $taskResult = $this->taskExec("zcat $dbPath | $dbDriver -h mariadb -u tugboat -ptugboat $dbName")
//                ->run();
//            $resultData->append($taskResult);
//            $taskResult = $this->taskExec('rm')->args($dbPath)->run();
//            $resultData->append($taskResult);
//
//            if (!$this->drupalVersionIsD7($this->drupalRoot)) {
//                $taskResult = $this->taskExec("$this->vendorDirectory/bin/drush")
//                    ->arg('cache:rebuild')
//                    ->dir("$this->drupalRoot/sites/$siteName")
//                    ->run();
//                $resultData->append($taskResult);
//            }
//        }
//
//        return $resultData;
//    }
}
