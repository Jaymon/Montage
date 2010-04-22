<?php

/**
 *  holds path helper methods
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 3-8-10
 *  @package montage
 *  @subpackage help  
 ******************************************************************************/
class montage_path extends montage_base_static {

  /**
   *  make sure a path exists and is writable, also make sure it doesn't end with
   *  a directory separator
   *  
   *  @param  string  $path
   *  @return string  the $path
   */
  static function assure($path){
  
    // make sure path isn't empty...
    if(empty($path)){
      throw new UnexpectedValueException('cannot verify that an empty $path exists');
    }//if
    
    // make sure path is directory, try to create it if it isn't...
    if(!is_dir($path)){
      if(!mkdir($path,0755,true)){
        throw new UnexpectedValueException(
          sprintf('"%s" is not a valid directory and the attempt to create it failed.',$path)
        );
      }//if
    }//if
  
    // make sure the path is writable...
    if(!is_writable($path)){
      throw new RuntimeException(sprintf('cannot write to $path (%s)',$path));
    }//if
    
    return self::format($path);
  
  }//method
  
  /**
   *  format $path to a standard format so we can guarrantee that all paths are formatted
   *  the same
   *  
   *  @since  4-20-10   
   *  @param  string  $path
   *  @return string  the $path, formatted for consistency
   */
  static function format($path){
  
    // canary...
    if(empty($path)){ return ''; }//if
  
    // make sure path doesn't end with a slash...
    if(mb_substr($path,-1) == DIRECTORY_SEPARATOR){
      $path = mb_substr($path,0,-1);
    }//if
    
    return $path;
  
  }//method

  /**
   *  given multiple path bits, build a custom path
   *  
   *  @example  self::getCustomPath('foo','bar'); // -> foo/bar
   *  
   *  @param  $args,... one or more path bits
   *  @return string
   */
  static function get(){
    $ret_list = array();
    $path_bits = func_get_args();
    foreach($path_bits as $path_bit){
      if(is_array($path_bit)){
        $ret_list = array_merge($ret_list,$path_bit);
      }else{
        $ret_list[] = $path_bit;
      }//if/else
    }//foreach
    return join(DIRECTORY_SEPARATOR,$ret_list);
  }//method
  
  /**
   *  recursively get all the child directories in a given directory
   *  
   *  @param  string  $path a valid directory path
   *  @param  boolean $go_deep  if true, then get all the directories   
   *  @return array an array of sub-directories, 1 level deep if $go_deep = false, otherwise
   *                all directories   
   */
  static function getDirectories($path,$go_deep = true){
  
    // canary...
    if(empty($path)){ return array(); }//if
    if(!is_dir($path)){ return array(); }//if
    
    $ret_list = glob(join(DIRECTORY_SEPARATOR,array($path,'*')),GLOB_ONLYDIR);
    if($go_deep){
    
      if(!empty($ret_list)){
        
        foreach($ret_list as $path){
          $ret_list = array_merge($ret_list,self::getDirectories($path,$go_deep));
        }//foreach
        
      }//if
      
    }//if
    
    return $ret_list;
      
  }//method
  
  /**
   *  set the montage root path
   *  
   *  @param  string  $val
   */
  static function setFramework($val){ self::setField('montage_framework_path',self::format($val)); }//method
  
  /**
   *  get the montage root path
   *  
   *  @return string
   */
  static function getFramework(){ return self::getField('montage_framework_path',''); }//method
  
  /**
   *  set the montage app root path
   *  
   *  @param  string  $val
   */
  static function setApp($val){ self::setField('montage_app_path',self::format($val)); }//method
  
  /**
   *  get the montage app root path
   *  
   *  @return string
   */
  static function getApp(){ return self::getField('montage_app_path',''); }//method
  
  /**
   *  set the montage app's default cache path
   *  
   *  @param  string  $val
   */
  static function setCache($val){ self::setField('montage_cache_path',self::format($val)); }//method
  
  /**
   *  get the montage app's default cache path
   *  
   *  @return string
   */
  static function getCache(){ return self::getField('montage_cache_path',''); }//method

}//class     