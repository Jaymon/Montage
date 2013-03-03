<?php

namespace Montage\Controller;

class DefaultEndpoint extends Endpoint {

  /**
   * by default, this will be printed out to let you know that Montage is installed and running
   *
   * THIS METHOD WILL ALMOST ALWAYS BE OVERRIDDEN!!!
   *
   */
  public function handleDefault(array $params = array()){

    //throw new \RuntimeException("this is a test");
    //throw new \Montage\Exception\HttpException(500, "blah");

    $eol = '<br />'; // this can no longer ever be called by a command

    // TODO: move all this to params and create a success view that will print this out
    // TODO: set request->setTitle()
    echo "<h1>Congratulations on getting Montage up and running</h1>";
    echo $eol;
    echo $eol;

    echo sprintf('<p>Your Request was interpreted as: %s(%s)</p>', __METHOD__, join(',',$params)), $eol;
    
    if(!empty($params[0])){
    
      echo sprintf(
        '<p>Your Request could have been: \\Namespace\\%sEndpoint::handleDefault(%s)</p>',
        ucfirst($params[0]), join(',', array_slice($params, 1))
      );
      echo $eol;
      
      echo sprintf(
        '<p>Your Request could have been: \\Namespace\\DefaultEndpoint::handle%s(%s)</p>',
        ucfirst($params[0]), join(',', array_slice($params, 1))
      );
      echo $eol;
      
      if(!empty($params[1])){
    
        echo sprintf(
          '<p>Your Request could have been: \\Namespace\\%sEndpoint::handle%s(%s)</p>',
          ucfirst($params[0]), ucfirst($params[1]), join(',', array_slice($params, 2))
        );
        echo $eol;
      
      }//if
      
    }//if

    return false;
    
  }//method

}//class

