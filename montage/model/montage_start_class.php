<?php

/**
 *  a start class is what will be used by a montage app to do configuration things
 *  
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
  abstract function start();
  
}//class     
