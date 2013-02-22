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
   *  true if the param has been touched
   *  
   *  @since  12-12-11
   *  @see  changedParam()   
   *  @var  boolean 
   */
  protected $param_changed = false;

  /**
   *  create a new Event
   *  
   *  @param  string  $msg  the info message
   *  @param  mixed $param  the argument being filtered
   *  @param  array $field_map  any other values you want to pass to the event callback   
   */
  public function __construct($name,$param,array $field_map = array()){
  
    $this->setParam($param);
    $this->param_changed = false;
    
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
  public function setParam($param){
  
    $this->param = $param;
    $this->param_changed = true;
    
  }//method
  
  /**
   *  return true if {@link setParam()} has been called
   *  
   *  this is handy if you want to know if the param has been updated
   *  
   *  @since  12-12-11
   *  @return boolean               
   */
  public function changedParam(){ return $this->param_changed; }//method
  
}//class
