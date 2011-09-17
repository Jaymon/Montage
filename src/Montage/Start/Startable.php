<?php
/**
 *  all start classes should use this interface 
 *  
 *  this interface is the skeleton for the Framework's start classes, the start classes
 *  are classes that handle framework/app configuration every time the app runs. The
 *  {@link handle{)} method is called in the {@link Montage\Framework::handleStart()} method.
 *  
 *  @example
 *    class DevStart implements Montage\Start\Startable {
 *      public function handle(){}//method  
 *    }       
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-17-11
 *  @package montage
 *  @subpackage Start
 ******************************************************************************/      
namespace Montage\Start;

interface Startable {

  /**
   *  handle the meat of the configuration 
   *
   *  @since  9-16-11
   */
  public function handle();

}//interface
