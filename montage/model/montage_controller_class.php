<?php

/**
 *  all controller class should extend this class
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-19-10
 *  @package montage 
 ******************************************************************************/
abstract class montage_controller extends montage_base {

  final function __construct(){
    $this->start();
  }//method

  /**
   *  this is the default controller method for this controller
   *  
   *  this method is called if this controller is activated but no other method is given
   *      
   *  @return boolean|string  if true, then the template will be rendered, if string, then the
   *                          string will be echoed
   */
  abstract function getIndex();
  
  /**
   *  after calling the get* method, run this method
   */
  abstract function stop();

}//class     
