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

  final function __construct(){}//method

  /**
   *  before calling the get* method, run this method
   *  
   *  the reason why this is abstract and required is so you know about it and
   *  it isn't one of those magic things that montage can do, if you don't want
   *  to use it just put "function start(){}" in your controller child
   */
  abstract function start();
  
  /**
   *  after calling the get* method, run this method
   */
  abstract function stop();

}//class     
