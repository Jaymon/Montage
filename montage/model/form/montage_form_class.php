<?php

/**
 *  the main form class
 *  
 *  if you override __construct(), remember to call parent::__cosntruct() somewhere
 *  in your new __construct() method      
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 1-1-10
 *  @package montage
 *  @subpackage form 
 ******************************************************************************/
abstract class montage_form extends montage_form_base implements ArrayAccess,Countable,IteratorAggregate {

  const METHOD_POST = 'post';
  const METHOD_GET = 'get';

  /**#@+
   *  encoding for the different types
   *  
   *  @link http://www.w3.org/TR/html401/interact/forms.html#h-17.3
   *  
   *  sets both encoding and enctype attributes
   */
  const ENCODING_FILE = 'multipart/form-data';
  const ENCODING_POST = 'application/x-www-form-urlencoded';
  /**#@-*/

  /**
   *  the name of this form
   *  @var  string
   */
  protected $form_name = '';
  
  /**
   *  the form fields this form contains
   *  @var  array
   */
  protected $field_map = array();
  
  /**
   *  this  cannot be overriden to make things consistent, all class instantiation
   *  (eg, adding fields to the form) should go into {@link start()}
   *  
   *  @param  array $field_map  if you want to set values from an array into this instance, you can
   *                            pass them (key/val) here, otherwise, you can call {@link set()} at any time             
   */
  final function __construct($field_map = array()){
  
    $this->form_name = get_class($this);
    $this->start();
    if(!empty($field_map)){
      $this->set($field_map);
    }//if
  
  }//method
  
  /**
   *  get the form's name, this is basically the namespace the form is using
   *  
   *  @return string
   */
  final function getName(){ return $this->form_name; }//method
  
  /**
   *  this is the class that should add all the fields to the form, and do any form
   *  instantiation, called from the {@link __construct()} method
   */
  abstract protected function start();

  /**#@+
   *  access methods for the action url that this form will post to
   */
  function setUrl($val){ return $this->setAttr('action',$val); }//method
  function hasUrl(){ return $this->hasAttr('action'); }//method
  function getUrl(){ return $this->getAttr('action'); }//method
  /**#@-*/

  /**#@+
   *  access methods for the action method this form uses
   *  
   *  use the METHOD_* constants this class provides, defaults to METHOD_POST       
   */
  function setMethod($val){ return $this->setAttr('method',$val); }//method
  function hasMethod(){ return $this->hasAttr('method'); }//method
  function getMethod(){ return $this->getAttr('method',self::METHOD_POST); }//method
  /**#@-*/
  
  /**#@+
   *  access methods for the action url that this form will post to
   */
  protected function setEncoding($val){
    $this->setAttr('encoding',$val);
    $this->setAttr('enctype',$val);
  }//method
  protected function hasEncoding(){ return $this->hasAttr('encoding'); }//method
  protected function getEncoding(){ return $this->getAttr('encoding',self::ENCODING_POST); }//method
  /**#@-*/
  
  /**
   *  map an associative array of values to the fields defined in the form
   *  
   *  the supported fields of this form should already be defined before calling this
   *  method since and key of $field_map that can't find a corresponding defined field
   *  will be ignored
   *  
   *  the reason this function exists is to make it easy to map submitted values
   *  back into a form instance           
   *  
   *  @param  array $field_map  name/val pairs      
   */
  function set($field_map){
    
    foreach($field_map as $name => $val){
    
      list($name,$form_name) = $this->getFieldNames($name);
      if(isset($this->field_map[$name])){
        $this->field_map[$name]->setVal($val);
      }//if
    
    }//foreach
  
  }//method
  
  /**
   *  add a field to this form
   *  
   *  a field is an html element that the user can interact with (eg, <input>, <textarea>)
   *  
   *  @param  mixed $args,... different param cominations:
   *                            1 - montage_form_field - a field instance
   *                            2 - string, mixed         
   */
  function setField(){
  
    $args = func_get_args();
    
    if(!empty($args)){
    
      if($args[0] instanceof montage_form_field){
      
        $field = $args[0];
      
        // update the field's name to become an array for this form, this keeps all the form vals namespaced...
        list($name,$form_name) = $this->getFieldNames($field->getName());
        $field->setName($form_name);
        
        // if the field is a file, update the encoding...
        if($field instanceof input_field){
          if($field->isType(input_field::TYPE_FILE)){
            $this->setEncoding(self::ENCODING_FILE);
          }//if
        }//if
        
        $this->field_map[$name] = $field;
        
      }else{
      
        if(isset($args[1])){
        
          if($args[1] instanceof montage_form_field){
          
            $this->setField($args[1]);
          
          }else{
          
            if(isset($this->field_map[$args[0]])){
            
              $this->field_map[$args[0]]->setVal($args[1]);
            
            }else{
            
              throw new montage_form_exception(
                sprintf(
                  'you tried to update $name %s with a new $val %s, but %s isn\'t a defined form element',
                  $args[0],
                  $args[1],
                  $args[0]
                )
              );
            
            }//if/else
          
          }//if/else
        
        }else{
        
          throw new montage_form_exception(
            sprintf(
              'you need ($name,$val), you passed in $name, but no $val: (%s)',
              join(',',$args)
            )
          );
        
        }//if/else
      
      }//if/else
      
    }//if
  
  }//method
  
  function getField($name){ return isset($this->field_map[$name]) ? $this->field_map[$name] : null; }//method
  function hasField($name){ return isset($this->field_map[$name]); }//method
  function killField($name){
    if($this->hasField($name)){ unset($this->field_map[$name]); }//if
  }//method
  
  /**
   *  get an array with all the errors mapped to their names, this includes the global
   *  form errors
   *  
   *  @return array key/val array of found errors
   */
  function getErrors(){
  
    $ret_map = array();
    if($this->hasError()){
      $ret_map[$this->form_name] = $this->getError();
    }//if
    
    foreach($this->field_map as $field_name => $field){
    
      if($field->hasError()){
        $ret_map[$field_name] = $field->getError();
      }//if
    
    }//foreach
  
    return $ret_map;
    
  }//method
  
  function out(){
    
    $ret_str = $this->outStart();
    
    if($this->hasError()){
      $ret_str .= $this->outError();
    }//if
    
    foreach($this->field_map as $field){
    
      $format_str = '';
      $format_vals = array();
      
      if($field->hasLabel()){
        $format_str .= '%s:<br />';
        $format_vals[] = $field->getLabel();
      }//if
      if($field->hasError()){
        $format_str .= '%s<br />';
        $format_vals[] = $field->getError();
      }//if
      
      $format_str .= '%s<br />';
      $format_vals[] = $field->out();
      
      if($field->hasDesc()){
        $format_str .= '%s<br />';
        $format_vals[] = $field->getDesc();
      }//if
      
      $ret_str .= vsprintf($format_str,$format_vals);
    
    }//foreach
    
    $ret_str .= $this->outStop();
    return $ret_str;
    
  }//method
  
  function outStart(){
  
    $this->setMethod($this->getMethod());
    $this->setEncoding($this->getEncoding());
  
    return sprintf('<form%s>',$this->outAttr());
    
  }//method
  function outStop(){ return '</form>'; }//method
  
  /**
   *  finds and outputs all the hidden input fields
   *  
   *  this is a timesaver method for custom fields to easily get all the hidden
   *  fields and output them
   *  
   *  @since  2-6-10   
   *  @return string
   */
  function outHidden(){
  
    $ret_str = '';
  
    foreach($this->field_map as $field){
    
      if($field instanceof input_field){
        if($field->isType(input_field::TYPE_HIDDEN)){
          $ret_str .= $field->out();
        }//if
      }//if
    
    }//foreach
  
    return $ret_str;
  
  }//method

  /**#@+
   *  Required definition of interface ArrayAccess
   *  @link http://www.php.net/manual/en/class.arrayaccess.php   
   */
  /**
   *  Set a value given it's key e.g. $A['title'] = 'foo';
   */
  function offsetSet($key,$val){
    
    // if they are trying to do a $obj[] = $val let's append the $val
    // via: http://www.php.net/manual/en/class.arrayobject.php#93100
    
    $this->setField($key,$val);
    
  }//method
  /**
   *  Return a value given it's key e.g. echo $A['title'];
   */
  function offsetGet($key){ return $this->getField($key); }//method
  /**
   *  Unset a value by it's key e.g. unset($A['title']);
   */
  function offsetUnset($key){ $this->killField($key); }//method
  /**
   *  Check value exists, given it's key e.g. isset($A['title'])
   */
  function offsetExists($key){ return $this->hasField($key); }//method
  /**#@-*/

  /**
   *  reuired method definition for IteratorAggregate
   *
   *  @return ArrayIterator allows this class to be iteratable by going throught he main array
   */
  function getIterator(){ return new ArrayIterator($this->field_map); }//spl method
  
  /**
   *  Required definition for Countable, allows count($this) to work
   *  @link http://www.php.net/manual/en/class.countable.php
   */
  function count(){ return count($this->field_map); }//method

  /**
   *  gets the form specific name of the given $name, also normalizes $name
   *  
   *  basically, this converts a normal name like "foo" to form_name[foo]
   *  
   *  @param  string  $name
   *  @return array array($name,$form_name)
   */
  protected function getFieldNames($name){
    
    $postfix = '';
    $index = mb_strpos($name,'[');
    if($index !== false){
    
      $postfix = mb_substr($name,$index);
      $name = mb_substr($name,0,$index);
      
    }//if

    return array($name,sprintf('%s[%s]%s',$this->form_name,$name,$postfix));
    
  }//method

}//class     
