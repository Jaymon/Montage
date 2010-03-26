<?php

/**
 *  this is the master class, it is static and available from everywhere in your app,
 *  it provides access to the most commonly used montage objects so they can be easily
 *  retrieved from anywhere     
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
final class montage extends montage_base_static {

  /**
   *  return the montage_request instance
   *  
   *  @return montage_request      
   */
  static function getRequest(){ return self::getField('montage_request'); }//method

  /**
   *  return the montage_response instance
   *  
   *  @return montage_response      
   */
  static function getResponse(){ return self::getField('montage_response'); }//method
  
  /**
   *  return the montage_settings instance
   *  
   *  @return montage_settings      
   */
  static function getSettings(){ return self::getField('montage_settings'); }//method
  
  /**
   *  return the montage_url instance
   *  
   *  @return montage_url      
   */
  static function getUrl(){ return self::getField('montage_url'); }//method
  
  /**
   *  return the montage_log instance
   *  
   *  @return montage_log      
   */
  static function getLog(){ return self::getField('montage_log'); }//method
  
  /**
   *  return the montage_session instance
   *  
   *  @return montage_session      
   */
  static function getSession(){ return self::getField('montage_session'); }//method
  
  /**
   *  return the montage_cookie instance
   *  
   *  @return montage_cookie    
   */
  static function getCookie(){ return self::getField('montage_cookie'); }//method

}//class     
