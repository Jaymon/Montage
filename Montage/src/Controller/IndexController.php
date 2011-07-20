<?php

namespace Montage\Controller;

use Montage\Controller\Controller;

class IndexController extends Controller {

  public function handleIndex(array $params = array()){
  
    $eol = $this->request->isCli() ? PHP_EOL : '<br />';

    echo sprintf('Your Request was interpreted as: %s(%s)',__METHOD__,join(',',$params)),$eol;
    
    if(!empty($params[0])){
    
      echo sprintf(
        'Your Request could have been: Controller\%sController::handleIndex(%s)',
        ucfirst($params[0]),join(',',array_slice($params,1))
      );
      echo $eol;
      
      echo sprintf(
        'Your Request could have been: Controller\IndexController::handle%s(%s)',
        ucfirst($params[0]),join(',',array_slice($params,1))
      );
      echo $eol;
      
      if(!empty($params[1])){
    
        echo sprintf(
          'Your Request could have been: Controller\%sController::handle%s(%s)',
          ucfirst($params[0]),ucfirst($params[1]),join(',',array_slice($params,2))
        );
        echo $eol;
      
      }//if
      
    }//if
    
  }//method

}//class
