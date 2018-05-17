<?php
 /**
  * @copyright  Copyright 2017 Clay Freeman. All rights reserved.
  * @license    GNU Lesser General Public License v3 (LGPL-3.0).
  */

  namespace SharedHosting\Services;

  use \SharedHosting\Interfaces\Service;
  use \SharedHosting\Utility\Validation;

  class MySQLAccount implements Service {
    protected $db       = null;
    protected $fwd      = true;
    protected $result   = false;
    protected $username = null;
    protected $password = null;

    public function __construct(string $username, ?string $password = null) {
      $this->db = $GLOBALS['db'];
      // Check the provided username for validity
      Validation::username($username);
      // Assign the provided username to an internal property
      $this->username = $username;
      // Check if an optional argument was provided
      if (isset($password)) {
        // Assign the provided password to an internal property
        $this->password = $password;
      } else {
        // Load the account from the database
        $account = new HostingAccount($username);
        // Check the hosting account for validity
        if (!$account->exists())
          throw new \Exception('The provided MySQL account does not exist.');
        // Assign the provided arguments to an internal property
        $this->password = $account->fetchAccount()['password'] ?? null;
      } // Check the provided password for validity
      Validation::password($this->password);
    }

    public function fetchResult(): bool {
      // Return whether the forward or reverse methods succeeded
      return ( $this->fwd &&  $this->exists() && $this->result) ||
             (!$this->fwd && !$this->exists());
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
      // Continue to grant permissions to this user upon successful creation
      if ($this->db->exec('CREATE USER '.
          $this->db->quote($this->username).'@`localhost` IDENTIFIED BY '.
          $this->db->quote($this->password)) !== false)
        // Run the prepared SQL statement to grant permissions
        $this->result = ($this->db->exec('GRANT ALL PRIVILEGES ON `'.
          $this->username.'\\_%`.* TO '.$this->db->quote($this->username).
          '@`localhost`') !== false);
    }

    protected function delete(): void {
      // Run the SQL statement to drop the requested username
      $this->db->exec('DROP USER '.$this->db->quote($this->username).
        '@`localhost`');
      // TODO: Backup user's databases and drop them too
    }

    public function exists(): bool {
      // Prepare an SQL statement to check if the requested username exists
      $statement = $this->db->prepare('SELECT EXISTS(SELECT 1 FROM '.
        '`mysql`.`user` WHERE `user` = :input) AS `exists`');
      // Run the prepared SQL statement to check if the username already exists
      return !$statement->execute([':input' => $this->username]) ||
        boolval($statement->fetch()['exists']);
    }
  }
