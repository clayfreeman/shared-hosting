<?php
  /**
   * This file defines a command `list-accounts` used to print a list of all
   * hosting accounts.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  namespace SharedHosting\Commands;

  use Symfony\Component\Console\Command\Command;
  use Symfony\Component\Console\Input\InputArgument;
  use Symfony\Component\Console\Input\InputInterface;
  use Symfony\Component\Console\Output\OutputInterface;
  use Symfony\Component\Console\Style\SymfonyStyle;

  /**
   * The `list-accounts` command prints a list of all hosting accounts.
   */
  class ListAccounts extends Command {
    protected $db = null;

    /**
     * Configures the command using the Symfony console API with its name,
     * description, help text, and arguments.
     */
    protected function configure() {
      $this->db = $GLOBALS['db'];
      // Configure this class to represent the `list-accounts` command
      $this->setName        ('list-accounts')
           ->setDescription ('Lists all known hosting accounts')
           ->setHelp        ('This command will list all registered hosting '.
                             'accounts on the system.');
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
      $io->table(['Username', 'Password (MySQL)'], $this->fetchAccounts());
    }

    protected function fetchAccounts(): array {
      // Query the database for all accounts and passwords
      $statement = $this->db->query('SELECT username, password FROM '.
        '`hosting_schema`.`accounts`');
      // Fetch all results from the prepared statement
      return $statement->fetchAll();
    }
  }
