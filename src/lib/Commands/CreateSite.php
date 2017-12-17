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
  class CreateSite extends Command {
    protected $db = null;

    /**
     * Configures the command using the Symfony console API with its name,
     * description, help text, and arguments.
     */
    protected function configure() {
      $this->db = $GLOBALS['db'];
      // Configure this class to represent the `create-site` command
      $this->setName        ('create-site')
           ->setDescription ('Creates a shared hosting site')
           ->setHelp        ('This command will create a website based on the '.
                             'provided template and DKIM mail signing keys '.
                             'for each provided domain')
           ->addArgument    ('account',        InputArgument::REQUIRED,
                             'The account that should own the site')
           ->addArgument    ('template',       InputArgument::REQUIRED,
                             'The template to use for this site')
           ->addArgument    ('primary-domain', InputArgument::REQUIRED,
                             'The primary domain for this site')
           ->addArgument    ('other-domains',  InputArgument::OPTIONAL |
                                               InputArgument::IS_ARRAY,
                             'An optional list of supplemental domains for '.
                             'this site. These domains will not redirect, but '.
                             'serve the same document root');
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
      $username = $input->getArgument('account');
      $template = $input->getArgument('template');
      $uuid     = \Ramsey\Uuid\Uuid::uuid4()->toString();
      // Fetch the primary and supplemental domain arguments and ensure that no
      // duplicates occur by canonicalizing the input strings
      $primary  = strtolower(trim(
        $input->getArgument('primary-domain'),
      ".\t\n\r\0\x0B "));
      $other    = array_diff(array_unique(array_map(function($input) {
        return strtolower(trim($input, " .\t\n\r\0\x0B"));
      }, $input->getArgument('other-domains'))), [$primary]); sort($other);
      $domains  = array_merge($other, [$primary]); sort($domains);
      // Load the hosting account using the appropriate service
      $account  = new HostingAccount($username);
      // Check that we received a valid response
      if (!$account->exists())
        throw new \Exception('Unable to load an account with this username.');
      // Create instances of each Service to be used in this command
      $site     = new Site($uuid, $account, $primary, $template);
      if ($site->exists())
        throw new \Exception('The provided site already exists on '.
          'this system.');
      $domains  = array_map(function($domain) use ($site) {
        // Generate a DKIM private key for this domain
        openssl_pkey_export(openssl_pkey_new(
          ['private_key_bits' => 1024]), $secret);
        // Create a Domain service instance with the required arguments
        return new Domain($domain, $site,
          \Ramsey\Uuid\Uuid::uuid4()->toString(), 'default', $secret);
      }, $domains);
      $nginx    = new NGINX($site);
      $opendkim = new OpenDKIM();
      // Check that each domain is free to be created
      $invalid  = array_map(function($domain) {
        return $domain->fetchDomain()['name'];
      }, array_filter($domains, function($domain) {
        return $domain->exists();
      })); // Throw an exception detailing the error
      if (count($invalid) > 0)
        throw new \Exception('The following domain name(s) already exist '.
          'on this system: '.implode(', ', $invalid));
      // Attempt to create the site, domains, and configuration files
      (new Transaction(...array_merge([$site], $domains, [$nginx,
        $opendkim])))->run();
      // Build an information array listing the domains
      $info = array_map(function($domain) {
        return [$domain->fetchDomain()['name']];
      }, $domains);
      // Reload NGINX and OpenDKIM so that the new site is made available
      if (!NGINX::reload() || !OpenDKIM::reload())
        throw new \Exception('Unable to restart services.');
      // Finish the site creation process with an info message
      $io->success('Site created successfully.');
      $io->title('Site Information');
      $io->text (['Document Root: '.$account->fetchAccount()['home'].
        '/public_html/'.$primary, null]);
      $io->text ('This site can be reached via:');
      $io->table(['Domain'], $info);
      $io->text ('DKIM public keys can be fetched using the '.
        '`get-dkim <domain>` command.');
    }
  }
