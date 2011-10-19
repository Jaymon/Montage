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
  
    // make sure the value is safe and not considered an attribute...
    if(isset($attr_map['value'])){
      $val = $this->getSafe($attr_map['value']);
      unset($attr_map['value']);
    }else{
      $val = $this->getSafe($this->getVal());
    }//if/else
    
    $this->clearVal();
  
    $ret_str = sprintf('<textarea%s>%s</textarea>',$this->renderAttr($attr_map),$val);
    
    $this->setVal($orig_val);
    return $ret_str;
    
  }//method

}//class     
