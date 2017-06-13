<?php
  namespace SharedHosting\Interfaces;

  interface Service {
    public function fetchResult(bool $batch): bool;
    public function     forward(): void;
    public function     reverse(): void;
  }
