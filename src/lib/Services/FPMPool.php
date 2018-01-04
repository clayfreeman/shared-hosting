<?php
  namespace SharedHosting\Services;

  use \SharedHosting\Interfaces\Service;
  use \SharedHosting\Utility\Validation;

  class FPMPool implements Service {
    protected        $db        = null;
    protected        $files     = [];
    protected        $fwd       = true;
    protected static $overwrite = false;
    protected        $username  = null;
    protected        $versions  = [];

    public function __construct(string $username, bool $overwrite = false) {
      $this->db = $GLOBALS['db'];
      // Check the provided username for validity
      Validation::username($username);
      // Assign the provided username to an internal property
      $this->username  = $username;
      self::$overwrite = $overwrite;
      $this->versions  =  self::fetchVersions();
      $this->files     = $this->fetchFiles();
    }

    protected function fetchFiles(): array {
      $result = []; // Iterate over each installed version of PHP
      foreach ($this->versions as $version)
        // Create a path key and content value for this version
        $result['/etc/php/'.$version.'/fpm/pool.d/'.$this->username.'.conf'] =
          implode("\n", [ '['.$this->username.']',
            'include = /etc/shared-hosting/php'.$version.'-common.conf', null]);
      // Return the resulting array of paths and contents
      return $result;
    }

    protected static function fetchVersions(): array {
      // Return a list of installed PHP versions based on available binaries
      return preg_split('/\\s+/', trim(shell_exec('ls /usr/sbin/php-fpm?.? | '.
        'sed \'s|/usr/sbin/php-fpm||g\'')));
    }

    public function fetchResult(): bool {
      // Return whether the forward or reverse methods succeeded
      return ( $this->fwd &&  $this->exists()) ||
             (!$this->fwd && !$this->exists());
    }

    public function exists(): bool {
      // Return false for any file that doesn't exist
      foreach (array_keys($this->files) as $file)
        if (!is_file($file)) return false;
      // If we reach this point, then all files exist
      return true;
    }

    public function forward(): void {
      // Keep track of the last direction in the internal state
      $this->fwd = true;
      // Install the configuration files for this user's pools
      foreach ($this->files as $file => $content) {
        is_dir(dirname($file)) || mkdir(dirname($file), 0755, true);
        file_put_contents($file, $content);
      }
    }

    public function reverse(): void {
      // Keep track of the last direction in the internal state
      $this->fwd = false;
      // Remove the configuration files for this user's pools
      foreach (array_keys($this->files) as $file) unlink($file);
    }

    public static function flushCommon() {
      // Flush a common include for each PHP version
      foreach (self::fetchVersions() as $version) {
        $file = '/etc/shared-hosting/php'.$version.'-common.conf';
        // Only write the file if it doesn't exist, or overwrite requested
        if (!file_exists($file) || self::$overwrite)
          file_put_contents($file, str_replace('{{VERSION}}', $version,
            file_get_contents(__PROJECTROOT__.'/php-common.cnf')));
      }
    }

    public static function reload(): bool {
      $result = true;
      // Restart the PHP FPM services
      foreach (self::fetchVersions() as $version) {
        system('systemctl restart php'.escapeshellarg($version).
          '-fpm', $tempResult);
        $result = $result && ($tempResult === 0);
      } return $result;
    }
  }
