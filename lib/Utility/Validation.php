<?php
  namespace SharedHosting\Utility;

  class Validation {
    public static function domain(?string $input): void {
      if (!preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\\.)+[a-z]{2,}$/i', $input))
        throw new \Exception('An invalid domain name was provided: '.
          escapeshellarg($input));
    }

    public static function domainSubdivision(?string $input): void {
      if (!preg_match('/^[a-z0-9]{1,63}$/i', $input))
        throw new \Exception('An invalid domain subdivision was provided: '.
          escapeshellarg($input));
    }

    public static function password(?string $input): void {
      if (!preg_match('/^\\S{16,}$/', $input))
        throw new \Exception('The generated password does not meet the '.
          'required security standards.');
    }

    public static function template(?string $input): void {
      if (!file_exists('/etc/nginx/templates/'.$input.'-http.conf'))
        throw new \Exception('An unknown template was specified.');
    }

    public static function username(?string $input): void {
      if (!preg_match('/^[a-z][a-z0-9]{2,30}$/', $input))
        throw new \Exception('Usernames must begin with a letter and consist '.
          'of between 3-32 lowercase alphanumeric characters.');
    }

    public static function uuid(?string $input): void {
      if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB]'.
          '[0-9A-F]{3}-[0-9A-F]{12}$/i', $input))
        throw new \Exception('The generated UUID does not meet the standards '.
          'for a Version 4 UUID.');
    }
  }
