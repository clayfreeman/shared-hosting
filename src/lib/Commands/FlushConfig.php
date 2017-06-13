<?php
  /**
   * This file defines a command `flush-config` used to update the configuration
   * of all associated services.
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
  use SharedHosting\Services\Site;
  use SharedHosting\Utility\Transaction;

  /**
   * The `flush-config` rewrites the configuration for all services.
   */
  class FlushConfig extends Command {
    protected $db = null;

    /**
     * Configures the command using the Symfony console API with its name,
     * description, help text, and arguments.
     */
    protected function configure() {
      $this->db = $GLOBALS['db'];
      // Configure this class to represent the `flush-config` command
      $this->setName        ('flush-config')
           ->setDescription ('Rewrites the configuration for all services')
           ->setHelp        ('This command will regenerate the configuration '.
                             'files for all associated services.');
    }

    /**
     * Executes the command with the required arguments.
     *
     * @param   InputInterface   $input   An interface to user-input methods.
     * @param   OutputInterface  $output  An interface to output methods.
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
      // Regenerate all configuration files relating to each hosting account
      $accounts = new Transaction(...array_map(function($username) {
        return new FPMPool($username[0]);
      }, $this->fetchAccounts()));
      // Regenerate all configuration files relating to each site
      $sites    = new Transaction(...array_map(function($uuid) {
        return new NGINX(new Site($uuid[0]));
      }, $this->fetchSites()));
      // Regenerate all configuration files relating to each domain
      $domains  = new Transaction(new OpenDKIM());
      // Attempt to run all transactions in batch mode
      $accounts->run(true, true);
      $sites->run(true, true);
      $domains->run(true, true);
    }

    protected function fetchAccounts(): array {
      // Query the database for all account usernames
      $statement = $this->db->query('SELECT username FROM '.
        '`hosting_schema`.`accounts`');
      // Fetch all results from the prepared statement
      return $statement->fetchAll();
    }

    protected function fetchSites(): array {
      // Query the database for all site UUIDs
      $statement = $this->db->query('SELECT uuid FROM '.
        '`hosting_schema`.`sites`');
      // Fetch all results from the prepared statement
      return $statement->fetchAll();
    }
  }
