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
use Montage\Form\Field\Submit;

use ReflectionObject,ReflectionProperty;
use Montage\Form\Annotation\FormAnnotation;

use ArrayIterator;
use ArrayAccess,IteratorAggregate;

use Montage\Field\GetFieldable;

abstract class Form extends Common implements ArrayAccess,IteratorAggregate,GetFieldable {

  /**
   *  @var  Submit
   */
  public $submit = null;

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
   *  the form fields this form contains
   *  @var  array
   */
  protected $field_map = array();
  
  /**
   *  create a Form
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
   *  and set their names in this method. And set any other values that should be default
   *  on the form   
   *  
   *  @since  6-28-11
   */
  protected function populate(){
  
    $annotation = new FormAnnotation($this);
    $annotation->populate();
  
    $this->field_map = $annotation->getFields();
  
  }//method

  /**
   *  returns true if form contains valid values
   *
   *  @since  8-1-11   
   *  @return boolean   
   */
  public function isValid(){ return true; }//method

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
   *  
   *  use ENCODING_* attributes      
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
    
      if($this->hasField($name)){
    
        $field = $this->getField($name);
        $field->setVal($val);
      
      }//if
      
    }//foreach
  
  }//method
  
  /**
   *  return the value of $key, return $default_val if key doesn't exist
   *
   *  @param  string  $key
   *  @param  mixed $default_val  here for compatibality with Fieldable interface, not used
   *  @return Field
   */
  public function getField($name,$default_val = null){
  
    $ret_field = null;
    
    if($this->hasField($name)){
    
      $ret_field = $this->$name;
      
    }else{
    
      throw new \InvalidArgumentException(sprintf('%s field does not exist',$name));
      
    }//if/else
    
    return $ret_field;
    
  }//method
  
  /**
   *  return the value of getField, but wrap it in an escape object
   *  
   *  this is useful for making sure user submitted input is safe
   *
   *  @see  getField()      
   */
  public function escField($key,$default_val = null){
    throw new \BadMethodCallException('This method is not used in this class');
  }//method
  
  /**
   *  check if $key exists and is non-empty
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  public function hasField($name){ return isset($this->field_map[$name]); }//method
  
  /**
   *  check if $key exists
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  public function existsField($name){ return $this->hasField($name); }//method
  
  /**
   *  return true if there are fields
   *  
   *  @since  6-30-11   
   *  @return boolean
   */
  public function hasFields(){ return !empty($this->field_map); }//method
  
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
      $ret_bool = ($field instanceof $val);
    }//if
    
    return $ret_bool;
  
  }//method
  
  /**
   * get all non submit field values, similar to getFields()
   *
   * @since 2013-3-7
   * @return  array a map of name => value
   */
  public function getVals(){

    $ret_map = array();

    foreach($this->getFields() as $name => $field){
    
      $ret_map[$name] = $field->getVal();

    }//foreach

    return $ret_map;

  }//method

  /**
   * get all field values, similar to getAllFields()
   *
   * @since 2013-3-7
   * @return  array a map of name => value
   */
  public function getAllVals(){

    $ret_map = array();

    foreach($this->getAllFields() as $name => $field){
    
      $ret_map[$name] = $field->getVal();

    }//foreach

    return $ret_map;

  }//method
  /**
   *  return the instance's field_map, minus the submit fields
   *
   *  this is handy because most of the time you want the fields without the submit
   *  fields
   *  
   *  @return array
   */
  public function getFields(){
    $ret_map = array();

    foreach($this as $name => $field){
      
      // canary, skip submit buttons...
      if($field instanceof Submit){ continue; }//if
    
      $ret_map[$name] = $field;

    }//foreach

    return $ret_map;

  }//method

  /**
   * this will return all fields, including submit fields
   *
   * @return  array
   */
  public function getAllFields(){ return $this->field_map; }//method
  
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
  
  public function render(array $attr_map = array()){
    
    $ret_str = array();
    $ret_str[] = $this->renderStart($attr_map);
    
    if($errmsg = $this->renderError()){
      $ret_str[] = $errmsg;
    }//if
    
    foreach($this as $field){
    
      $format_str = '';
      $format_vals = array();
      
      if($field->hasLabel() && ($label = $field->renderLabel())){
        $format_str .= '%s<br>';
        $format_vals[] = $label;
      }//if
      
      if($errmsg = $field->renderError()){
        $format_str .= '%s<br>';
        $format_vals[] = $errmsg;
      }//if
      
      $format_str .= '%s<br>';
      $format_vals[] = $field->render();
      
      if($field->hasDesc()){
        $format_str .= '%s<br>';
        $format_vals[] = $field->renderDesc();
      }//if
      
      $ret_str[] = vsprintf($format_str,$format_vals);
    
    }//foreach
    
    $ret_str[] = $this->renderStop();
    return join(PHP_EOL,$ret_str);
    
  }//method
  
  public function renderStart(array $attr_map = array()){
  
    $ret_str = '';
  
    $this->setMethod($this->getMethod());
    $this->setEncoding($this->getEncoding());
  
    if($attr_str = $this->renderAttr($attr_map)){
    
      $ret_str = sprintf('<form %s>',$attr_str);
    
    }else{
    
      $ret_str = '<form>';
    
    }//if/else
  
    return $ret_str;
    
  }//method
  public function renderStop(){ return '</form>'; }//method
  
  /**
   *  finds and outputs all the hidden input fields
   *  
   *  this is a timesaver method for custom fields to easily get all the hidden
   *  fields and output them
   *  
   *  @since  2-6-10   
   *  @return string
   */
  public function renderHidden(){
  
    $ret_str = '';
  
    foreach($this as $field){
    
      if($field instanceof Input){
        if($field->isType(Input::TYPE_HIDDEN)){
          $ret_str .= $field->render();
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
    throw new \BadMethodCallException('Array set is not supported on this object');
  }//method
  /**
   *  Return a value given it's key e.g. echo $A['title'];
   */
  public function offsetGet($name){ return $this->getField($name); }//method
  /**
   *  Unset a value by it's key e.g. unset($A['title']);
   */
  public function offsetUnset($name){
    throw new \BadMethodCallException('Array unset is not supported on this object');
  }//method
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
  public function getIterator(){ return new ArrayIterator($this->field_map); }//spl method

}//class     
