<?php

/**
 *  start the mingo montage plugin
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 2-24-10
 *  @package mingo
 *  @subpackage montage 
 ******************************************************************************/
class mingo_start extends montage_start {
  
  const KEY_INTERFACE = 'mingo_db_interface';
  const KEY_NAME = 'mingo_db_name';
  const KEY_HOST = 'mingo_host';
  const KEY_USERNAME = 'mingo_username';
  const KEY_PASSWORD = 'mingo_password';
  
  protected function start(){
    
    $settings = montage::getSettings();
  
    // get an instance...
    $db = mingo_db::getInstance();
    $db->setDebug($settings->getDebug());
    
    try{
      
      // actually connect to the db...
      $db->connect(
        $settings->getField(self::KEY_INTERFACE,''),
        $settings->getField(self::KEY_NAME,''),
        $settings->getField(self::KEY_HOST,''),
        $settings->getField(self::KEY_USERNAME,''),
        $settings->getField(self::KEY_PASSWORD,'')
      );
      
    }catch(Exception $e){
    
      $this_reflect = new ReflectionObject($this);
      $key_list = array();
      
      $constant_list = $this_reflect->getConstants();
      foreach($constant_list as $constant_name => $constant_val){
        if(preg_match('#^KEY_#',$constant_name)){
          $key_list[] = sprintf('%s = "%s"',$constant_val,$settings->getField($constant_name,''));
        }//if
      }//foreach
    
      $e_msg = sprintf(
        'Mingo connection failed with error, "%s." Available Settings fields: [%s]',
        $e->getMessage(),
        join(sprintf(',%s',PHP_EOL),$key_list)
      );
    
      throw new RuntimeException($e_msg);
    
    }//try/catch
    
  }//method

}//class
