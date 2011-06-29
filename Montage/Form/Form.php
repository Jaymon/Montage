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
namespace Montage\Form;

use Montage\Form\Common;
use Montage\Form\Field\Field;
use Montage\Form\Field\Input;
use ReflectionObject;
use ArrayIterator;
use ArrayAccess,IteratorAggregate,Montage\Field\Fieldable;

abstract class Form extends Common implements ArrayAccess,IteratorAggregate,Fieldable {

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
  ///protected $form_name = '';
  
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
  public function __construct(array $field_map = array()){

    $this->setId($this->getName());
    
    $this->populate();
    
    if(!empty($field_map)){
      $this->set($field_map);
    }//if
  
  }//method

  /**
   *  populate the fields of this form
   *  
   *  this is like a schema building method, basically, you would create Field instances
   *  and set their names in this method. And set any other valuesthat should be default
   *  on the form   
   *  
   *  @since  6-28-11
   */
  abstract protected function populate();

  /**
   *  get the form's name, this is basically the namespace the form is using
   *  
   *  @return string
   */
  public function getName(){
  
    if(!$this->hasName()){
      $rthis = new ReflectionObject($this);
      $this->setName($rthis->getShortName());
    }//if
  
    return parent::getName();
    
  }//method
  
  /**#@+
   *  access methods for the action url that this form will post to
   */
  public function setUrl($val){ return $this->setAttr('action',$val); }//method
  public function hasUrl(){ return $this->hasAttr('action'); }//method
  public function getUrl(){ return $this->getAttr('action'); }//method
  /**#@-*/

  /**#@+
   *  access methods for the action method this form uses
   *  
   *  use the METHOD_* constants this class provides, defaults to METHOD_POST       
   */
  public function setMethod($val){ return $this->setAttr('method',$val); }//method
  public function hasMethod(){ return $this->hasAttr('method'); }//method
  public function getMethod(){ return $this->getAttr('method',self::METHOD_POST); }//method
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
   *  the reason $field_map isn't specified as an array is to allow children to override
   *  this method and pass in things like an Orm instance or something               
   *  
   *  @param  array $field_map  name/val pairs
   */
  public function set($field_map){
    
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
        
      }catch(\InvalidArgumentException $e){
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
  public function setField($name,$val = null){
  
    $args = func_get_args();
    
    if(!empty($args)){
    
      if($args[0] instanceof Field){
      
        $field = $args[0];
      
        // update the field's name to become an array for this form, this keeps all the form vals namespaced...
        $name_info_map = $this->getNameInfo($field->getName());
        $field->setName($name_info_map['form_name']);
        
        // if the field is a file, update the encoding...
        if($field instanceof Input){
          if($field->isType(Input::TYPE_FILE)){
            $this->setEncoding(self::ENCODING_FILE);
          }//if
        }//if
        
        if($name_info_map['is_array']){ // name has multiple values
        
          if(!isset($this->field_map[$name_info_map['namespace']])){
            $this->field_map[$name_info_map['namespace']] = array();
          }//if
        
          if($name_info_map['index'] === null){ // name[]
          
            $this->field_map[$name_info_map['namespace']][] = $field;
          
          }else{ // name[index]
          
            $this->field_map[$name_info_map['namespace']][$name_info_map['index']] = $field;
          
          }//if/else
        
        }else{ // name
        
          $this->field_map[$name_info_map['namespace']] = $field;
        
        }//if/else
        
      }else{
      
        if(isset($args[1])){
        
          if($args[1] instanceof Field){
          
            $args[1]->setName($args[0]);
            $this->setField($args[1]);
          
          }else{
          
            $name_info_map = $this->getNameInfo($args[0]);
            
            if(is_array($this->field_map[$name_info_map['namespace']])){
            
              // canary...
              if(empty($name_info_map['is_array'])){
                throw new \UnexpectedValueException(
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
              
                throw new \InvalidArgumentException(
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
        
          throw new \InvalidArgumentException(
            sprintf(
              'you need ($name,$val), you passed in $name, but no $val: (%s)',
              join(',',$args)
            )
          );
        
        }//if/else
      
      }//if/else
      
    }//if
  
  }//method
  
  /**
   *  return the value of $key, return $default_val if key doesn't exist
   *
   *  @param  string  $key
   *  @param  mixed $default_val  here for compatibality with Fieldable interface, not used
   *  @return mixed
   */
  public function getField($name,$default_val = null){
  
    $ret_mixed = null;
    $name_map = $this->getNameInfo($name);
    $name = $name_map['namespace'];

    if(isset($this->field_map[$name])){
      
      $ret_mixed = $this->field_map[$name];
      $is_arr = is_array($ret_mixed);
      
      if($name_map['is_array']){
      
        if(!$is_arr){ $ret_mixed = array($ret_mixed); }//if
        
      }//if
      
    }else{
    
      $ret_mixed = $name_map['is_array'] ? array() : null;
      
    }//if/else
    
    return $ret_mixed;
    
  }//method
  
  /**
   *  check if $key exists and is non-empty
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  public function hasField($name){
  
    $ret_bool = false;
    $name_map = $this->getNameInfo($name);
    $name = $name_map['namespace'];
  
    if(isset($this->field_map[$name])){
    
      if($name_map['is_array']){
      
        $ret_bool = is_array($this->field_map[$name]);
      
      }else{
      
        $ret_bool = true;
      
      }//if/else
      
    }//if
  
    return $ret_bool;
  
  }//method
  
  /**
   *  check if $key exists
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  public function existsField($name){ return $this->hasField($name); }//method
  
  /**
   *  remove $key and its value from the map
   *  
   *  @param  string  $key
   *  @return object  the class instance for fluid interface
   */
  public function killField($name){
  
    $name_map = $this->getNameInfo($name);
    $name = $name_map['namespace'];
    
    if(isset($this->field_map[$name])){
    
      if($name_map['is_array'] && !empty($name_map['index'])){
      
        unset($this->field_map[$name][$name_map['index']]);
      
      }else{
      
        unset($this->field_map[$name]);
      
      }//if/else
    
    }//if
    
    return $this;
    
  }//method
  
  /**
   *  bump the field at $name by $count
   *  
   *  @since  5-26-10
   *      
   *  @param  string  $name  the name
   *  @param  integer $count  the value to increment $name
   *  @return integer the incremented value now stored at $name
   */
  public function bumpField($name,$count = 1){
  
    $ret_count = 0;
  
    if($field = $this->getField($name)){
    
      if(is_array($field)){
      
        foreach($field as $f){
        
          $field_val = $f->getVal();
          $ret_count += ((int)$field_val + (int)$count);
          $f->setVal($ret_count);
        
        }//foreach
      
      }else{
      
        $field_val = $field->getVal();
        $ret_count = (int)$field_val + (int)$count;
        $field->setVal($ret_count);
        
      }//if/else
      
    }//if
    
    return $ret_count;
  
  }//method
  
  /**
   *  check's if a field exists and is equal to $val
   *  
   *  @param  string  $name  the name
   *  @param  string  $val  the value to compare to the $name's set value
   *  @return boolean
   */
  public function isField($name,$val){
  
    $ret_bool = false;
  
    if($field = $this->getField($name)){
      $ret_bool = ($val === $field->getVal());
    }//if
    
    return $ret_bool;
  
  }//method
  
  /**
   *  add all the fields in $field_map to the instance field_map
   *  
   *  $field_map takes precedence, it will overwrite previously set values
   *      
   *  @param  array $field_map      
   *  @return object  the class instance for fluid interface
   */
  public function addFields(array $field_map){
  
    $this->field_map = array_merge($this->field_map,$field_map);
    return $this;
  
  }//method
  
  /**
   *  set all the fields in $field_map to the instance field_map
   *  
   *  @since  6-3-11   
   *  @param  array $field_map      
   *  @return object  the class instance for fluid interface
   */
  public function setFields(array $field_map){
    $this->form_map = $field_map;
    return $this;
  }//method
  
  /**
   *  return the instance's field_map
   *  
   *  @return array
   */
  public function getFields(){ return $this->field_map; }//method
  
  /**
   *  get an array with all the errors mapped to their names, this includes the global
   *  form errors
   *  
   *  @return array key/val array of found errors
   */
  public function getErrors(){
  
    $ret_map = array();
    if($this->hasError()){
      $ret_map[$this->getName()] = $this->getError();
    }//if
    
    foreach($this as $field_name => $field){
    
      if($field->hasError()){
        $ret_map[$field_name] = $field->getError();
      }//if
    
    }//foreach
  
    return $ret_map;
    
  }//method
  
  public function out(array $attr_map = array()){
    
    $ret_str = $this->outStart($attr_map);
    
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
  
  public function outStart(array $attr_map = array()){
  
    $this->setMethod($this->getMethod());
    $this->setEncoding($this->getEncoding());
  
    return sprintf('<form%s>',$this->outAttr($attr_map));
    
  }//method
  public function outStop(){ return '</form>'; }//method
  
  /**
   *  finds and outputs all the hidden input fields
   *  
   *  this is a timesaver method for custom fields to easily get all the hidden
   *  fields and output them
   *  
   *  @since  2-6-10   
   *  @return string
   */
  public function outHidden(){
  
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
  public function offsetSet($name,$val){
    
    // if they are trying to do a $obj[] = $val let's append the $val
    // via: http://www.php.net/manual/en/class.arrayobject.php#93100
    
    $this->setField($name,$val);
    
  }//method
  /**
   *  Return a value given it's key e.g. echo $A['title'];
   */
  public function offsetGet($name){ return $this->getField($name); }//method
  /**
   *  Unset a value by it's key e.g. unset($A['title']);
   */
  public function offsetUnset($name){ $this->killField($name); }//method
  /**
   *  Check value exists, given it's key e.g. isset($A['title'])
   */
  public function offsetExists($name){ return $this->hasField($name); }//method
  /**#@-*/

  /**
   *  reuired method definition for IteratorAggregate
   *
   *  @return ArrayIterator allows this class to be iteratable by going throught he main array
   */
  public function getIterator(){
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
   *  gets the form specific name of the given $name, also normalizes $name
   *  
   *  basically, this converts a normal name like "foo" to form_name[foo], it is also
   *  handy because it would return namespace "foo" if you passed in "foo[]" so you could get
   *  the key that it would be in the array   
   *  
   *  @param  string  $name
   *  @return array array($name,$form_name,$is_array). $is_array will be set to true if
   *                [] was found in $name   
   */
  protected function getNameInfo($name){
    
    $ret_map = array();
    $ret_map['name'] = $ret_map['namespace'] = $name;
    $ret_map['is_array'] = false;
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
      $ret_map['is_array'] = true;
      
    }//if

    $ret_map['form_name'] = sprintf('%s[%s]%s',$this->getName(),$ret_map['namespace'],$postfix);

    return $ret_map;
    
  }//method

}//class     
