<?php

/**
 *  the base class for any form element     
 *   
 *  @abstract 
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 1-2-10
 *  @package montage
 *  @subpackage form
 ******************************************************************************/
abstract class montage_form_field extends montage_form_base {

  protected $label = '';
  protected $desc = '';

  public function __construct(){
  
    $this->setRandomId();
  
  }//method

  /**#@+
   *  access methods for the name of the form field/element       
   */
  public function setName($val){ $this->setAttr('name',$val); }//method
  public function hasName(){ return $this->hasAttr('name'); }//method
  public function getName(){ return $this->getAttr('name'); }//method
  /**#@-*/
  
  /**#@+
   *  access methods for the value of the form field/element       
   */
  public function setVal($val){ return $this->setAttr('value',$val); }//method
  public function hasVal(){ return $this->hasAttr('value'); }//method
  public function getVal(){ return $this->getAttr('value'); }//method
  public function clearVal(){ return $this->clearAttr('value'); }//method
  /**#@-*/
  
  /**#@+
   *  access methods for the label element of the field       
   */
  public function setLabel($val){ $this->label = $val; }//method
  public function hasLabel(){ return !empty($this->label); }//method
  public function getLabel(){ return $this->label; }//method
  public function outLabel(){
  
    // canary...
    if(!$this->hasLabel()){ return ''; }//if
    if(!$this->hasId()){ return ''; }//if
    return sprintf('<label for="%s">%s</label>',$this->getId(),$this->getLabel());
  
  }//method
  /**#@-*/
  
  /**#@+
   *  access methods for the description of the field
   *  
   *  this is handy for attaching an example, or helpful message about how to fill out
   *  the element, for the user                
   */
  public function setDesc($val){ $this->desc = $val; }//method
  public function hasDesc(){ return !empty($this->desc); }//method
  public function getDesc(){ return $this->desc; }//method
  public function outDesc(){
  
    // canary...
    if(!$this->hasDesc()){ return ''; }//if
    return sprintf('<p class="desc">%s</p>',$this->getDesc());
  
  }//method
  /**#@-*/
  
  /**
   *  set a random id
   */        
  public function setRandomId(){
    $this->setId(sprintf('id%s',rand(0,500000)));
  }//method

}//class     
