<?php

/**
 *  the base class for any form element     
 *   
 *  @abstract 
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 1-2-10
 *  @package montage
 *  @subpackage form
 ******************************************************************************/
namespace Montage\Form\Field;

use Montage\Form\Common;
use Montage\Form\Form;

abstract class Field extends Common {

  protected $label = '';
  protected $desc = '';
  
  /**
   *  holds ths form instance that this field belongs to
   *   
   *  @since  10-24-11
   *  @var  \Montage\Form\Form   
   */
  protected $form = null;

  /**
   *  create a field instance
   *  
   *  @param  string  $name the name of this field
   *  @param  mixed $val  the value of this field
   */
  public function __construct($name = '',$val = null){

    $this->setName($name);
    $this->setVal($val);
  
  }//method
  
  public function setForm(Form $val){ return $this->form = $val; }//method
  public function hasForm(){ return !empty($this->form); }//method
  public function getForm(){ return $this->form; }//method
  
  /**#@+
   *  access methods for the value of the form field/element       
   */
  public function setVal($val){ return $this->setAttr('value',$val); }//method
  public function hasVal(){ return $this->hasAttr('value'); }//method
  public function getVal(){ return $this->getAttr('value'); }//method
  public function killVal(){ return $this->killAttr('value'); }//method
  /**#@-*/
  
  /**#@+
   *  access methods for the label element of the field       
   */
  public function setLabel($val){ $this->label = $val; }//method
  public function hasLabel(){ return !empty($this->label); }//method
  public function getLabel(){ return $this->label; }//method
  public function renderLabel($label = '',array $attr_map = array()){
  
    $ret_str = '';
    
    if(empty($label)){ $label = $this->getLabel(); }//if
    
    if(!empty($label)){
      
      if(!$this->hasId()){
        $prefix = $this->hasForm() ? $this->getForm()->getName() : '';
        $this->setId($this->getRandomId($prefix));
      }//if
      
      if($attr_str = parent::renderAttr($attr_map)){ $attr_str = ' '.$attr_str; }//if
      
      
      $ret_str = sprintf('<label for="%s"%s>%s</label>',$this->getId(),$attr_str,$label);
      
    }//if
    
    return $ret_str;
  
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
  public function renderDesc(){
  
    // canary...
    if(!$this->hasDesc()){ return ''; }//if
    return sprintf('<p class="desc">%s</p>',$this->getDesc());
  
  }//method
  /**#@-*/
  
  /**
   *  override parent to do Field specific things
   *  
   *  @see  parent::renderAttr()
   */
  public function renderAttr(array $attr_map = array()){
    
    $name = isset($attr_map['name']) ? $attr_map['name'] : $this->getName();
    
    $name_map = $this->getNameInfo($name);
    $attr_map['name'] = $name_map['form_name'];
    
    if(!$this->hasId()){
    
      $prefix = $this->hasForm() ? $this->getForm()->getName() : '';
      $attr_map['id'] = $this->getRandomId($prefix);
    
    }//if
    
    return parent::renderAttr($attr_map);
    
  }//method
  
  /**
   *  gets the form specific name of the given $name, also normalizes $name
   *  
   *  name - the actual input name, this is the raw full name, so it would be foo[] if foo[] was passed in   
   *  short_name - if name was foo[] then it should be foo
   *  form_name - would be Formname[foo] or Formname[foo][] if foo was an array      
   *      
   *  @param  string  $name
   *  @return array an array with keys like name and short_name set   
   */
  protected function getNameInfo($name){
    
    $ret_map = array();
    $ret_map['name'] = $ret_map['short_name'] = $ret_map['form_name'] = $name;
    $ret_map['is_array'] = false;
    $ret_map['index'] = '';
    
    $postfix = '';
    
     // see if we have an array name...
    $index = mb_strpos($name,'[');
    if($index !== false){

      // $name is an array itself so we need to compensate to namespace it with the form name also...
      // form_name[$name][] works, form_name[$name[]] does not.
      
      $postfix = mb_substr($name,$index); // [...]
      $ret_map['short_name'] = mb_substr($name,0,$index); // the left of the [, so if you had foo[], it would be foo
      $ret_map['index'] = trim($postfix,'[]'); // if you had foo[2] the index would be 2
      $ret_map['is_array'] = true;
      
    }//if

    if($this->hasForm()){

      $ret_map['form_name'] = sprintf('%s[%s]%s',$this->getForm()->getName(),$ret_map['short_name'],$postfix);
      
    }//if

    return $ret_map;
    
  }//method

}//class     
