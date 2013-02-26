<?php
/**
 *  handles deciding what classes that implement subscribeable should be started
 *  
 *  @version 0.2
 *  @author Jay Marcyes
 *  @since 8-25-11
 *  @package montage
 *  @subpackage Event
 ******************************************************************************/       
namespace Montage\Event;

use Montage\Reflection\ReflectionFramework;

class Select {
  
  /**
   *  this is the interface a class has to implement
   *  
   *  @var  string
   */
  protected $class_interface = '\\Montage\\Event\\Subscribeable';
  
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
    return $reflection->findClassNames($this->class_interface);
    
  }//method
  
}//class
