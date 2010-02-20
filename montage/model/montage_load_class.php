<?php

/**
 *  handle autoloading duties    
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
final class montage_load {

  ///static private $path_include_list = array();

  private $path_map = array();
  
  private $class_map = array();

  function __construct(){
    $this->start();
  }//method
  
  function start(){
  
    // set this class to use its self as an autoloader...
    $this->set(array($this,'get'));
  
  }//method
  
  function setPath($path){
  
    // canary...
    if(empty($path)){
      throw new UnexpectedValueException('tried to set an empty $path');
    }//if
    if(!is_dir($path)){
      throw new UnexpectedValueException(sprintf('"%s" is not a valid directory',$path));
    }//if
    
    /**
    clearstatcache();
    ///out::e(filesize($path));
    out::e(stat($path));
    exit;
    **/
    
    // get all the directories in this path...
    $path_list = $this->getPaths($path);
    $this->path_map[$path] = $path_list;
  
  }//method
  
  /**
   *  recursively get all the child directories in a given directory
   *  
   *  @param  string  $path a valid directory path
   *  @return array an array of $path and all its sub-directory paths
   */
  private function getPaths($path){
  
    // canary...
    if(empty($path)){ return array(); }//if
    
    $ret_list = array($path);
    $path_list = glob(join(DIRECTORY_SEPARATOR,array($path,'*')),GLOB_ONLYDIR);
    if(!empty($path_list)){
      
      foreach($path_list as $path){
        $ret_list = array_merge($ret_list,$this->getPaths($path));
      }//foreach
      
    }//if
      
    return $ret_list;
      
  }//method

  /**
   *  register an autoload function
   *  
   *  @param  callback  $callback a valid php callback
   *  @return boolean         
   *
   */        
  function set($callback){
    return spl_autoload_register($callback,true);
  }//method

  /**
   *  load a class
   *  
   *  @return boolean      
   */
  function get($class){
  
    // if you just get blank pages: http://www.php.net/manual/en/function.error-reporting.php#28181
    //  http://www.php.net/manual/en/function.include-once.php#53239
  
    $name_list = array();
    $name_list[] = sprintf('%s_class.php',$class);
    $name_list[] = sprintf('%s.class.php',$class);
    $name_list[] = sprintf('%s.php',$class);
    
    // go through each of the paths...
    foreach($this->path_map as $path_list){
    
      // now go through each path and check it agains $name_list...
      foreach($path_list as $path){
        
        foreach($name_list as $name){
          
          $filepath = join(DIRECTORY_SEPARATOR,array($path,$name));
          if(file_exists($filepath)){
            require($filepath);
            $this->class_map[$class] = $filepath;
            return true;
          }//if
          
        }//foreach
      
      }//foreach
    
    }//foreach

    /**
    $backtrace = debug_backtrace();
    $file = empty($backtrace[1]['file']) ? 'unknown' : $backtrace[1]['file'];
    $line = empty($backtrace[1]['line']) ? 'unknown' : $backtrace[1]['line']; 
    out::t();
    trigger_error($class.' was not found, called from '.$file.':'.$line,E_USER_ERROR);
    **/
    
    // can't use montage exception here because something might have failed before here...
    throw new Exception(sprintf('could not find class %s',$class));
    return false;
  
  }//method

}//class     
