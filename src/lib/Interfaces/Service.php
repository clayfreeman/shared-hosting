<?php
 /**
  * @copyright  Copyright 2017 Clay Freeman. All rights reserved.
  * @license    GNU Lesser General Public License v3 (LGPL-3.0).
  */

  namespace SharedHosting\Interfaces;

  interface Service {
    public function fetchResult(): bool;
    public function     forward(): void;
    public function     reverse(): void;
  }
