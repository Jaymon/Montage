<?php
/**
 *  handles deciding what classes that implement Assets
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 9-26-11
 *  @package montage
 *  @subpackage Asset
 ******************************************************************************/       
namespace Montage\Asset;

use Montage\Dependency\Reflection;

class Select {
  
  /**
   *  this is the interface a class has to implement to be considered an asset
   *  
   *  @var  string
   */
  protected $class_interface = '\Montage\Asset\Asset';
  
  /**
   *  holds the information about what classes exist in the system
   *
   *  @var  Reflection   
   */
  protected $reflection = null;
  
  /**
   *  create instance of this class
   *  
   *  @param  Reflection  $reflection needed to be able to find suitable classes            
   */
  function __construct(Reflection $reflection){
  
    $this->reflection = $reflection;
  
  }//method
  
  /**
   *  find all the class names that should be instantiated
   * 
   *  @return array a list of fully namespaced class names
   */
  public function find(){
  
    return $this->reflection->findClassNames($this->class_interface);
    
  }//method
  
}//class
