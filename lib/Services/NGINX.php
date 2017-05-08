<?php
  namespace SharedHosting\Services;

  use \SharedHosting\Interfaces\Service;

  class NGINX implements Service {
    protected $account    = null;
    protected $db         = null;
    protected $domains    = null;
    protected $fwd        = true;
    protected $result     = false;
    protected $site       = null;

    public function __construct(Site $site) {
      $this->db = $GLOBALS['db'];
      // Assign the provided arguments to an internal property
      $this->site    = $site;
    }

    public function prepare(): void {
      // Check the provided arguments for validity
      if (!$this->site->exists())
        throw new \Exception('An unexpected NGINX error occurred.');
      $this->account = new HostingAccount($this->site->fetchOwner());
      if (!$this->account->exists())
        throw new \Exception('An unexpected NGINX error occurred.');
      $this->domains = $this->site->fetchDomains();
    }

    public function fetchResult(): bool {
      // Return whether the forward or reverse methods succeeded
      return ( $this->fwd &&  $this->exists() && $this->result) ||
             (!$this->fwd && !$this->exists() && $this->result);
    }

    public function forward(): void {
      $this->prepare();
      // Keep track of the last direction in the internal state
      $this->fwd = true;
      // Attempt to create the MySQL user account
      $this->create();
    }

    public function reverse(): void {
      $this->prepare();
      // Keep track of the last direction in the internal state
      $this->fwd = false;
      // Attempt to destroy the MySQL user account
      $this->delete();
    }

    protected function create(): void {
      // Prepare the NGINX configuration for this site
      $account   = $this->account->fetchAccount();
      $site      = $this->site->fetchSite();
      $html      = $account['home'].'/public_html';
      $root      = $html.'/'.$site['root'];
      $sitetpl   = boolval($site['tls_enabled']) ? 'https' : 'http';
      $config    = file_get_contents(__PROJECTROOT__.'/nginx-'.$sitetpl.'.tpl');
      $config    = str_replace('{{SITEUSER}}', $account['username'], $config);
      $config    = str_replace('{{TEMPLATE}}', $site['template'],    $config);
      $config    = str_replace('{{DOMAINS}}', implode(' ', array_map(
        function($input) { return '.'.$input; }, $this->domains)),   $config);
      $config    = str_replace('{{CERTIFICATE}}',
        $site['tls_certificate'], $config);
      $config    = str_replace('{{PRIVATE}}',
        $site['tls_private'], $config);
      $config    = str_replace('{{ROOT}}', $root, $config);
      // Write the NGINX config to the appropriate location
      $available = '/etc/nginx/sites-available/'.$site['uuid'];
      $enabled   = '/etc/nginx/sites-enabled/'.$site['uuid'];
      $this->result = file_put_contents($available, $config) &&
          (is_link($enabled) || symlink($available, $enabled));
      // Create the document root for the newly created site
      $this->result = $this->result && (is_dir($root) ||
        mkdir($root, null, true));
      // Set the ownership for the document root
      $this->result = $this->result && chown($html, $account['username']);
      $this->result = $this->result && chgrp($html, $account['username']);
      $this->result = $this->result && chown($root, $account['username']);
      $this->result = $this->result && chgrp($root, $account['username']);
      $this->result = $this->result && chmod($html, 02775);
      $this->result = $this->result && chmod($root, 02775);
      // Reload the NGINX to enable the new configuration
      $this->result = $this->result && $this->reload();
    }

    protected function delete(): void {
      // Fetch the site details from the database
      $site      = $this->site->fetchSite();
      // Remove the NGINX config files from the appropriate location
      $available = '/etc/nginx/sites-available/'.$site['uuid'];
      $enabled   = '/etc/nginx/sites-enabled/'.$site['uuid'];
      $this->result = unlink($available) && unlink($enabled);
      // Reload NGINX for the new site to become active
      exec('systemctl restart nginx', $null, $exit);
      $this->result = $this->result && $this->reload();
    }

    public function exists(): bool {
      $this->prepare();
      // Fetch the site details from the database
      $site      = $this->site->fetchSite();
      // Determine the NGINX config file location
      $available = '/etc/nginx/sites-available/'.$site['uuid'];
      return file_exists($available);
    }

    protected function reload(): bool {
      // Test the NGINX config for errors
      exec('service nginx configtest', $null, $exit);
      if ($exit === 0) {
        // Reload NGINX for the new site to become active
        exec('systemctl restart nginx', $null, $exit);
        return ($exit === 0);
      } return false;
    }
  }
