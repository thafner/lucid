<?php

namespace Lucid\Robo\Tasks;

use Lucid\Robo\Tasks\Alternatives;

trait Tasks
{
    /**
     * @param string $command
     * @param array $alternatives
     *
     * @return \Lucid\Robo\Tasks\Alternatives|\Robo\Collection\CollectionBuilder
     */
    protected function taskAlternatives(string $command, array $alternatives)
    {
        return $this->task(Alternatives::class, $command, $alternatives);
    }
}
