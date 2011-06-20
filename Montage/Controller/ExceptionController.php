<?php

namespace Montage\Controller;

use Montage\Controller\Controller;

class ExceptionController extends Controller {

  public function handleIndex(\Exception $e){
  
    echo $e;
  
  }//method

}//class
