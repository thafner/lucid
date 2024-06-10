<?php

use Robo\Symfony\ConsoleIO;
use Robo\Tasks;

class RoboFile extends Tasks
{
  use \Robo\Common\TaskIO;

  function hello(ConsoleIO $io)
  {
    $io->say("Importing Drupal Configuration.");
    $task = $this->task();
    $task->exec('config:import');
    $task->run();
  }
}