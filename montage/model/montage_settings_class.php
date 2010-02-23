<?php

/**
 *  save all montage settings
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-21-10
 *  @package montage 
 ******************************************************************************/
class montage_settings extends montage_base {

  final function __construct(){
    $this->start();
  }//method

  function start(){}//method
  
  function setDebug($val){ return $this->setField('montage_settings_debug',$val); }//method
  function getDebug(){ return $this->getField('montage_settings_debug',''); }//method
  function hasDebug(){ return $this->hasField('montage_settings_debug'); }//method

  function setEnvironment($val){ return $this->setField('montage_settings_env',$val); }//method
  function getEnvironment(){ return $this->getField('montage_settings_env',''); }//method

  function setController($val){ return $this->setField('montage_settings_controller',$val); }//method
  function getController(){ return $this->getField('montage_settings_controller',''); }//method

}//class     
