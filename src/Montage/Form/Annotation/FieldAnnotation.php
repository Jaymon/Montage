<?php
/**
 *  this class provides annotation support for the field class
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 11-4-11
 *  @package montage
 *  @subpackage form  
 ******************************************************************************/
namespace Montage\Form\Annotation;

use Montage\Form\Form;

use Montage\Annotation\ParamAnnotation;

class FieldAnnotation extends ParamAnnotation {
  
  /**
   *  the form instance that the field belongs to
   *  
   *  @var  \Montage\Form\Form  
   */
  protected $form = null;
  
  /**
   *  hold the parent class that will be used to {@link populate()} the fields
   *  
   *  @since  10-24-11   
   *  @var  string  the full namespaced class
   */
  protected $field_class_name = 'Montage\\Form\\Field\\Field';
  
  /**
   *  these are the valid form field types
   *  
   *  basically, if your $var class_name isn't an absolute namespaced class
   *  (eg, \Namespace\Class\Name) then it has to be one of the keys in this array
   *  to be picked up by the annotations               
   *
   *  @var  array   
   */
  protected $form_field_map = array(
    'Textarea' => 'Montage\\Form\\Field\\Textarea',
    'Input' => 'Montage\\Form\\Field\\Input',
    'Submit' => 'Montage\\Form\\Field\\Submit'
  );
  
  /**
   *  @param  \ReflectionProperty $reflection a property of $form
   *  @param  \Montage\Form\Form  $form the form of the property      
   */
  public function __construct(\ReflectionProperty $reflection,Form $form){
  
    parent::__construct($reflection);
  
    $this->form = $form;
  
  }//method
  
  /**
   *  creates a field if the passed in param is annotated correctly
   *
   *  @return Field   
   */
  public function populate(){
  
    // canary...
    $rdocblock = $this->getDocBlock();
    if(empty($rdocblock)){ return; }//if
  
    $ret_field = null;
  
    $this->reflection->setAccessible(true);
    
    $val = $this->reflection->getValue($this);
    
    if(($val === null)){
      
      if($class_name = $this->getClassName()){
        
        // if we don't have an absolute class name, then we need to check if it is a supported
        // class name...
        if($class_name[0] !== '\\'){
        
          foreach($this->form_field_map as $form_field => $form_field_class_name){
          
            if(preg_match(sprintf('#%s$#i',$form_field),$class_name)){
            
              $class_name = $form_field_class_name;
              break;
            
            }//if
          
          }//foreach
        
        }//if
        
      }//if
        
      if(!empty($class_name)){
      
        // make sure the class name is a Field descendant, ignore everything else...  
        if(class_exists($class_name) && is_subclass_of($class_name,$this->field_class_name)){
        
          $instance = $this->createField($class_name);
        
          $this->reflection->setValue(
            $this->reflection->isStatic() ? null : $this->form,
            $instance
          );
          
          $ret_field = $instance;
        
        }//if
        
      }//if
      
    }//if
    
    return $ret_field;
    
  }//method
  
  /**
   *  create the field instance
   *  
   *  @param  string  $class_name the field class to be created
   *  @param  \ReflectionProperty $rparam the class property that was found to contain a field class
   *  @param  Annotation  $rdocblock  the property's docblock      
   *  @return Field a field instance
   */
  protected function createField($class_name){
  
    $name = $this->reflection->getName();
    $rdocblock = $this->getDocBlock();
    
    $instance = new $class_name();
    $instance->setName($name);
    $instance->setForm($this->form);
    
    if($desc = $rdocblock->getShortDesc()){
    
      $instance->setDesc($desc);
    
    }//if
    
    if($type = $rdocblock->getTag('type')){
    
      if(method_exists($instance,'setType')){
      
        $instance->setType($type);
        
      }//if
    
    }//if
  
    if($label = $rdocblock->getTag('label')){
    
      $instance->setLabel($label);
      
    }else{
    
      $instance->setLabel($name);
    
    }//if/else
  
    return $instance;
  
  }//method

}//class     
