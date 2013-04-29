<?php
/**
 *  the object that represents an <input type="file"> html element
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 2013-4-14
 *  @package montage
 *  @subpackage form
 ******************************************************************************/
namespace Montage\Form\Field;

class File extends Input {

  public function __construct($name = '', $val = null){
  
    parent::__construct($name, $val);
    $this->setType(self::TYPE_FILE);
  
  }//method
  
}//class
