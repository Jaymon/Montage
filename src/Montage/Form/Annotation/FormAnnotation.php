<?php
/**
 *  this class provides annotation support for the form class
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 11-4-11
 *  @package montage
 *  @subpackage form  
 ******************************************************************************/
namespace Montage\Form\Annotation;

use Montage\Form\Form;
use Montage\Annotation\Annotation;

use ReflectionProperty;

class FormAnnotation extends Annotation {
  
  /**
   *  the internal form instance
   *  
   *  @var  Montage\Form\Form
   */
  protected $form = null;
  
  /**
   *  hold all the fields that were created with this class
   * 
   *  @since  11-9-11    
   *  @var  array      
   */
  protected $field_map = array();
  
  /**
   *  create instance
   *  
   *  @param  \Montage\Form\Form  $form
   */
  public function __construct(Form $form){
  
    $this->form = $form;
    $rform = new \ReflectionObject($form);
    
    parent::__construct($rform);
    
  }//method
  
  /**
   *  go through and use annotions to populate the form
   */
  public function populate(){
  
    $this->annotateForm();
    $this->field_map = $this->annotateFields();
  
  }//method
  
  /**
   *  return the fields that were annotated with this class
   * 
   *  @since  11-9-11    
   *  @return array      
   */
  public function getFields(){ return $this->field_map; }//method
  
  /**
   *  Form specific annotation
   *  
   *  this will use the docblock on the form class itself to set certain properties of the form
   */
  protected function annotateForm(){
  
    //canary...
    $rdocblock = $this->getDocBlock();
    if(empty($rdocblock)){ return; }//if
  
    if($method = $rdocblock->getTag('method')){
    
      $this->form->setMethod($method);
      
    }//if
    
    // @todo  just like encoding, I think this might be short for this world, I originally
    // liked the idea of having the url set in the form, but that was when the Url class
    // was static, so the forms could do stuff like Url::get(...). Now that I've moved
    // to the current Montage model setting the Url internally is a lot harder since it
    // requires a \Montage\Url dependency. So I've been setting it when rendering the form
    // by passing it in as an attribute to renderStart(array('action' => 'url')); 
    if($url = $rdocblock->getTag('url')){
    
      $this->form->setUrl($url);
      
    }//if
    
  }//method
  
  /**
   *  use docblocks of public form properties to create and set the fields of the form
   *  
   *  @return array a list of the fields that were found         
   */
  protected function annotateFields(){
  
    $ret_map = array();
  
    // check property specific annotations...
    $rparam_list = $this->reflection->getProperties(ReflectionProperty::IS_PUBLIC);
    foreach($rparam_list as $rparam){
    
      $field_annotation = new FieldAnnotation($rparam,$this->form);
      if($ret_field = $field_annotation->populate()){
      
        $ret_map[$rparam->getName()] = $ret_field;
      
      }//method
    
    }//foreach
  
    return $ret_map;
  
  }//method

}//class     
