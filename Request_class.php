<?php

namespace Plugin\Symfony;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Montage\Interfaces\Request as MontageRequest;

class Request extends SymfonyRequest implements MontageRequest {

  public function __construct(){
  
    parent::__construct($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);
  
  }//method



}//class
