<?php
/**
 *  handles deciding which start classes should be instantiated and what method to
 *  call of each class
 *  
 *  @version 0.2
 *  @author Jay Marcyes
 *  @since 6-23-11
 *  @package montage
 *  @subpackage Start 
 ******************************************************************************/       
namespace Montage\Start;

use Montage\Reflection\ReflectionFramework;

class Select {
  
  /**
   *  this is the interface a class has to implement to be considered a controller
   *  
   *  @var  string
   */
  protected $class_interface = '\Montage\Start\Startable';
  
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
  public function find($prefix){
  
    $reflection = $this->reflection;
    $class_name_list = array();
  
    // get the framework start class, this is where the frameworks configuration resides...
    $class_name = $this->findFrameworkClassName();
    if(!empty($class_name)){ $class_name_list[] = $class_name; }//if
    
    // get the application start class, this is where most of the user configuration resides...
    $class_name = $this->findAppClassName($prefix);
    if(!empty($class_name)){ $class_name_list[] = $class_name; }//if
    
    // get all the other known start classes (eg, plugins)...
    $other_class_name_list = $reflection->findClassNames($this->class_interface,$class_name_list);
    
    return array_merge($class_name_list,$other_class_name_list);
    
  }//method
  
  /**
   *  find the framework class name
   *
   *  @return string  a full namespaced class name      
   */
  public function findFrameworkClassName(){
  
    $reflection = $this->reflection;
  
    $class_name = '\\Montage\\Start\\FrameworkStart';
    if($reflection->isChildClass($class_name,$this->class_interface)){
    
      $class_name = $reflection->findClassName('\\Montage\\Start\\FrameworkStart');
    
    }//if
  
    return $class_name;
  
  }//method
  
  /**
   *  find the framework class name
   *
   *  @param  string  $prefix what name to prepend to Start   
   *  @return string  a full namespaced class name      
   */
  public function findAppClassName($prefix){
  
    $reflection = $this->reflection;
    
    // start application...
    $class_name = sprintf('\\Start\\%sStart',$prefix);
    if(!$reflection->isChildClass($class_name,$this->class_interface)){
    
      $class_name = '\\Start\\Start';
      if(!$reflection->isChildClass($class_name,$this->class_interface)){
        $class_name = '';
      }//if
    
    }//if
    
    if(!empty($class_name)){
    
      $class_name = $reflection->findClassName($class_name);
      
    }//if
  
    return $class_name;
  
  }//method
  
}//class
