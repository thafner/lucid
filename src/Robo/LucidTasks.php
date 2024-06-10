<?php

namespace Thafner\Lucid\Robo;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\Contract\IOAwareInterface;
use Robo\LoadAllTasks;
use Symfony\Component\Console\Input\ArrayInput;
use Thafner\Lucid\Robo\Common\IO;
use Thafner\Lucid\Robo\Config\ConfigAwareTrait;
use Thafner\Lucid\Robo\Tasks\LoadTasks;

/**
 * Base class for BLT Robo commands.
 */
class LucidTasks implements ConfigAwareInterface, LoggerAwareInterface, BuilderAwareInterface, IOAwareInterface, ContainerAwareInterface {

  use ContainerAwareTrait;
  use LoadAllTasks;
  use ConfigAwareTrait;
  use IO;
  use LoggerAwareTrait;
  use LoadTasks;

  /**
   * The depth of command invocations, used by invokeCommands().
   *
   * E.g., this would be 1 if invokeCommands() called a method that itself
   * called invokeCommands().
   *
   * @var int
   */
  protected $invokeDepth = 0;

  /**
   * Invokes an array of Symfony commands.
   *
   * @param array $commands
   *   An array of Symfony commands to invoke, e.g., 'tests:behat:run'.
   *
   * @throws \Exception
   */
  protected function invokeCommands(array $commands) {
    foreach ($commands as $key => $value) {
      if (is_numeric($key)) {
        $command = $value;
        $args = [];
      }
      else {
        $command = $key;
        $args = $value;
      }
      $this->invokeCommand($command, $args);
    }
  }

  /**
   * Invokes a single Symfony command.
   *
   * @param string $command_name
   *   The name of the command, e.g., 'tests:behat:run'.
   * @param array $args
   *   An array of arguments to pass to the command.
   *
   * @throws \Exception
   */
  protected function invokeCommand($command_name, array $args = []) {
    $this->invokeDepth++;


    /** @var \Thafner\Lucid\Robo\Application $application */
    $application = $this->getContainer()->get('application');
    $command = $application->find($command_name);

    // Build a new input object that inherits options from parent command.
    if ($this->input()->hasParameterOption('--environment')) {
      $args['--environment'] = $this->input()->getParameterOption('--environment');
    }
    if ($this->input()->hasParameterOption('--site')) {
      $args['--site'] = $this->input()->getParameterOption('--site');
    }
    $input = new ArrayInput($args);
    $input->setInteractive($this->input()->isInteractive());

    // Now run the command.
    $prefix = str_repeat(">", $this->invokeDepth);
    $this->output->writeln("<comment>$prefix $command_name</comment>");
    $exit_code = $application->runCommand($command, $input, $this->output());
    $this->invokeDepth--;

    // The application will catch any exceptions thrown in the executed
    // command. We must check the exit code and throw our own exception. This
    // obviates the need to check the exit code of every invoked command.
    if ($exit_code) {
      $this->output->writeln("The command failed. This often indicates a problem with your configuration. Review the command output above for more detailed errors, and consider re-running with verbose output for more information.");
      throw new \Exception("Command `$command_name {$input->__toString()}` exited with code $exit_code.");
    }
  }

}
