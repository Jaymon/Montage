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

use Montage\Autoload\AutoLoader;
use Montage\Dependency\Reflection;

class ReflectionAutoLoader extends Autoloader {

  protected $reflection = null;

  public function __construct(Reflection $reflection){
  
    $this->reflection = $reflection;
  
  }//method

  /**
   *  this is what will do the actual loading of each autoloader
   *  
   *  @param  string  $class_name
   */
  public function handle($class_name){

    // canary...
    if(!$this->reflection->hasClass($class_name)){ return false; }//if

    $ret_bool = false;

    if($class_map = $this->reflection->getClass($class_name)){
    
      require($class_map['path']);
      $ret_bool = true;
    
    }//if

    return $ret_bool;
  
  }//method

}//class     
