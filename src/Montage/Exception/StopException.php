<?php
/**
 *  thrown when you just want to halt processing but it isn't an error state
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-15-11
 *  @package montage
 *  @subpackage exception
 ******************************************************************************/      
namespace Montage\Exception;

class StopException extends FrameworkException {

  /**
   *  the ret value
   *  
   *  the return value is the same return value that a controller would return 
   *  
   *  I don't really like the name controller_response but I use that every where
   *  in the Framework class so I might as well keep the name consistent              
   *
   *  @since  11-7-11   
   *  @var  mixed   
   */
  protected $controller_response = null;

  /**
   *  create instance
   *  
   *  @since  11-7-11   
   *  @param  mixed $controller_response  same value a Controller would return
   *  @param  string  $msg  the message
   *  @param  interger  $code      
   */
  public function __construct($controller_response = null,$msg = '',$code = 0){
  
    parent::__construct($msg,$code);
    
    $this->controller_response = $controller_response;
  
  }//method
  
  /**
   *  return the ret value
   *  
   *  the return value is the same return value that a controller would return      
   *
   *  @since  11-7-11   
   *  @return mixed   
   */
  public function getControllerResponse(){ return $this->controller_response; }//methdod

}//class
