<?php

/**
 *  the base class for any form element     
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 1-2-10
 *  @package montage
 *  @subpackage form
 ******************************************************************************/
abstract class montage_form_field extends montage_form_base {

  protected $label = '';
  protected $desc = '';

  function __construct(){
  
    $this->setId(sprintf('id%s',rand(0,500000)));
  
  }//method

  /**#@+
   *  access methods for the name of the form field/element       
   */
  function setName($val){ $this->setAttr('name',$val); }//method
  function hasName(){ return $this->hasAttr('name'); }//method
  function getName(){ return $this->getAttr('name'); }//method
  /**#@-*/
  
  /**#@+
   *  access methods for the value of the form field/element       
   */
  function setVal($val){ return $this->setAttr('value',$val); }//method
  function hasVal(){ return $this->hasAttr('value'); }//method
  function getVal(){ return $this->getAttr('value'); }//method
  function clearVal(){ return $this->clearAttr('value'); }//method
  /**#@-*/
  
  /**#@+
   *  access methods for the label element of the field       
   */
  function setLabel($val){ $this->label = $val; }//method
  function hasLabel(){ return !empty($this->label); }//method
  function getLabel(){ return $this->label; }//method
  function outLabel(){
  
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
  function setDesc($val){ $this->desc = $val; }//method
  function hasDesc(){ return !empty($this->desc); }//method
  function getDesc(){ return $this->desc; }//method
  function outDesc(){
  
    // canary...
    if(!$this->hasDesc()){ return ''; }//if
    return sprintf('<p class="desc">%s</p>',$this->getDesc());
  
  }//method
  /**#@-*/

}//class     
