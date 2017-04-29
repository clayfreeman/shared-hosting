<?php
  /**
   * This file defines a command `delete-account` used to delete Unix and MySQL
   * accounts in addition to user-specific PHP FPM pools.
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

  use SharedHosting\Services\FPMPool;
  use SharedHosting\Services\HostingAccount;
  use SharedHosting\Services\MySQLAccount;
  use SharedHosting\Services\UnixAccount;
  use SharedHosting\Utility\Transaction;

  /**
   * The `delete-account` command delete Unix and MySQL accounts in addition to
   * user-specific PHP FPM pools.
   */
  class DeleteAccount extends Command {
    protected $db = null;

    /**
     * Configures the command using the Symfony console API with its name,
     * description, help text, and arguments.
     */
    protected function configure() {
      $this->db = $GLOBALS['db'];
      // Configure this class to represent the `delete-account` command
      $this->setName        ('delete-account')
           ->setDescription ('Creates a shared hosting account')
           ->setHelp        ('This command will delete the Unix and MySQL '.
                             'accounts and user-specific PHP FPM pool for the '.
                             'provided username.')
           ->addArgument    ('username', InputArgument::REQUIRED,
                             'The username of the account to be deleted');
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
      $username = $input->getArgument('username');
      // Create instances of each Service to be used in this command
      $hosting  = new HostingAccount($username);
      $fpm      = new FPMPool       ($username);
      $mysql    = new MySQLAccount  ($username);
      $unix     = new UnixAccount   ($username);
      // Ensure that this account has no sites associated with it
      if (count($hosting->fetchSites()) > 0)
        throw new \Exception('The provided hosting account has sites '.
          'associated with it. Its sites must be deleted to continue.');
      // Attempt to delete the Unix and MySQL user accounts
      (new Transaction($hosting, $fpm, $mysql, $unix))->run(false);
      $io->success('Account '.escapeshellarg($username).' was deleted.');
    }
  }
