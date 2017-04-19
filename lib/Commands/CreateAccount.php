<?php
  /**
   * This file defines a command `create-account` used to create Unix and MySQL
   * accounts in addition to a user-specific PHP FPM pool.
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
   * The `create-account` command creates Unix and MySQL accounts in addition to
   * a user-specific PHP FPM pool.
   */
  class CreateAccount extends Command {
    protected $db = null;

    /**
     * Configures the command using the Symfony console API with its name,
     * description, help text, and arguments.
     */
    protected function configure() {
      $this->db = $GLOBALS['db'];
      // Configure this class to represent the `create-account` command
      $this->setName        ('create-account')
           ->setDescription ('Creates a shared hosting account')
           ->setHelp        ('This command will provide you with a Unix '.
                             'account (can only login via `su`), MySQL '.
                             'account (with a randomly generated password), '.
                             'and a segregated PHP FPM pool')
           ->addArgument    ('username', InputArgument::REQUIRED,
                             'The desired username used to create the account');
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
      // Generate a random password for the new user account
      $password = bin2hex(random_bytes(16));
      $uuid     = \Ramsey\Uuid\Uuid::uuid4()->toString();
      // Create instances of each Service to be used in this command
      $fpm      = new FPMPool       ($username);
      $unix     = new UnixAccount   ($username);
      $mysql    = new MySQLAccount  ($username, $password);
      $hosting  = new HostingAccount($username, $password, $uuid, $unix);
      // Ensure that neither Unix or MySQL accounts exist
      if ($hosting->exists() || $unix->exists() || $mysql->exists())
        throw new \Exception('The requested username is taken.');
      // Attempt to create the Unix and MySQL user accounts
      (new Transaction($unix, $mysql, $fpm, $hosting))->run();
      $io->success('Hosting account created successfully.');
      // Finish the account creation process with an info message
      $io->title( 'Account Information');
      $io->text (['Please save the following account information:', null]);
      $io->table(['Username', 'Password (MySQL)'],
                [[$username,  $password]]);
    }
  }
