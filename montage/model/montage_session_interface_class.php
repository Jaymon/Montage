<?php

/**
 *  
 *
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 3-11-10
 *  @package montage
 ******************************************************************************/
abstract class montage_session_interface {

  final function __construct(){
  
    session_set_save_handler(
      array($this,'open'),
      array($this,'close'),
      array($this,'read'),
      array($this,'write'),
      array($this,'destroy'),
      array($this,'gc')
    );
  
    $this->start();
    
  }//method
  
  abstract function start();
  
  abstract function open($save_path,$session_name);
  
  abstract function close();
  
  abstract function read($id);
  
  abstract function write($id,$data);
  
  abstract function destroy($id);
  
  abstract function gc($max_lifetime);
  
}//class
