<?php

/**
 *  the object that represents an <input> html element
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 1-2-10
 *  @package montage
 *  @subpackage form
 ******************************************************************************/
class input_field extends montage_form_field {

  /**#@+
   *  the different types an input field can contain
   *
   *  @link http://www.w3schools.com/tags/tag_input.asp   
   */        
  const TYPE_BUTTON = 'button';
  const TYPE_CHECKBOX = 'checkbox';
  const TYPE_FILE = 'file';
  const TYPE_HIDDEN = 'hidden';
  const TYPE_IMAGE = 'image';
  const TYPE_PASSWORD = 'password';
  const TYPE_RADIO = 'radio';
  const TYPE_RESET = 'reset';
  const TYPE_SUBMIT = 'submit';
  const TYPE_TEXT = 'text';
  /**#@-*/
  
  /**#@+
   *  the different html5 types an input field can contain
   *
   *  @link http://diveintohtml5.org/forms.html
   */
  const TYPE_SEARCH = 'search';
  const TYPE_EMAIL = 'email';
  const TYPE_URL = 'url';
  /**#@+
   *  you can set attributes: min, max, and step when input is this type
   */     
  const TYPE_NUMBER = 'number';
  const TYPE_RANGE = 'range';
  /**#@-*/
  const TYPE_DATE = 'date';
  const TYPE_MONTH = 'month';
  const TYPE_WEEK = 'week';
  const TYPE_TIME = 'time';
  const TYPE_DATETIME = 'datetime';
  const TYPE_DATETIME_LOCAL = 'datetime-local';
  /**#@-*/

  /**#@+
   *  access methods for the type of the form field/element       
   */
  function setType($val){
  
    $this->setAttr('type',$val);
    
    if($this->isType(self::TYPE_SUBMIT)){
      if(!$this->hasName()){ $this->setName(self::TYPE_SUBMIT); }//if
    }//if
    
  }//method
  function hasType(){ return $this->hasAttr('type'); }//method
  function getType(){ return $this->getAttr('type',self::TYPE_TEXT); }//method
  function isType($val){ return $this->isAttr('type',$val); }//method
  /**#@-*/
  
  /**#@+
   *  set the placeholder text for the input
   *  
   *  via: http://diveintohtml5.org/forms.html   
   *  Q: Can I use HTML markup in the placeholder attribute? I want to insert an image, or maybe change the colors. 
   *  A: No, the placeholder attribute can only contain text.
   *      
   *  html5 attribute             
   */
  function setPlaceholder($val){ return $this->setAttr('placeholder',$val); }//method
  function hasPlaceholder(){ return $this->hasAttr('placeholder'); }//method
  function getPlaceholder(){ return $this->getAttr('placeholder'); }//method
  /**#@-*/
  
  function out(){
  
    // make sure the value is safe...
    $this->setVal($this->getSafe($this->getVal()));
  
    return sprintf('<input%s/>',$this->outAttr());
    
  }//method

}//class     
