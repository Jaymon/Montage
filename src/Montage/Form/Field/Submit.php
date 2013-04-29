<?php
/**
 *  the object that represents an <input type="submit"> html element
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 10-24-11
 *  @package montage
 *  @subpackage form
 ******************************************************************************/
namespace Montage\Form\Field;

class Submit extends Input {

  public function __construct($name = '', $val = null){
  
    parent::__construct($name, $val);
    $this->setType(self::TYPE_SUBMIT);
  
  }//method
  
  /**
   *  do submit buttons really need a label? I think not!
   *  
   *  @see  parent::renderLabel()      
   */
  public function renderLabel($label = '', array $attr_map = array()){ return ''; }//method

  /**
   *  
   *  @see  parent::setType()
   */
  public function setType($val){

    parent::setType($val);
    if(!$this->hasName()){ $this->setName(self::TYPE_SUBMIT); }//if
    if(!$this->hasVal()){ $this->setVal('Submit'); }//if
    
  }//method

}//class
