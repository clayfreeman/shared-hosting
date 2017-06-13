<?php
  namespace SharedHosting\Utility;

  use \SharedHosting\Interfaces\Service;

  class Transaction {
    public function __construct(Service ...$services) {
      // Save the array of services to be ran in this transaction
      $this->services = $services;
    }

    public function run(bool $forward = true): void {
      // Initialize a direction variable
      $increment = 1; $fail = null;
      // Create a function for the loop condition
      $count     = count($this->services);
      $condition = function($increment, $i) use ($count) {
        return (($increment < 0 && $i >= 0) ||
                ($increment > 0 && $i < $count));
      }; // Iterate over each service and attempt to run it
      for ($i = 0; $condition($increment, $i); $i += ($increment <=> 0)) {
        // Fetch the current service from the array
        $service = $this->services[$i];
        // Check if we're moving forward or reverse order
        if ($forward && ($increment <=> 0) === 1) {
          // Attempt to run this service
          $service->forward();
          // Determine if we're the last service in the transaction
          $last = !$condition($increment, $i + ($increment <=> 0));
          // Upon error, switch direction to begin rolling back changes
          if (!$service->fetchResult()) {
            $fail = get_class($service); $increment = -1; ++$i;
          } // Run the reverse method of this service
        } else if (!$forward || ($increment <=> 0) === -1) {
          // Attempt to reverse this service
          $service->reverse();
          // Determine if we're the last service in the transaction
          $last = !$condition($increment, $i + ($increment <=> 0));
          // Upon error, throw an exception to log rollback failure
          if (!$service->fetchResult())
            throw new \Exception('Unable to rollback transaction (original '.
              'failure caused by '.($fail ?? get_class($service)).').');
          // This should never even happen unless programmer error
        } else throw new \Exception('An unexpected error occurred (original '.
          'failure caused by '.($fail ?? get_class($service)).').');
      } // Throw an exception if the transaction failed
      if (!is_null($fail))
        throw new \Exception('Transaction failed with no changes made '.
          '(original failure caused by '.$fail.')');
    }
  }
