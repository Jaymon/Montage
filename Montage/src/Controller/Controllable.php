<?php
/**
 *  all controller classes should implement this interface 
 *  
 *  @abstract 
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-19-10
 *  @package montage 
 ******************************************************************************/      
namespace Montage\Controller;

interface Controllable {

  /**
   *  override to allow your controller to do stuff right before any handle method is called
   */
  public function preHandle();
  
  /**
   *  override to allow your controller to do stuff right after the handle method is called
   */
  public function postHandle();
  
}//class
