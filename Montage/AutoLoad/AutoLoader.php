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
  public function register(){
    spl_autoload_register(array($this,'handle'));
  }//method
  
  /**
   *  unregister this class as an autoloader
   */
  public function unregister(){
    spl_autoload_unregister(array($this,'handle'));
  }//method

}//class     
