<?php
/**
 *  a filter event
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 10-2-11
 *  @package montage
 *  @subpackage event
 ******************************************************************************/
namespace Montage\Event;

class FilterEvent extends Event {

  /**
   *  holds the field that is going to be filtered
   *
   *  @var  mixed
   */
  protected $param = null;

  /**
   *  create a new Event
   *  
   *  @param  string  $msg  the info message
   *  @param  array $options  any other values you want to pass to the event callback   
   */
  public function __construct($name,$param,array $field_map = array()){
  
    $this->setParam($param);
    parent::__construct($name,$field_map,false);
  
  }//method
  
  /**
   *  get the param that is being 'filtered'
   *
   *  @return mixed   
   */
  public function getParam(){ return $this->param; }//method
  
  /**
   *  set the filtered param
   *
   *  @param  mixed $param
   */
  public function setParam($param){ $this->param = $param; }//method
  
}//class
