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
  public function register($prepend = false){
    spl_autoload_register($this->getCallback(),true,$prepend);
  }//method
  
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
  
  /**
   *  normalizes the class name to the standards set by the portable autoloader requirements
   *   
   *  implements the portable autoloader requirements:
   *  http://groups.google.com/group/php-standards/web/psr-0-final-proposal   
   *
   *  @since  7-19-11   
   *  @param  string  $class_name the class name that is being normalized
   *  @return string  the class name, normalized
   */
  protected function normalizeClassName($class_name){
  
    $class_name = ltrim($class_name, '\\'); // get rid of absolute
    $namespace = array();
    
    $class_bits = explode('\\',$class_name);
    
    // if a second item was set then there is a namespace...
    if(isset($class_bits[1])){
      
      end($class_bits);
      $class_name = $class_bits[key($class_bits)]; // last bit is the class name
      $namespace = array_slice($class_bits,0,-1); // everything else is namespace
      
    }//if
    
    $ret_name = 
      join(DIRECTORY_SEPARATOR,$namespace)
      .DIRECTORY_SEPARATOR.
      str_replace('_',DIRECTORY_SEPARATOR,$class_name)
      .'.php';
  
    return $ret_name;
  
  }//method

}//class     
