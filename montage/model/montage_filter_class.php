<?php

/**
 *  if you want any application wide start/stop classes, just have them extend this class
 *  
 *  a filter is a class that will have its start() method called before going to the controller
 *  and have its stop() method called after the caller has completed
 *  
 *  if you want controller specific start/stop then just implement the controller's
 *  own start() and stop() methods      
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-20-10
 *  @package montage 
 ******************************************************************************/
abstract class montage_filter extends montage_base {

  final function __construct(){}//method

  /**
   *  this method will be called before the controller is called
   */
  abstract function start();
  
  /**
   *  this method will be called after the controller is called
   */
  abstract function stop();

}//class     
