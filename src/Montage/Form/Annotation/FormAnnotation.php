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
    $this->annotateFields();
  
  }//method
  
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
  
    // @todo  this can probably be removed when I implement file field support as
    // it can be inferred, basically, if there is a file, then it is encoded 
    // using ENCODING_FILE, otherwise ENCODING_POST
    if($encoding = $rdocblock->getTag('encoding')){
    
      $this->form->setEncoding($encoding);
      
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
   */
  protected function annotateFields(){
  
    // check property specific annotations...
    $rparam_list = $this->reflection->getProperties(ReflectionProperty::IS_PUBLIC);
    foreach($rparam_list as $rparam){
    
      $field_annotation = new FieldAnnotation($rparam,$this->form);
      $field_annotation->populate();
    
    }//foreach
  
  }//method

}//class     
