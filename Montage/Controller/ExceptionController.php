<?php

namespace Montage\Controller;

use Montage\Controller\Controller;

class ExceptionController extends Controller {

  /**
   *  overrides the parent to get rid of the dependencies since this could be
   *  called before all dependencies are sorted, which would cause a fatal error
   *  since the dependencies can't be found
   */
  public function __construct(){}//method

  public function handleIndex(\Exception $e){
  
    if(strncasecmp(PHP_SAPI, 'cli', 3) === 0){
    
      echo $e; // CLI
    
    }else{
    
      echo nl2br($e); // html
    
    }//if/else
  
  }//method

}//class
