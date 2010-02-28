<?php

/**
 *  save all montage settings
 *  
 *  You can save any field by just calling setField('name',$val) but there
 *  are a few predifined methods to make it easy to set/get certain setting fields   
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-21-10
 *  @package montage 
 ******************************************************************************/
class montage_settings extends montage_base {

  const FIELD_DEBUG = 'montage_settings_debug';
  const FIELD_CHARSET = 'montage_settings_charset';
  const FIELD_TIMEZONE = 'montage_settings_timezone';

  /**
   *  start the settings instance
   *  
   *  @param  boolean if debug is on or not
   *  @param  string  $charset  the default charset
   *  @param  string  $timezone the default timezone
   */
  final function __construct($debug,$charset,$timezone){
  
    $this->setField(self::FIELD_DEBUG,$debug);
    $this->setField(self::FIELD_CHARSET,$charset);
    $this->setField(self::FIELD_TIMEZONE,$timezone);
  
  
    $this->start();
  }//method

  final function setDebug($val){
  
    if($val){
      ini_set('display_errors','on');
    }else{
      // since debug isn't on let's not display the errors to the user and rely on logging...
      ini_set('display_errors','off');
    }//if/else
    
    return $this->setField(self::FIELD_DEBUG,$val);
    
  }//method
  final function getDebug(){ return $this->getField(self::FIELD_DEBUG,false); }//method
  final function hasDebug(){ return $this->hasField(self::FIELD_DEBUG); }//method
  
  final function setCharset($val){
    mb_internal_encoding($val);
    return $this->setField(self::FIELD_CHARSET,$val);
  }//method
  final function getCharset(){ return $this->getField(self::FIELD_CHARSET,''); }//method
  final function hasCharset(){ return $this->hasField(self::FIELD_CHARSET); }//method
  
  final function setTimezone($val){
    date_default_timezone_set($val);
    return $this->setTimezone(self::FIELD_TIMEZONE,$val);
  }//method
  final function getTimezone(){ return $this->getField(self::FIELD_TIMEZONE,''); }//method
  final function hasTimezone(){ return $this->hasField(self::FIELD_TIMEZONE); }//method

}//class     
