<?php

namespace Thafner\Lucid\Robo\Tasks;

/**
 * Load custom Robo tasks.
 */
trait LoadTasks {

  /**
   * Task drush.
   *
   * @return \Thafner\Lucid\Robo\Tasks\DrushTask
   *   Drush task.
   */
  protected function taskDrush() {
    /** @var \Thafner\Lucid\Robo\Tasks\DrushTask $task */
    $task = $this->task(DrushTask::class);
    /** @var \Symfony\Component\Console\Output\OutputInterface $output */
    $output = $this->output();
    $task->setVerbosityThreshold($output->getVerbosity());

    return $task;
  }

}
