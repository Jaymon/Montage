<?php
/**
 *  an info event
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 8-25-11
 *  @package montage
 *  @subpackage event
 ******************************************************************************/
namespace Montage\Event;

use Montage\Field\Field;

class InfoEvent extends Event {

  /**
   *  holds the info message
   *
   *  @var  string   
   */
  protected $msg = '';

  /**
   *  create a new Event
   *  
   *  @param  string  $msg  the info message
   *  @param  array $options  any other values you want to pass to the event callback   
   */
  public function __construct($msg,array $options = array()){
  
    $this->msg = $msg;
    parent::__construct('framework.info',$options,true);
  
  }//method
  
  public function getMsg(){ return $this->msg; }//method
  
}//class
