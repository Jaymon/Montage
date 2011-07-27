<?php

namespace PHPUnit\Controller;

use Montage\Controller\Controller;
use Montage\Config\FrameworkConfig;
use Montage\Request\Request;
use Montage\Response\Response;

class PHPUnitController extends Controller {

  protected $framework_config = null;

  public function __construct(FrameworkConfig $framework_config){
  
    $this->framework_config = $framework_config;
  
  }//method

  public function preHandle(){
  
    if(!$this->request->isCli()){
    
      throw new \RuntimeException('Only command line requests are allowed for PHPUnit');
    
    }//if
  
  }//method

  public function handleIndex(array $params = array()){
  
    \out::i($this->framework_config);
    
    return false;
  
  }//method

}//class
