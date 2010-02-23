<?php

/**
 *  the base for the form and any form element     
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 1-1-10
 *  @package montage
 *  @subpackage form
 ******************************************************************************/
abstract class montage_form_base {

  /**
   *  this should never be touched unless absolutely necessary
   *  
   *  @see  setCharset()      
   *  @var  string
   */        
  protected $charset = 'UTF-8';

  /**
   *  holds attribute info 
   *  @var  array key/val pairs
   */
  protected $attr_map = array();
  
  /**
   *  holds the error message
   *  @var  string
   */
  protected $error = '';

  /**
   *  output an html representation of the instance
   *  
   *  @return string
   */
  abstract function out();
  
  /**
   *  uses the instance's defined {@link out()} method to output the class
   *  
   *  @return string
   */
  function __toString(){ return $this->out(); }//method

  /**#@+
   *  access methods for the id of the form field/element       
   */
  function setId($val){ return $this->setAttr('id',$val); }//method
  function hasId(){ return $this->hasAttr('id'); }//method
  function getId(){ return $this->getAttr('id'); }//method
  /**#@-*/

  /**#@+
   *  access methods for the class of the form field/element       
   */
  function setClass($val){ return $this->setAttr('class',$val); }//method
  function hasClass(){ return $this->hasAttr('class'); }//method
  function getClass(){ return $this->getAttr('class'); }//method
  /**#@-*/
  
  /**#@+
   *  access methods for any error message the form field has       
   */
  function setError($val){ $this->error = $val; }//method
  function hasError(){ return !empty($this->error); }//method
  function getError(){ return $this->error; }//method
  function outError(){
  
    // canary...
    if(!$this->hasError()){ return ''; }//if  
    return sprintf('<p class="error">%s</p>',$this->getError());
  
  }//method
  /**#@-*/

  /**
   *  set a generic attribute
   *  
   *  @param  mixed $args,... the different combinations of args:
   *                            1 - array($attr_name => $attr_val [,...])
   *                            2 - $attr_name,$attr_val     
   */
  function setAttr(){
  
    $args = func_get_args();
    
    if(empty($args)){
    
      throw new montage_form_exception(
        'invalid args, use ->setAttr($name,$val) or ->setAttr(array($name => $val))'
      );
    
    }else{
    
      if(is_array($args[0])){
      
        foreach($args[0] as $attr_name => $attr_val){  
          $this->attr_map[$attr_name] = $attr_val;
        }//foreach
      
      }else{
      
        if(isset($args[1])){
        
          $val = $args[1];
          if(is_array($args[1])){
          
            $val = json_encode($args[1]);
          
            /*
            $val = '{';
            foreach($args[1] as $key => $key_val){
              $val .= sprintf("%s='%s',",$key,$key_val);
            }//foreach
            $val = rtrim($val,',');
            $val .= '}';
            */
          
          }//if
        
          $this->attr_map[$args[0]] = $val;
        
        }else{
        
          throw new montage_form_exception(
            sprintf(
              'you need ($attr_name,$attr_val), you passed in: (%s)',
              join(',',$args)
            )
          );
        
        }//if/else
      
      }//if/else
    
    }//if/else
  
  }//method
  
  /**
   *  check's if an attribute exists
   *  
   *  @param  string  $name the name of the attribute   
   *  @return boolean
   */
  function hasAttr($name){ return !empty($this->attr_map[$name]); }//method
  
  /**
   *  check's if an attribute exists and is equal to $val
   *  
   *  @param  string  $name the name of the attribute
   *  @param  string  $val  the attributes value      
   *  @return boolean
   */
  function isAttr($name,$val){
    $ret_bool = false;
    if(isset($this->attr_map[$name])){
      $ret_bool = $this->attr_map[$name] == $val;
    }//if
    return $ret_bool;
  }//method
  
  /**
   *  return's an attribute's current value
   *  
   *  @param  string  $name the name of the attribute 
   *  @param  mixed $default_val  the default value of the attribute if it doesn't exist   
   *  @return mixed
   */
  function getAttr($name,$default_val = ''){ return $this->hasAttr($name) ? $this->attr_map[$name] : $default_val; }//method

  /**
   *  clears an attibute from the list
   *  
   *  @param  string  $name the name of the attribute
   *  @param  string  $val  the attributes value      
   *  @return boolean
   */
  function clearAttr($name){
    if($this->hasAttr($name)){
      unset($this->attr_map[$name]);
    }//if
  }//method

  /**#@+
   *  access methods for the form's charset     
   */
  function setCharset($val){
    if(!empty($val)){ $this->charset = $val; }//if
  }//method
  function hasCharset(){ return !empty($this->charset); }//method
  function getCharset(){ return $this->charset; }//method
  /**#@-*/

  /**
   *  output all the attributes in a nicely formatted string
   *     
   *  @return string
   */       
  function outAttr(){
    
    // canary...
    if(empty($this->attr_map)){ return ''; }//if
  
    $ret_str = ' ';
    foreach($this->attr_map as $attr_name => $attr_val){
      $ret_str .= sprintf('%s="%s" ',$attr_name,$attr_val);
    }//foreach
    return $ret_str;
  }//method

  /**
   *  return a safe value for $val that is suitable for display in stuff like the value attribute 
   *  
   *  @param  string  $val  the value to be "cleansed"
   *  @return string      
   */
  protected function getSafe($val){ return htmlspecialchars($val,ENT_COMPAT,$this->charset,false); }//method 

}//class     
