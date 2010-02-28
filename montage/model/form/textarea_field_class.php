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
  
  function out(){
  
    // make sure the value is safe and not considered an attribute...
    $val = $this->getSafe($this->getVal());
    $this->clearVal();
  
    return sprintf('<textarea%s>%s</textarea>',$this->outAttr(),$val);
    
  }//method

}//class     
