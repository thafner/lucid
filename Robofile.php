<?php

use Robo\Symfony\ConsoleIO;
use Robo\Tasks;

class RoboFile extends Tasks
{
  use \Boedah\Robo\Task\Drush\loadTasks;
  use \Robo\Common\TaskIO;

  function hello(ConsoleIO $io)
  {
    $io->say("Importing Drupal Configuration.");
    $task = $this->taskDrushStack();
    $task->exec('config:import');
    $task->run();
  }
}