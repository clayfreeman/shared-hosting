<?php
  namespace SharedHosting\Services;

  use \SharedHosting\Interfaces\Service;
  use \SharedHosting\Utility\Validation;

  class TLS implements Service {
    protected $account    = null;
    protected $db         = null;
    protected $domains    = null;
    protected $fwd        = true;
    protected $site       = null;

    public function __construct(Site $site) {
      $this->db = $GLOBALS['db'];
      // Assign the provided arguments to an internal property
      $this->site    = $site;
    }

    public function prepare(): void {
      // Check the provided arguments for validity
      if (!$this->site->exists())
        throw new \Exception('An unexpected TLS error occurred.');
      $this->account = new HostingAccount($this->site->fetchOwner());
      if (!$this->account->exists())
        throw new \Exception('An unexpected TLS error occurred.');
      $this->domains = $this->site->fetchDomains();
    }

    public function fetchResult(): bool {
      // Return whether the forward or reverse methods succeeded
      return ( $this->fwd &&  $this->site->isTLSEnabled()) ||
             (!$this->fwd && !$this->site->isTLSEnabled());
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
      $this->site->setTLSEnabled(false);
    }

    protected function create(): void {
      // Ensure that the site's template has an HTTPS variety
      Validation::template($this->site->fetchSite()['template'], true);
      // Generate a Let's Encrypt PKI for this site if none exists
      if (!$this->site->hasPKI() && $this->run()) {
        // Setup the paths for this site's PKI
        $domain      = $this->domains[0] ?? null;
        $certificate = '/etc/letsencrypt/live/'.$domain.'/fullchain.pem';
        $private     = '/etc/letsencrypt/live/'.$domain.'/privkey.pem';
        // Ensure that both files exist
        if (!file_exists($certificate) || !file_exists($private))
          throw new \Exception('Unable to find the generated PKI.');
        $this->site->setTLSCertificate($certificate);
        $this->site->setTLSPrivate    ($private);
      } else if (!$this->site->hasPKI())
        throw new \Exception('Unable to generate PKI for this site.');
      // Mark the site as having TLS enabled
      $this->site->setTLSEnabled(true);
    }

    protected function run(): bool {
      // Ensure that the count of domains for this certificate doesn't exceed 25
      if (count($this->domains) > 25)
        throw new \Exception('TLS can only be enabled for sites with less '.
          'than 25 total domains');
      // Attempt to create a certificate for all the domains for this site
      system('certbot certonly --quiet --no-eff-email --non-interactive '.
        '--agree-tos --email '.escapeshellarg($GLOBALS['email']).' --webroot '.
        '--webroot-path /var/private/letsencrypt --rsa-key-size 4096 '.
        implode(' ', array_map(function($domain) {
          return '-d '.($domain = escapeshellarg($domain)).' -d www.'.$domain;
        }, $this->domains)), $exit);
      // Determine success based on the exit code of the command
      return $exit === 0;
    }
  }
