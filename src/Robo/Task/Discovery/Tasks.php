<?php

namespace Thafner\Lucid\Robo\Task\Discovery;

trait Tasks
{
  /**
   * @param string $command
   * @param array $alternatives
   *
   * @return \Thafner\Lucid\Robo\Task\Discovery\Alternatives|\Robo\Collection\CollectionBuilder
   */
  protected function taskAlternatives(string $command, array $alternatives)
  {
    return $this->task(Alternatives::class, $command, $alternatives);
  }
}