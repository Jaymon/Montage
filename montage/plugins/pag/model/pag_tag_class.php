<?php

/**
 *  the response object for parse_tag 
 *
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 8-8-10
 *  @package parse
 ******************************************************************************/

class pag_tag {

  protected $field_map = array();

  public final function __construct($tag = ''){
  
    $this->setTag($tag);
    
    $this->field_map['attr'] = array();
  
  }//method

  protected function setTag($val){
    $this->field_map['tag'] = $val;
  }//method

  public function setBody($val){
    $this->field_map['body'] = $val;
  }//method
  
  public function getBody(){
    return isset($this->field_map['body']) ? $this->field_map['body'] : '';
  }//method

  public function setStart($val){
    $this->field_map['start'] = $val;
  }//method

  public function getStart(){
    return isset($this->field_map['start']) ? $this->field_map['start'] : '';
  }//method
  
  public function setStop($val){
    $this->field_map['stop'] = $val;
  }//method

  public function getStop(){
    return isset($this->field_map['stop']) ? $this->field_map['stop'] : '';
  }//method

  public function setOffset($val){
    $this->field_map['offset'] = (int)$val;
  }//method
  
  public function getOffset(){
    return isset($this->field_map['offset']) ? $this->field_map['offset'] : 0;
  }//method
  
  public function getBodyOffset(){
    $ret_int = $this->getOffset();
    $ret_int += mb_strlen($this->getStart());
    return $ret_int;
  }//method
  
  public function setBodyLength($val){
    $this->field_map['body_length'] = (int)$val;
  }//method
  
  public function setFull($val){
    $this->field_map['full'] = $val;
  }//method

  public function setAttr($val){
    $this->field_map['attr'] = (array)$val;
  }//method
  
  public function getAttr($name,$default_val = ''){
    return isset($this->field_map['attr'][$name]) ? $this->field_map['attr'][$name] : $default_val;
  }//method

  public function hasAttr($name){
    return !empty($this->field_map['attr'][$name]);
  }//method

}//class
