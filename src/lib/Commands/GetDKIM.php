<?php
  /**
   * This file defines a command `get-dkim` used to print the DKIM DNS record
   * for a given domain name.
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
   * The `get-dkim` command prints the DKIM DNS record for a given domain name.
   */
  class GetDKIM extends Command {
    protected $db = null;

    /**
     * Configures the command using the Symfony console API with its name,
     * description, help text, and arguments.
     */
    protected function configure() {
      $this->db = $GLOBALS['db'];
      // Configure this class to represent the `get-dkim` command
      $this->setName        ('get-dkim')
           ->setDescription ('Prints the DKIM DNS record for a domain name')
           ->setHelp        ('This command will print the DKIM DNS record for '.
                             'a given domain name.')
           ->addArgument    ('domain', InputArgument::REQUIRED,
                             'Any domain associated with a site');;
    }

    /**
     * Executes the command with the required arguments.
     *
     * @param   InputInterface   $input   An interface to user-input methods.
     * @param   OutputInterface  $output  An interface to output methods.
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
      // Print the trimmed record from the database
      echo trim($this->fetchDKIM(strtolower(trim($input->getArgument('domain'),
        ".\t\n\r\0\x0B "))))."\n";
    }

    protected function fetchDKIM(string $input): ?string {
      // Query the database for a DKIM record for the requested domain
      $statement = $this->db->prepare('SELECT `dkim_record` FROM '.
        '`hosting_schema`.`domains` WHERE `name` = :input LIMIT 0,1');
      $statement->execute([':input' => $input]);
      // Fetch the results from the prepared statement
      return $statement->fetch()['dkim_record'] ?? null;
    }
  }
