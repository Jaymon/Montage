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
   *  @param  string  $event_name the event name      
   *  @param  array $field_map  the values you want this event to contain when it is handled by the callbacks
   *  @param  boolean $persist  set to true to have this event stick around until something is listening to
   *                            $event_name, if something is already listening, then this is ignored
   */
  public function __construct($msg,array $options = array()){
  
    $this->msg = $msg;
    parent::__construct('framework.info',$options,true);
  
  }//method
  
  public function getMsg(){ return $this->msg; }//method
  
}//class
