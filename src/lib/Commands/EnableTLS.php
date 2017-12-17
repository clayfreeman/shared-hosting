<?php
  /**
   * This file defines a command `enable-tls` used to add HTTPS capability to
   * the provided site.
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

  use SharedHosting\Services\Domain;
  use SharedHosting\Services\NGINX;
  use SharedHosting\Services\Site;
  use SharedHosting\Services\TLS;
  use SharedHosting\Utility\Transaction;

  /**
   * The `enable-tls` command adds HTTPS capability to the provided site.
   */
  class EnableTLS extends Command {
    protected $db = null;

    /**
     * Configures the command using the Symfony console API with its name,
     * description, help text, and arguments.
     */
    protected function configure() {
      $this->db = $GLOBALS['db'];
      // Configure this class to represent the `enable-tls` command
      $this->setName        ('enable-tls')
           ->setDescription ('Enables HTTPS for the given site')
           ->setHelp        ('This command will enable HTTPS for the website '.
                             'with the provided domain name.')
           ->addArgument    ('domain', InputArgument::REQUIRED,
                             'Any domain associated with a site');
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
      $domain   = new Domain($input->getArgument('domain'));
      // Check that we received a valid response
      if (!$domain->exists())
        throw new \Exception('The provided domain name does not exist.');
      // Create instances of each Service to be used in this command
      $site     = new Site($domain->fetchSite());
      $domain   = $domain->fetchDomain()['name'];
      if (!$site->exists())
        throw new \Exception('Unable to fetch the site for this domain name.');
      $nginx    = new NGINX($site);
      $tls      = new TLS  ($site);
      // Attempt to create or re-use PKI for HTTPS
      (new Transaction($tls, $nginx))->run();
      // Reload NGINX so that the new TLS certificate is activated
      if (!NGINX::reload())
        throw new \Exception('Unable to restart services.');
      // Finish the process with an info message
      $io->success('Site '.escapeshellarg($domain).' now has HTTPS enabled.');
    }
  }
