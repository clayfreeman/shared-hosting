<?php
  namespace SharedHosting\Services;

  use \SharedHosting\Interfaces\Service;
  use \SharedHosting\Utility\Validation;

  class Site implements Service {
    protected $account    = null;
    protected $db         = null;
    protected $fwd        = true;
    protected $result     = false;
    protected $root       = null;
    protected $template   = null;
    protected $uuid       = null;

    public function __construct(string $uuid, ?HostingAccount $account = null,
        ?string $root = null, ?string $template = null) {
      $this->db = $GLOBALS['db'];
      // Check the provided UUID for validity
      Validation::uuid   ($uuid);
      // Assign the provided UUID to an internal property
      $this->uuid       = $uuid;
      // Check if an optional argument was provided
      $optional = [$account, $root, $template];
      if (count(array_filter($optional, function($i) {
          return isset($i); })) > 0) {
        // Assign the provided arguments to an internal property
        $this->account    = $account;
        $this->root       = $root;
        $this->template   = $template;
      } else {
        // Load the account from the database
        $site             = $this->fetchSite();
        // Assign the provided arguments to an internal property
        $this->root       = $site['root'];
        $this->template   = $site['template'];
        $this->account    = new HostingAccount($this->fetchOwner());
      } // Check the provided arguments for validity
      if (!$this->account->exists())
        throw new \Exception('An unexpected site error occurred.');
      Validation::domain  ($this->root);
      Validation::template($this->template);
    }

    public function fetchDomains(): ?array {
      // Prepare an SQL statement to fetch the site
      $statement = $this->db->prepare('SELECT `name` FROM '.
        '`hosting_schema`.`domains` WHERE `site_id` = (SELECT `id` FROM '.
        '`hosting_schema`.`sites` WHERE `uuid` = :input)');
      // Run the prepared SQL statement to fetch the site
      if ($statement->execute([':input' => $this->uuid]))
        return array_map(function($domain) {
          return $domain['name'];
        }, $statement->fetchAll());
      // Return null if the SQL statement failed
      return null;
    }

    public function fetchOwner(): ?string {
      // Prepare an SQL statement to fetch the site
      $statement = $this->db->prepare('SELECT `username` FROM '.
        '`hosting_schema`.`accounts` WHERE `id` = (SELECT `account_id` FROM '.
        '`hosting_schema`.`sites` WHERE `uuid` = :input)');
      // Run the prepared SQL statement to fetch the site
      if ($statement->execute([':input' => $this->uuid]))
        return $statement->fetch()['username'];
      // Return null if the SQL statement failed
      return null;
    }

    public function fetchResult(): bool {
      // Return whether the forward or reverse methods succeeded
      return ( $this->fwd &&  $this->exists() && $this->result) ||
             (!$this->fwd && !$this->exists());
    }

    public function fetchSite(): ?array {
      // Prepare an SQL statement to fetch the site
      $statement = $this->db->prepare('SELECT * FROM '.
        '`hosting_schema`.`sites` WHERE `uuid` = :input');
      // Run the prepared SQL statement to fetch the site
      if ($statement->execute([':input' => $this->uuid]))
        return $statement->fetch();
      // Return null if the SQL statement failed
      return null;
    }

    public function forward(): void {
      // Keep track of the last direction in the internal state
      $this->fwd = true; $this->result = $this->exists();
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
      // Prepare an SQL statement to insert a site record
      $statement = $this->db->prepare('INSERT INTO '.
        '`hosting_schema`.`sites` (`account_id`, `template`, `uuid`, `root`) '.
        'VALUES (:account_id, :template, :uuid, :root)');
      // Insert a record into the database reflecting the new account
      $this->result   =  $statement->execute([':uuid' => $this->uuid,
        ':account_id' => $this->account->fetchAccount()['id'],
        ':template'   => $this->template, ':root' => $this->root ]);
    }

    protected function delete(): void {
      // Prepare an SQL statement to check if the requested username exists
      $statement = $this->db->prepare('DELETE FROM '.
        '`hosting_schema`.`sites` WHERE `uuid` = :input');
      // Run the prepared SQL statement to check if the username already exists
      $statement->execute([':input' => $this->uuid]);
    }

    public function exists(): bool {
      // Prepare an SQL statement to check if the requested site exists
      $statement = $this->db->prepare('SELECT EXISTS(SELECT 1 FROM '.
        '`hosting_schema`.`sites` WHERE `uuid` = :input) AS `exists`');
      // Run the prepared SQL statement to check if the site already exists
      return !$statement->execute([':input' => $this->uuid]) ||
        boolval($statement->fetch()['exists']);
    }
  }
