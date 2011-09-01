<?php

/**
 *  use the Reflection instance to load classes
 *  
 *  I would like to get rid of this and just have the StdAutoLoader but I need this
 *  to include the classes that make up the StdAutoLoader so I can then instantiate
 *  the StdAutoLoader. Basically, there is a chicken/egg problem 
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 6-27-11
 *  @package montage
 *  @subpackage Autoload  
 ******************************************************************************/
namespace Montage\AutoLoad;

use Montage\Autoload\AutoLoader;
use Montage\Dependency\Reflection;

class ReflectionAutoLoader extends AutoLoader {

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

    if($this->reflection->hasClass($class_name)){ // have to check has because getClass throws exceptions
    
      $class_map = $this->reflection->getClass($class_name);
      require($class_map['path']);
    
    }//if

  }//method

}//class     
