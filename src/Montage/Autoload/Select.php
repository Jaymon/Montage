<?php
/**
 *  handles deciding which autoload classes should be instantiated and what method to
 *  call of each class
 *  
 *  @version 0.2
 *  @author Jay Marcyes
 *  @since 6-23-11
 *  @package montage
 *  @subpackage Start 
 ******************************************************************************/       
namespace Montage\Autoload;

use Montage\Reflection\ReflectionFramework;

class Select {
  
  /**
   *  this is the interface a class has to implement to be considered a controller
   *  
   *  @var  string
   */
  protected $class_interface = '\Montage\Autoload\Autoloadable';
  
  /**
   *  holds the information about what classes exist in the system
   *
   *  @var  \Montage\Reflection\ReflectionFramework
   */
  protected $reflection = null;
  
  /**
   *  create instance of this class
   *  
   *  @param  \Montage\Reflection\ReflectionFramework $reflection needed to be able to find suitable start classes            
   */
  function __construct(ReflectionFramework $reflection){
  
    $this->reflection = $reflection;
  
  }//method
  
  /**
   *  find all the class names that should be instantiated
   *  
   *  @param  string  $prefix see {@link findAppClassName()}
   *  @return array a list of fully namespaced class names
   */
  public function find(){
  
    $reflection = $this->reflection;
    
    // build a list of all the currently defined autoload classes...
    $class_name_list = array();
    foreach(spl_autoload_functions() as $function){
    
      if(is_object($function)){
      
        $class_name_list[] = get_class($function);
        
      }else if(is_array($function)){
        
        if(is_object($function[0])){ $class_name_list[] = get_class($function[0]); }//if
        
      }//if/else if
    
    }//foreach

    // get all the unregistered autoloaders so they can be registered... 
    return $reflection->findClassNames($this->class_interface,$class_name_list);
    
  }//method
  
}//class
