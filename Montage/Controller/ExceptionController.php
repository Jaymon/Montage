<?php

namespace Montage\Controller;

use Montage\Controller\Controller;
use Montage\Exception\StopException;

class ExceptionController extends Controller {

  /**
   *  overrides the parent to get rid of the dependencies since this could be
   *  called before all dependencies are sorted, which would cause a fatal error
   *  since the dependencies can't be found
   */
  public function __construct(){}//method

  public function handleIndex(\Exception $e){
  
    $title = sprintf('Exception handled by %s',__METHOD__);
  
    if(strncasecmp(PHP_SAPI, 'cli', 3) === 0){
    
      echo $title,PHP_EOL,PHP_EOL;
      echo $e; // CLI
    
    }else{
    
      echo $title,'<br><br>';
      echo nl2br($e); // html
    
    }//if/else
     
    return false;
  
  }//method

}//class
