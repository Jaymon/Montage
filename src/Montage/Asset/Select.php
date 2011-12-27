<?php
/**
 *  handles deciding what classes that implement Assets
 *  
 *  @version 0.2
 *  @author Jay Marcyes
 *  @since 9-26-11
 *  @package montage
 *  @subpackage Asset
 ******************************************************************************/       
namespace Montage\Asset;

use Montage\Reflection\ReflectionFramework;

class Select {
  
  /**
   *  this is the parent class a class has to extend to be considered an asset
   *  
   *  @var  string
   */
  protected $class_extend = '\\Montage\\Asset\\Assets';
  
  /**
   *  holds the information about what classes exist in the system
   *
   *  @var  \Montage\Reflection\ReflectionFramework
   */
  protected $reflection = null;
  
  /**
   *  create instance of this class
   *  
   *  @param  \Montage\Reflection\ReflectionFramework $reflection needed to be able to find suitable classes            
   */
  function __construct(ReflectionFramework $reflection){
  
    $this->reflection = $reflection;
  
  }//method
  
  /**
   *  find all the class names that should be instantiated
   * 
   *  @return array a list of fully namespaced class names
   */
  public function find(array $ignore_list = array()){
  
    return $this->reflection->findClassNames($this->class_extend,$ignore_list);
    
  }//method
  
}//class
