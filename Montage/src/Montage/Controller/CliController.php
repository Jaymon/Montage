<?php

namespace Montage\Controller;

use Montage\Controller\Controller;

abstract class CliController extends Controller {

  public function preHandle(){
  
    if(!$this->request->isCli()){
    
      throw new \RuntimeException('Only command line requests are allowed');
    
    }//if
  
  }//method

  public function handleIndex(array $params = array()){}//method

}//class
