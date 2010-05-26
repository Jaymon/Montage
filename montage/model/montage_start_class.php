<?php

/**
 *  a start class is what will be used by a montage app to do configuration things
 *  
 *  these classes are thrown away after they are started, they are basically like a
 *  configuration file so things should be stored in a montage_settings instance (ie,
 *  use the start() method of this class to set all your settings in montage_settings, retrieved
 *  via {@link montage::getSettings()}     
 *  
 *  @abstract 
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-21-10
 *  @package montage 
 ******************************************************************************/
abstract class montage_start {

  final function __construct(){
    $this->start();
  }//method

  /**
   *  start() is very important for montage_start children since this method will
   *  be called and allow configuration stuff to be done
   */
  abstract protected function start();
  
}//class     
