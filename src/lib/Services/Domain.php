<?php
  namespace SharedHosting\Services;

  use \SharedHosting\Interfaces\Service;
  use \SharedHosting\Utility\Validation;

  class Domain implements Service {
    protected $db       = null;
    protected $fwd      = true;
    protected $name     = null;
    protected $public   = null;
    protected $result   = false;
    protected $secret   = null;
    protected $selector = null;
    protected $site     = null;
    protected $uuid     = null;

    public function __construct(string $name, ?Site $site = null,
        ?string $uuid   = null, ?string $selector = null,
        ?string $secret = null) {
      $this->db = $GLOBALS['db'];
      // Check the provided domain for validity
      Validation::domain ($name);
      // Assign the provided domain to an internal property
      $this->name       = $name;
      // Check if an optional argument was provided
      $optional = [$site, $uuid, $selector, $secret];
      if (count(array_filter($optional, function($i) {
          return isset($i); })) > 0) {
        // Assign the provided arguments to an internal property
        $this->secret     = $secret;
        $this->selector   = $selector;
        $this->site       = $site;
        $this->uuid       = $uuid;
        // Check the provided keys for validity
        $secret           = openssl_pkey_get_private($this->secret);
        if ($secret === false)
          throw new \Exception('The generated secret key is invalid.');
        // Fetch the public key from the secret key
        $this->public     = openssl_pkey_get_details($secret)['key'];
        // Modify the public key into a DKIM DNS record
        $this->public     = explode("\n", trim($this->public));
        $this->public     = implode(null, array_splice($this->public, 1, -1));
        $this->public     = 'v=DKIM1; k=rsa; p='.$this->public;
      } else {
        // Load the account from the database
        $domain           = $this->fetchDomain();
        // Assign the provided arguments to an internal property
        $this->public     = $domain['dkim_record'];
        $this->secret     = $domain['dkim_private'];
        $this->selector   = $domain['dkim_selector'];
        $this->uuid       = $domain['uuid'];
        if (!is_null($this->fetchSite()))
          $this->site     = new Site($this->fetchSite());
      }
    }

    public function fetchDomain(): ?array {
      // Prepare an SQL statement to fetch the site
      $statement = $this->db->prepare('SELECT * FROM '.
        '`hosting_schema`.`domains` WHERE `name` = :input');
      // Run the prepared SQL statement to fetch the site
      if ($statement->execute([':input' => $this->name]) &&
          is_array($result = $statement->fetch()))
        return $result;
      // Return null if the SQL statement failed
      return null;
    }

    public function fetchResult(): bool {
      // Return whether the forward or reverse methods succeeded
      return ( $this->fwd &&  $this->exists() && $this->result) ||
             (!$this->fwd && !$this->exists());
    }

    public function fetchSite(): ?string {
      // Prepare an SQL statement to fetch the site
      $statement = $this->db->prepare('SELECT `uuid` FROM '.
        '`hosting_schema`.`sites` WHERE `id` = (SELECT `site_id` FROM '.
        '`hosting_schema`.`domains` WHERE `uuid` = :input)');
      // Run the prepared SQL statement to fetch the site
      if ($statement->execute([':input' => $this->uuid]))
        return $statement->fetch()['uuid'];
      // Return null if the SQL statement failed
      return null;
    }

    public function forward(): void {
      // Keep track of the last direction in the internal state
      $this->fwd = true; $this->result = $this->exists();
      // Check the provided site for validity
      if (!$this->site->exists())
        throw new \Exception('An unexpected error occurred.');
      // Check the provided arguments for validity
      Validation::domainSubdivision($this->selector);
      Validation::uuid             ($this->uuid);
      // Attempt to create the MySQL user account
      if (!$this->exists()) $this->create();
    }

    public function reverse(): void {
      // Keep track of the last direction in the internal state
      $this->fwd = false;
      // Attempt to destroy the MySQL user account
      if ( $this->exists()) $this->delete();
    }

    protected function create(): void {
      // Prepare an SQL statement to insert a domain record
      $statement = $this->db->prepare('INSERT INTO '.
        '`hosting_schema`.`domains` (`site_id`, `uuid`, `name`, '.
        '`dkim_selector`, `dkim_private`, `dkim_record`) VALUES (:site_id, '.
        ':uuid, :name, :selector, :secret, :record)');
      // Insert a record into the database reflecting the new domain
      $this->result   =  $statement->execute([':site_id' =>
        $this->site->fetchSite()['id'], ':uuid' => $this->uuid,
        ':name'   => $this->name, ':selector' => $this->selector,
        ':secret' => $this->secret, ':record' => $this->public]);
    }

    protected function delete(): void {
      // Prepare an SQL statement to delete the domain
      $statement = $this->db->prepare('DELETE FROM '.
        '`hosting_schema`.`domains` WHERE `name` = :input');
      // Run the prepared SQL statement to delete the domain
      $statement->execute([':input' => $this->name]);
    }

    public function exists(): bool {
      // Prepare an SQL statement to check if the requested username exists
      $statement = $this->db->prepare('SELECT EXISTS(SELECT 1 FROM '.
        '`hosting_schema`.`domains` WHERE `name` = :input) '.
        'AS `exists`');
      // Run the prepared SQL statement to check if the username already exists
      return !$statement->execute([':input' => $this->name]) ||
        boolval($statement->fetch()['exists']);
    }
  }
