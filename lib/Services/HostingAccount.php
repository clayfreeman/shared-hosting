<?php
  namespace SharedHosting\Services;

  use \SharedHosting\Interfaces\Service;
  use \SharedHosting\Services\UnixAccount;
  use \SharedHosting\Utility\Validation;

  class HostingAccount implements Service {
    protected $db       = null;
    protected $fwd      = true;
    protected $password = null;
    protected $unix     = null;
    protected $username = null;
    protected $uuid     = null;

    public function __construct(string $username, ?string $password = null,
        ?string $uuid = null, ?UnixAccount $unix = null) {
      $this->db = $GLOBALS['db'];
      // Check the provided username for validity
      Validation::username($username);
      // Assign the provided username to an internal property
      $this->username = $username;
      // Check if an optional argument was provided
      $optional = [$password, $uuid, $unix];
      if (count(array_filter($optional, function($i) {
          return isset($i); })) > 0) {
        // Assign the provided arguments to an internal property
        $this->uuid     = $uuid;
        $this->password = $password;
        $this->unix     = $unix;
      } else {
        if (!$this->exists())
          throw new \Exception('The provided hosting account does not exist.');
        // Load the account from the database
        $account        = $this->fetchAccount();
        // Assign the provided arguments to an internal property
        $this->password = $account['password'];
        $this->uuid     = $account['uuid'];
        $this->unix     = new UnixAccount($username);
        // Check the provided unix account for validity
        if (!$this->unix->exists())
          throw new \Exception('The provided Unix account does not exist.');
      } // Check the provided arguments for validity
      Validation::password($this->password);
      Validation::uuid    ($this->uuid);
    }

    public function fetchAccount(): ?array {
      // Prepare an SQL statement to fetch the hosting account
      $statement = $this->db->prepare('SELECT * FROM '.
        '`hosting_schema`.`accounts` WHERE `username` = :input');
      // Run the prepared SQL statement to fetch the hosting account
      if ($statement->execute([':input' => $this->username]) &&
          is_array($result = $statement->fetch()))
        return $result;
      // Return null if the SQL statement failed
      return null;
    }

    public function fetchResult(): bool {
      // Return whether the forward or reverse methods succeeded
      return ( $this->fwd &&  $this->exists()) ||
             (!$this->fwd && !$this->exists());
    }

    public function fetchSites(): ?array {
      // Prepare an SQL statement to fetch the sites for this account
      $statement = $this->db->prepare('SELECT `uuid` FROM '.
        '`hosting_schema`.`sites` WHERE `account_id` = (SELECT `id` FROM '.
        '`hosting_schema`.`accounts` WHERE `username` = :input)');
      // Run the prepared SQL statement to fetch the sites for this account
      if ($statement->execute([':input' => $this->username]) &&
          is_array($result = $statement->fetchAll()))
        return array_map(function($site) {
          return $site['uuid'];
        }, $result);
      // Return null if the SQL statement failed
      return null;
    }

    public function forward(): void {
      // Keep track of the last direction in the internal state
      $this->fwd = true;
      // Attempt to create the hosting user account
      if (!$this->exists()) $this->create();
    }

    public function reverse(): void {
      // Keep track of the last direction in the internal state
      $this->fwd = false;
      // Attempt to destroy the hosting user account
      if ( $this->exists()) $this->delete();
    }

    protected function create(): void {
      // Prepare an SQL statement to insert an account record
      $statement = $this->db->prepare('INSERT INTO '.
        '`hosting_schema`.`accounts` (`uuid`, `username`, `password`, '.
        '`home`) VALUES (:uuid, :username, :password, :home)');
      // Insert a record into the database reflecting the new account
      $statement->execute([':uuid' => $this->uuid,
        ':username' => $this->username, ':password' => $this->password,
        ':home' => $this->unix->fetchHome() ]);
      // Attempt to write an autologin configuration for MySQL
      file_put_contents($this->unix->fetchHome().'/.my.cnf', implode("\n",
        ['[client]', 'user="'.$this->username.'"',
        'password="'.$this->password.'"', null]));
    }

    protected function delete(): void {
      // Prepare an SQL statement to check if the requested username exists
      $statement = $this->db->prepare('DELETE FROM '.
        '`hosting_schema`.`accounts` WHERE `username` = :input');
      // Run the prepared SQL statement to check if the username already exists
      $statement->execute([':input' => $this->username]);
    }

    public function exists(): bool {
      // Prepare an SQL statement to check if the requested username exists
      $statement = $this->db->prepare('SELECT EXISTS(SELECT 1 FROM '.
        '`hosting_schema`.`accounts` WHERE `username` = :input) AS `exists`');
      // Run the prepared SQL statement to check if the username already exists
      return !$statement->execute([':input' => $this->username]) ||
        boolval($statement->fetch()['exists']);
    }
  }
