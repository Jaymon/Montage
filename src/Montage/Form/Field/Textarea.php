<?php

/**
 *  the object that represents a <textarea> html element
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-5-10
 *  @package montage
 *  @subpackage form
 ******************************************************************************/
namespace Montage\Form\Field;

use Montage\Form\Field\Field;

class Textarea extends Field {
  
  public function render(array $attr_map = array()){
  
    $ret_str = '';
    $orig_val = $this->getVal();
    $val = '';
  
    // make sure the value is safe and not considered an attribute...
    if(isset($attr_map['value'])){
      $val = $this->getSafe($attr_map['value']);
      unset($attr_map['value']);
    }else{
      $val = $this->getSafe($this->getVal());
    }//if/else
    
    $this->killVal();
    
    $ret_str = '';
  
    if($attr_str = $this->renderAttr($attr_map)){
    
      $ret_str = sprintf('<textarea %s>',$attr_str);
    
    }else{
    
      $ret_str = '<textarea>';
    
    }//if/else
  
    $ret_str .= $val.'</textarea>';
    
    $this->setVal($orig_val);
    
    return $ret_str;
    
  }//method

}//class     
