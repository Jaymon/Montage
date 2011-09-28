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
   *  this is the parent class a class has to extend to be considered an asset
   *  
   *  @var  string
   */
  protected $class_extend = '\Montage\Asset\Assets';
  
  /**
   *  this is the parent class a class has to extend to be considered a catchall asset
   *  
   *  @var  string
   */
  protected $class_catchall = '\Montage\Asset\FrameworkAssets';
  
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
  
    return $this->reflection->findClassNames($this->class_extend,$this->class_catchall);
    
  }//method
  
  /**
   *  find the catch-all class
   * 
   *  @return string  the catch-all class
   */
  public function findCatchAll(){
  
    return $this->reflection->findClassName($this->class_catchall);
    
  }//method
  
}//class
