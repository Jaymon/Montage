<?php

/**
 *  all controller class should extend this class 
 *  
 *  @abstract 
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-19-10
 *  @package montage 
 ******************************************************************************/
abstract class montage_controller extends montage_base {

  final public function __construct(){
    $this->start();
  }//method

  /**
   *  this is the default controller method for this controller
   *  
   *  this method is called if this controller is activated but no other method is given
   *      
   *  @return boolean like all controller methods if true, then the template will be rendered, 
   *                  if false, then montage::getResponse()->get() will be used instead of the template
   */
  abstract public function handleIndex();
  
  /**
   *  after calling the handle* method, this method is run
   */
  abstract public function stop();

}//class     
