<?php

namespace Montage\Controller;

use Montage\Controller\CliController;

class PHPUnitController extends CliController {

  public function handleIndex(array $params = array()){
  
    \out::h();
    
    return false;
  
  }//method

}//class
