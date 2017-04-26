<?php
  namespace SharedHosting\Services;

  use \SharedHosting\Interfaces\Service;
  use \SharedHosting\Utility\Validation;

  class UnixAccount implements Service {
    protected $db       = null;
    protected $fwd      = true;
    protected $username = null;

    public function __construct(string $username) {
      $this->db = $GLOBALS['db'];
      // Check the provided username for validity
      Validation::username($username);
      // Assign the provided username to an internal property
      $this->username = $username;
    }

    public function fetchHome(): ?string {
      // Attempt to fetch the home directory for this user
      return posix_getpwnam($this->username)['dir'] ?? null;
    }

    public function fetchResult(): bool {
      // Return whether the forward or reverse methods succeeded
      return ( $this->fwd &&  $this->exists() && $this->mkhtml()) ||
             (!$this->fwd && !$this->exists());
    }

    protected function mkhtml(): void {
      $html = $this->fetchHome().'/public_html';
      return mkdir($html) && chown($html, $this->username) &&
        chgrp($html, $this->username) && chmod($html, 02775);
    }

    public function forward(): void {
      // Keep track of the last direction in the internal state
      $this->fwd = true;
      // Attempt to create the Unix user account
      if (!$this->exists())
        shell_exec('adduser --quiet --disabled-password --gecos "" '.
          escapeshellarg($this->username));
    }

    public function reverse(): void {
      // Keep track of the last direction in the internal state
      $this->fwd = false;
      // Attempt to destroy the Unix user account
      if ($this->exists())
        shell_exec('deluser --quiet --remove-home --backup '.
          '--backup-to /home '.escapeshellarg($this->username).' 2>/dev/null');
    }

    public function exists(): bool {
      // Determine if the requested username exists
      return is_array(posix_getpwnam($this->username));
    }
  }
