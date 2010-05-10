<?php

/**
 *  the main form class  
 *  
 *  @abstract 
 *  @version 0.2
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
    $this->setId($this->form_name);
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
   *  method since any key of $field_map that can't find a corresponding defined field
   *  will be ignored
   *  
   *  the reason this function exists is to make it easy to map submitted values
   *  back into a form instance           
   *  
   *  @param  array $field_map  name/val pairs      
   */
  function set($field_map){
    
    foreach($field_map as $name => $val){
    
      try{
      
        if(is_array($val)){
        
          foreach($val as $index => $index_val){
            $this->setField(
              sprintf('%s[%s]',$name,$index),
              $index_val
            );
          }//foreach
        
        }else{
        
          $this->setField($name,$val);
        
        }//if/else
        
      }catch(InvalidArgumentException $e){
        // just ignore non-existent names
      }//try/catch
    
    }//foreach
  
  }//method
  
  /**
   *  add a field to this form
   *  
   *  a field is an html element that the user can interact with (eg, <input>, <textarea>)
   *  
   *  @param  mixed $args,... different param cominations:
   *                            1 - montage_form_field - a field instance
   *                            2 - string, mixed - $name,$value where value is either the string value, or a
   *                                                field instance           
   */
  function setField(){
  
    $args = func_get_args();
    
    if(!empty($args)){
    
      if($args[0] instanceof montage_form_field){
      
        $field = $args[0];
      
        // update the field's name to become an array for this form, this keeps all the form vals namespaced...
        $name_info_map = $this->getNameInfo($field->getName());
        $field->setName($name_info_map['form_name']);
        
        // if the field is a file, update the encoding...
        if($field instanceof input_field){
          if($field->isType(input_field::TYPE_FILE)){
            $this->setEncoding(self::ENCODING_FILE);
          }//if
        }//if
        
        if($name_info_map['is_array_index']){
        
          if(!isset($this->field_map[$name_info_map['namespace']])){
            $this->field_map[$name_info_map['namespace']] = array();
          }//if
        
          if($name_info_map['index'] === null){
          
            $this->field_map[$name_info_map['namespace']][] = $field;
          
          }else{
          
            $this->field_map[$name_info_map['namespace']][$name_info_map['index']] = $field;
          
          }//if/else
        
        }else{
        
          $this->field_map[$name_info_map['namespace']] = $field;
        
        }//if/else
        
      }else{
      
        if(isset($args[1])){
        
          if($args[1] instanceof montage_form_field){
          
            $args[1]->setName($args[0]);
            $this->setField($args[1]);
          
          }else{
          
            $name_info_map = $this->getNameInfo($args[0]);
            
            if(is_array($this->field_map[$name_info_map['namespace']])){
            
              // canary...
              if(empty($name_info_map['is_array_index'])){
                throw new UnexpectedValueException(
                  sprintf(
                    'you are trying to turn an array field %s into a non array field',
                    $name_info_map['namespace']
                  )
                );
              }//if
              
              $field = end($this->field_map[$name_info_map['namespace']]);
              $field = clone $field;
              $field->setRandomId();
              $field->setLabel('');
              $field->setDesc('');
              $field->setName($name_info_map['name']);
              $field->setVal($args[1]);
              $this->setField($field);
            
            }else{
            
              if(isset($this->field_map[$args[0]])){
            
                $this->field_map[$args[0]]->setVal($args[1]);
              
              }else{
              
                throw new InvalidArgumentException(
                  sprintf(
                    'you tried to update $name %s with a new $val %s, but %s isn\'t a defined form element',
                    $args[0],
                    $args[1],
                    $args[0]
                  )
                );
              
              }//if/else

            }//if/else
          
          }//if/else
        
        }else{
        
          throw new DomainException(
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
    
    foreach($this as $field_name => $field){
    
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
    
    foreach($this as $field){
    
      $format_str = '';
      $format_vals = array();
      
      if($field->hasLabel()){
        $format_str .= '%s:<br>';
        $format_vals[] = $field->outLabel();
      }//if
      if($field->hasError()){
        $format_str .= '%s<br>';
        $format_vals[] = $field->outError();
      }//if
      
      $format_str .= '%s<br>';
      $format_vals[] = $field->out();
      
      if($field->hasDesc()){
        $format_str .= '%s<br>';
        $format_vals[] = $field->outDesc();
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
  
    foreach($this as $field){
    
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
  function getIterator(){
    $ret_list = array();
    foreach($this->field_map as $name => $value){
      if(is_array($value)){
        $ret_list = array_merge($ret_list,$value);
      }else{
        $ret_list[$name] = $value;
      }//if/else
    }//foreach
    
    return new ArrayIterator($ret_list);
  }//spl method
  
  /**
   *  Required definition for Countable, allows count($this) to work
   *  @link http://www.php.net/manual/en/class.countable.php
   */
  function count(){ return count($this->field_map); }//method

  /**
   *  gets the form specific name of the given $name, also normalizes $name
   *  
   *  basically, this converts a normal name like "foo" to form_name[foo], it is also
   *  handy because it would return namespace "foo" if you passed in "foo[]" so you could get
   *  the key that it would be in the array   
   *  
   *  @param  string  $name
   *  @return array array($name,$form_name,$is_array_index). $is_array_index will be set to true if
   *                [] was found in $name   
   */
  protected function getNameInfo($name){
    
    $ret_map = array();
    $ret_map['name'] = $ret_map['namespace'] = $name;
    $ret_map['is_array_index'] = false;
    $ret_map['index'] = '';
    
    $postfix = '';
    $index = mb_strpos($name,'[');
    if($index !== false){

      // $name is an array itself so we need to compensate to namespace it with the form name also...
      // form_name[$name][] works, form_name[$name[]] does not.
          
      $postfix = mb_substr($name,$index);
      $ret_map['namespace'] = mb_substr($name,0,$index);
      $ret_map['index'] = trim($postfix,'[]');
      if($ret_map['index'] === ''){ $ret_map['index'] = null; }//if
      $ret_map['is_array_index'] = true;
      
    }//if

    $ret_map['form_name'] = sprintf('%s[%s]%s',$this->form_name,$ret_map['namespace'],$postfix);

    return $ret_map;
    
  }//method

}//class     
