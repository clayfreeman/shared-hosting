<?php
  /**
   * This file defines a command `create-site` used to create a document root,
   * server configurations, and DKIM signing keys for all provided domains.
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
  use SharedHosting\Services\HostingAccount;
  use SharedHosting\Services\NGINX;
  use SharedHosting\Services\OpenDKIM;
  use SharedHosting\Services\Site;
  use SharedHosting\Utility\Transaction;

  /**
   * The `create-site` command creates a document root, server configurations,
   * and DKIM signing keys for all provided domains.
   */
  class DeleteSite extends Command {
    protected $db = null;

    /**
     * Configures the command using the Symfony console API with its name,
     * description, help text, and arguments.
     */
    protected function configure() {
      $this->db = $GLOBALS['db'];
      // Configure this class to represent the `delete-site` command
      $this->setName        ('delete-site')
           ->setDescription ('Creates a shared hosting site')
           ->setHelp        ('This command will delete the website with the '.
                             'provided domain name, including supplemental '.
                             'domains, and all associated DKIM keys. No site '.
                             'files or databases will be harmed.')
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
      $opendkim = new OpenDKIM();
      // Attempt to delete the configuration files, site, and DKIM keys
      (new Transaction($nginx, $site, $opendkim))->run(false);
      // Finish the site deletion process with an info message
      $io->success('Site '.escapeshellarg($domain).' was deleted.');
    }
  }
