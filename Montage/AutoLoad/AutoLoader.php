<?php

/**
 *  handle caching
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-27-11
 *  @package montage
 *  @subpackage Autoload 
 ******************************************************************************/
namespace Montage\AutoLoad;

use Montage\Autoload\AutoLoadable;

abstract class AutoLoader implements AutoLoadable {

  /**
   *  register this class as an autoloader
   */
  public function register(){ spl_autoload_register($this->getCallback()); }//method
  
  /**
   *  unregister this class as an autoloader
   */
  public function unregister(){ spl_autoload_unregister($this->getCallback()); }//method
  
  /**
   *  get the callback that will be used to handle autoloading
   *  
   *  @since  7-5-11
   *  @return callback
   */
  public function getCallback(){ return array($this,'handle'); }//method

}//class     
