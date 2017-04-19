#!/usr/bin/env php
<?php
  /**
   * This file serves as an application entrypoint routine responsible for
   * preparing the application's runtime.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Display and enable *all* types of errors
  ini_set('display_errors',         '1');
  ini_set('display_startup_errors', '1');
  ini_set('log_errors',             '0');
  ini_set('log_errors_max_len',     '0');
  error_reporting(E_ALL | E_STRICT);

  // Define the required path constants for the application
  define('__PROJECTROOT__',  __DIR__);
  define('__COMMAND__',      basename($argv[0]));
  define('__DS__',           DIRECTORY_SEPARATOR);
  define('__CLASSPATH__',    realpath(__PROJECTROOT__.__DS__.'lib'));
  define('__VENDORROOT__',   realpath(__PROJECTROOT__.__DS__.'vendor'));
  define('__STARTTIME__',    microtime(true));

  // Check the PHP version number and complain if unsatisfactory
  { (version_compare(PHP_VERSION, $minimum = '7.1.0') >= 0) or trigger_error(
    'This project requires at least PHP '.$minimum.' to run', E_USER_ERROR); }

  // Ensure that this script is being run as 'root'
  if (posix_getuid() !== 0)
    trigger_error('This script must be run as root', E_USER_ERROR);

  // Load Composer's autoload registration file so that dependencies can be used
  require_once(implode(__DS__, [__VENDORROOT__, 'autoload.php']));

  // Load the configuration file
  $config = @json_decode(@file_get_contents(implode(__DS__, [
    __PROJECTROOT__, 'config.json'
  ])), true);

  // Ensure that there were no errors while loading the configuration
  if (!is_array($config))
    trigger_error('Could not load configuration file', E_USER_ERROR);

  // Connect to MySQL using the configured root password
  $dsn           = 'mysql:charset=utf8mb4;host=localhost';
  $GLOBALS['db'] = new \PDO($dsn, 'root', $config['password'] ?? '', [
    // Fetch associative array result sets by default
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    // Ensure that emulated prepared statements are disabled for security
    \PDO::ATTR_EMULATE_PREPARES   => false,
    // Force PDO to throw exceptions when an error occurs
    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION ]);
  // Create the expected schema if it doesn't exist
  $GLOBALS['db']->exec(file_get_contents(implode(__DS__, [
    __PROJECTROOT__, 'schema.sql'])));

  // Create a Symfony console application
  $application = new \Symfony\Component\Console\Application();

  // Load each configured sub-command
  foreach ($config['commands'] ?? [] as $command) {
    // Add the namespace prefix for this command
    $command = '\\SharedHosting\\Commands\\'.$command;
    // Add the command class to the application
    $application->add(new $command);
  }

  // Run the Symfony console application
  $application->setDefaultCommand(__COMMAND__, true);
  $application->run();
