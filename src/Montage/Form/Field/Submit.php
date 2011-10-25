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

use Montage\Form\Field\Input;

class Submit extends Input {

  public function __construct($name = '',$val = null){
  
    parent::__construct($name,$val);
    $this->setType(self::TYPE_SUBMIT);
  
  }//method

}//class     
