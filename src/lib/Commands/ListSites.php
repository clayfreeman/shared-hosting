<?php
  /**
   * This file defines a command `list-sites` used to print a list of all
   * configured sites.
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

  /**
   * The `list-sites` command prints a list of all configured sites.
   */
  class ListSites extends Command {
    protected $db = null;

    /**
     * Configures the command using the Symfony console API with its name,
     * description, help text, and arguments.
     */
    protected function configure() {
      $this->db = $GLOBALS['db'];
      // Configure this class to represent the `list-sites` command
      $this->setName        ('list-sites')
           ->setDescription ('Lists all known sites')
           ->setHelp        ('This command will list all configured sites on '.
                             'the system.');
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
      $io->table(['Username', 'Domains (Primary First)'], $this->fetchSites());
    }

    protected function fetchSites(): array {
      // Query the database for all sites and their owners
      $statement = $this->db->query('SELECT `username`, GROUP_CONCAT(`name` '.
        'ORDER BY `domains`.`id` ASC SEPARATOR \',\\n\') AS `domains` FROM '.
        '`hosting_schema`.`domains` INNER JOIN `hosting_schema`.`sites` ON '.
        '`domains`.`site_id` = `sites`.`id` LEFT JOIN '.
        '`hosting_schema`.`accounts` ON '.
        '`sites`.`account_id` = `accounts`.`id` GROUP BY `domains`.`site_id`');
      // Fetch all results from the prepared statement
      return $statement->fetchAll();
    }
  }
