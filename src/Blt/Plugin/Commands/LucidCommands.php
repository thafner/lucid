<?php

namespace Thafner\Lucid\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Defines commands in the "custom" namespace.
 */
class LucidCommands extends BltTasks {

  /**
   * Print "Hello world!" to the console.
   *
   * @command lucid:root
   * @description This is an example command.
   */
  public function hello() {
    $this->say("Hello world!");
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

}
