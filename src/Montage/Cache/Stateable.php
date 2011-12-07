<?php
/**
 *  any class can implement this interface to be able to export its current state
 *  
 *  this class aims to do what __set_state() and var_export() do, but more consistently
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 12-07-11
 *  @package montage 
 ******************************************************************************/
namespace Montage\Cache;

interface Stateable {

  /**
   *  return true if the internal state has been changed since the object was instantiated
   *  or since importState has been called   
   *
   *  @return boolean
   */
  public function changedState();

  /**
   *  return a hash of the current state of this object's internal state
   *  
   *  the returned value should be compatible with {@link importState()} to return
   *  the object to the state it was in when this method was called         
   *
   *  @return array
   */
  public function exportState();
  
  /**
   *  re-populate the params of the object instance with the param values that were cached
   *  with {@link exportState()}   
   *  
   *  @param  array $state_map  a hash of the cached params
   *  @param  boolean $trust_state  true if this state can be completely trusted   
   *  @return boolean
   */
  public function importState(array $state_map = array(),$trust_state = false);
  
}//interface
