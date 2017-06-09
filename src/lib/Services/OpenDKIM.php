<?php
  namespace SharedHosting\Services;

  use \SharedHosting\Interfaces\Service;

  class OpenDKIM implements Service {
    protected $db         = null;
    protected $result     = false;

    public function __construct() {
      $this->db = $GLOBALS['db'];
    }

    public function fetchDomains(): ?array {
      // Run an SQL statement to fetch all domains
      $data = $this->db->query('SELECT * FROM `hosting_schema`.`domains`');
      $data = $data->fetchAll();
      // Check if the resulting data is an array
      return is_array($data) ? $data : null;
    }

    public function fetchResult(): bool {
      // Return whether the forward or reverse methods succeeded
      return $this->result && $this->reload();
    }

    protected function build(): void {
      // Fetch an array of domains from the database
      $domains  = $this->fetchDomains();
      // Ensure that the array of domains is not null
      if (is_array($domains)) {
        $this->result = true;
        // Create an array of table entries for each domain
        $domains = array_map([$this, 'createConfiguration'], $domains);
        // Create the directory entry structure for OpenDKIM
        $this->createDirectoryEntries();
        // Iterate over each domain's configuration
        foreach ($domains as $domain) {
          // Create this domain's key file
          $this->createKeyFile($domain['KeyUUID'], $domain['KeyContents']);
          // Write this domain's table entries
          $dkim     = '/etc/opendkim';
          $keytbl   = $dkim.'/KeyTable';
          $signtbl  = $dkim.'/SigningTable';
          $trusttbl = $dkim.'/TrustedHosts';
          file_put_contents($keytbl,   $domain['KeyTable'],     FILE_APPEND);
          file_put_contents($signtbl,  $domain['SigningTable'], FILE_APPEND);
          file_put_contents($trusttbl, $domain['TrustedHosts'], FILE_APPEND);
        }
      }
    }

    protected function createConfiguration(array $domain): array {
      // Create a configuration array for this domain
      return [
        'KeyContents'  => $domain['dkim_private'],
        'KeyUUID'      => $domain['uuid'],
        'KeyTable'     => $domain['dkim_selector'].'._domainkey.'.
                          $domain['name'].' '.$domain['name'].':'.
                          $domain['dkim_selector'].':/etc/opendkim/keys/'.
                          $domain['uuid'].".pem\n",
        'SigningTable' => $domain['name'].' '.$domain['dkim_selector'].
                          '._domainkey.'.$domain['name']."\n",
        'TrustedHosts' => $domain['name']."\n"
      ];
    }

    protected function createDirectoryEntries(): void {
      $user = 'opendkim'; $group = 'opendkim'; $dir = 0700; $file = 0600;
      // Recursively create the keys directory on disk
      $dkim = '/etc/opendkim'; $keys = $dkim.'/keys';
      is_dir($keys) || mkdir($keys, null, true);
      // Set the appropriate permissions for the directory structure
      chown($dkim, $user); chgrp($dkim, $group); chmod($dkim, $dir);
      chown($keys, $user); chgrp($keys, $group); chmod($keys, $dir);
      // Remove all pre-existing key files
      array_map('unlink', glob($keys.'/*.pem'));
      // Create the KeyTable, SigningTable, TrustedHosts tables
      foreach (['KeyTable', 'SigningTable', 'TrustedHosts'] as $filename) {
        $suf = ($filename === 'TrustedHosts' ? "127.0.0.1\nlocalhost\n" : null);
        // Create the table file and set the appropriate permissions
        touch($filename = $dkim.'/'.$filename);
        chown($filename, $user); chgrp($filename, $group);
        chmod($filename, $file);
        // Empty this file to ensure a clean slate
        file_put_contents($filename, "# MANUAL CHANGES WILL BE LOST\n".$suf);
      }
    }

    protected function createKeyFile(string $uuid, string $content): void {
      $user = 'opendkim'; $group = 'opendkim'; $file = 0600;
      // Create the key file using the UUID as its name
      touch($filename = '/etc/opendkim/keys/'.$uuid.'.pem');
      chown($filename, $user); chgrp($filename, $group);
      chmod($filename, $file);
      // Write the key file contents to the file
      file_put_contents($filename, trim($content)."\n");
    }

    public function forward(): void {
      // Attempt to create the OpenDKIM configuration
      $this->build();
    }

    public function reverse(): void {
      // Attempt to create the OpenDKIM configuration
      $this->build();
    }

    protected function reload(): bool {
      // Attempt to reload the `opendkim` service
      system('systemctl restart opendkim', $exit);
      return ($exit === 0);
    }
  }
