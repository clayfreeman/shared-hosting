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
                             'Any domain associated with a site')
           ->addOption      ('get-fqdn', null, InputOption::VALUE_NONE,
                             'Fetch the Fully-Qualified Domain Name (FQDN) '.
                             'for the DKIM record instead of the value.');
    }

    /**
     * Executes the command with the required arguments.
     *
     * @param   InputInterface   $input   An interface to user-input methods.
     * @param   OutputInterface  $output  An interface to output methods.
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
      $domain = strtolower(trim($input->getArgument('domain'),
        ".\t\n\r\0\x0B "));
      $info   = $this->fetchDKIMInfo($domain);
      // Optionally print the FQDN for the DKIM record or ...
      if ($input->getOption('get-fqdn'))
        echo trim($info['dkim_selector']).'._domainkey.'.
             trim($info['name'])."\n";
      // print the trimmed record from the database
      else echo trim($info['dkim_record'])."\n";
    }

    protected function fetchDKIM(string $input): ?array {
      // Query the database for a DKIM record for the requested domain
      $statement = $this->db->prepare('SELECT `name`, `dkim_record`, '.
        '`dkim_selector` FROM `hosting_schema`.`domains` WHERE `name` = '.
        ':input LIMIT 0,1');
      $statement->execute([':input' => $input]);
      $result = $statement->fetch();
      // Fetch the results from the prepared statement
      return is_array($result) ? $result : null;
    }
  }
