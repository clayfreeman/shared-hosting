<?php
  /**
   * This file defines a command `restart-services` used to restart all
   * associated services.
   *
   * @copyright  Copyright 2017 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  namespace SharedHosting\Commands;

  use Symfony\Component\Console\Command\Command;
  use Symfony\Component\Console\Input\InputArgument;
  use Symfony\Component\Console\Input\InputInterface;
  use Symfony\Component\Console\Output\OutputInterface;
  use Symfony\Component\Console\Style\SymfonyStyle;

  use SharedHosting\Services\FPMPool;
  use SharedHosting\Services\NGINX;
  use SharedHosting\Services\OpenDKIM;
  use SharedHosting\Utility\Transaction;

  /**
   * The `restart-services` command restarts all associated services.
   */
  class RestartServices extends Command {
    protected $db = null;

    /**
     * Configures the command using the Symfony console API with its name,
     * description, help text, and arguments.
     */
    protected function configure() {
      $this->db = $GLOBALS['db'];
      // Configure this class to represent the `restart-services` command
      $this->setName        ('restart-services')
           ->setDescription ('Restarts all associated services')
           ->setHelp        ('This command will restart all associated '.
                             'services.');
    }

    /**
     * Executes the command with the required arguments.
     *
     * @param   InputInterface   $input   An interface to user-input methods.
     * @param   OutputInterface  $output  An interface to output methods.
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
      // Setup some environment variables to complete this command
      $io       = new SymfonyStyle($input, $output);
      // Reload all associated services
      if (!FPMPool::reload() || !NGINX::reload() || !OpenDKIM::reload())
        throw new \Exception('Unable to restart services.');
      // Finish the flush config process with an info message
      $io->success('All services restarted successfully.');
    }
  }
