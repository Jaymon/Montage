<?php
/**
 *  handles deciding what classes that implement Assets
 *  
 *  @version 0.3
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
  public $reflection = null;
  
  /**
   *  @since  12-29-11   
   *  @var  \Montage\Asset\FrameworkAssets
   */
  public $framework_assets = null;
  
  /**
   *  find all the class names that should be instantiated
   * 
   *  @return array a list of fully namespaced class names
   */
  public function find(){
  
    $ignore_list = array(get_class($this->framework_assets));
  
    return $this->reflection->findClassNames($this->class_extend,$ignore_list);
    
  }//method
  
}//class
