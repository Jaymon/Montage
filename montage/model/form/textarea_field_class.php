<?php

/**
 *  the object that represents an <textarea> html element
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-5-10
 *  @package montage
 *  @subpackage form
 ******************************************************************************/
class textarea_field extends montage_form_field {
  
  function out($attr_map = array()){
  
    $ret_str = '';
    $orig_val = $this->getVal();
  
    // make sure the value is safe and not considered an attribute...
    if(isset($attr_map['value'])){
      $val = $this->getSafe($attr_map['value']);
      unset($attr_map['value']);
    }else{
      $val = $this->getSafe($this->getVal());
    }//if/else
    
    $this->clearVal();
  
    $ret_str = sprintf('<textarea%s>%s</textarea>',$this->outAttr($attr_map),$val);
    
    $this->setVal($orig_val);
    return $ret_str;
    
  }//method

}//class     
