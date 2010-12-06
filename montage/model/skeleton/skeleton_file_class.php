<?php

/**
 *  used by the create.php script to build a skeleton montage app
 *  
 *  @abstract 
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 8-24-10
 *  @package montage
 *  @subpackage skeleton  
 ******************************************************************************/
abstract class skeleton_file extends montage_base {
  
  /**
   *  any child classes have to set this file in their {@link start()} methods, it should
   *  point to the template file that {@link get()} will use for the file   
   *  
   *  @var  string
   */
  protected $template_file = '';
  
  /**
   *  @param  string  $name a name, used differently depending on the child file  
   *  @param  array $options_map  key/val pairs
   */
  final function __construct($name,$options_map){
    
    $options_map['name'] = $name;
    $options_map['since'] = date('Y-m-d');
    
    if(empty($options_map['docblock_desc'])){
      $options_map['docblock_desc'] = $this->getDocblockDesc();
    }//if
    
    $this->setField('options_map',$options_map);
    
    $this->start();
    
    if(empty($this->template_file)){
      throw new UnexpectedValueException('invalid $template_file, set a real template_file in your start()');
    }//if
    
  }//method

  /**
   *  returns the internal contents of the file
   *  
   *  @return string
   */
  public function get(){
  
    // get the template...
    $response = montage::getResponse();
    $response->setTemplate($this->template_file);
    $template = $response->getTemplateInstance();
    ///$template->setTemplate($this->template_file);
    $template->setFields($this->getField('options_map',array()));
    return $template->out(montage_template::OPTION_OUT_STR);
  
  }//method
  
  /**
   *  returns a docblock description the file might use
   *  
   *  @abstract
   *  @return string
   */
  abstract protected function getDocblockDesc();
  
}//class     
