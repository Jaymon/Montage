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
      if(!mkdir($path,0777,true)){
        throw new UnexpectedValueException(
          sprintf(
            '"%s" is not a valid directory and the attempt to create it failed. '
            .'Check permissions for every directory on the path to make sure that path '
            .'is writable.',
            $path
          )
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
   *  turns $val into something that could be used as a path bit
   *  
   *  basically, take some $val and turn it into a path bit
   *  
   *  @example
   *    $val = 'This is a String WITH spaces and a /';
   *    self::convert($val); // -> 'this_string_spaces'           
   *      
   *  @since  2-4-10   
   *  @param  string|array  $val  a normal string to be converted into a path string
   *  @param  integer $char_limit max chars $input can be
   *  @param  string  $space_delim  what you want to use for spaces (usually dash or underscore)            
   *  @return string|array  path safe string
   */
  static public function convert($val,$char_limit = 0,$space_delim = '_'){
  
    // canary...
    if(empty($val)){ return is_array($val) ? array() : ''; }//if
    
    $ret_list = array();
    
    $ret_as_list = false;
    if(is_array($val)){
      $ret_as_list = true;
    }else{
      $val = array($val);
    }//if/else
  
    foreach($val as $v){
    
      // get rid of whitespace fat...
      $ret_str = trim($v);
      
      // replace anything that isn't a space or word char with nothing...
      $ret_str = preg_replace('/[^\w \-]/','',$ret_str);
      
      // make everything lower case...
      $ret_str = mb_strtolower($ret_str);
      
      // get rid of stop words and join the string with the delim...
      $ret_str = join($space_delim,array_filter(montage_text::killStopWords($ret_str)));
    
      // impose a character limit if there is one...
      if($char_limit > 0){
      
        $ret_str = montage_text::getWordSubStr($ret_str,0,$char_limit);
        
        /* since we're using getWordSubStr instead of mb_substr this shouldn't be needed anymore
        // make sure there isn't something dumb on the end like a "_", or just contains ___...
        $regex_space_delim = preg_quote($space_delim);
        $ret_str = preg_replace(
          sprintf('/(?:^[%s]+)|(?:[%s]+$)/',$regex_space_delim,$regex_space_delim)
          '',
          $ret_str
        );
        */
        
      }//if
      
      $ret_list[] = $ret_str;
      
    }//foreach
    
    return $ret_as_list ? $ret_list : $ret_list[0];
  
  }//method

  /**
   *  given multiple path bits, build a custom path
   *  
   *  @example  self::get('foo','bar'); // -> foo/bar
   *  
   *  @param  $args,... one or more path bits
   *  @return string
   */
  static function get(){
    
    $ret_list = array();
    $path_bits = func_get_args();
    
    foreach($path_bits as $path_bit){
      
      if(is_array($path_bit)){
        
        ///$path_bit = array_map('trim', $path_bit,array_fill(0,count($path_bit),'\\/'));
        ///$ret_list = array_merge($ret_list,$path_bit);
        // turns out this was faster in my dumb tests...
        foreach($path_bit as $pb){
          if(!empty($pb) || !ctype_space($pb)){
            $ret_list[] = trim($pb,'\\/');
          }//if
        }//foreach
        
      }else{
        
        $path_bit = trim($path_bit,'\\/');
        if(!empty($path_bit) || !ctype_space($path_bit)){
          $ret_list[] = $path_bit;
        }//if
        
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
   *  takes $path_1 and finds out where it starts in relation to $path_2, it then returns
   *  the rest of $path_1 that doesn't intersect with $path_2
   *  
   *  @example
   *    $path_1 = '/foo/bar/baz';
   *    $path_2 = /root/che/foo/'
   *    $this->getIntersection($path_1,$path_2); // array('bar','baz');               
   *  
   *  @since  8-8-10      
   *  @param  string  $path_1
   *  @param  string  $path_2 the root path   
   *  @return array the remaining elements of $path_1 where it starts in relation to $path_2            
   */
  public static function getIntersection($path_1,$path_2){
  
    // canary...
    if(empty($path_1)){ return array(); }//if
    if(empty($path_2)){ return $path_1; }//if
    
    // canary, split 'em if we have to...
    if(!is_array($path_1)){ $path_1 = preg_split('#\\/#u',$path_1); }//if
    if(!is_array($path_2)){ $path_2 = preg_split('#\\/#u',$path_2); }//if
  
    // canary, we don't want '' values throwing us off, and we want to reset the keys (just in case)...
    $path_1 = array_values(array_filter($path_1));
    $path_2 = array_values(array_filter($path_2));
    
    $ret_path = array();
  
    // find where the last directory of path 2 is the first directory of path 1...
    $key = array_search($path_2[(count($path_2) - 1)],$path_1);
    
    if($key !== false){
    
      $ret_path = array_slice($path_1,$key + 1);
    
    }else{
    
      // since there was no dir in common with root, set path from the root...
      $ret_path = $path_1;
      
    }//if/else
    
    return $ret_path;
  
  }//method
  
  /**
   *  completely remove the given path and any children
   *  
   *  @since  8-25-10   
   *  @param  string  $path the path to completely remove
   *  @return boolean
   */
  static public function kill($path){
  
    $ret_bool = self::clear($path);
    
    // if we cleared all the contents then get rid of the base folder also...
    if($ret_bool){
      $ret_bool = rmdir($path);
    }//if
  
    return $ret_bool;
  
  }//method
  
  /**
   *  recursively clear an entire directory, files, folders, everything
   *  
   *  @since  8-25-10   
   *  @see  montage_cache::clear()
   *  @param  string  $path the starting path, all sub things will be removed
   *  @param  string  $regex  if a PCRE regex is passed in then only files matching it will be removed 
   */
  static public function clear($path,$regex = ''){
  
    // canary...
    if(!is_dir($path)){ return false; }//if
    
    $ret_bool = true;
    $path_iterator = new RecursiveDirectoryIterator($path);
    foreach($path_iterator as $file){
      
      $file_path = $file->getRealPath();
      
      if($file->isDir()){
        
        $ret_bool = self::clear($file_path,$regex);
        if($ret_bool){
          rmdir($file_path);
        }//if
      
      }else{
    
        if(!empty($regex)){
        
          $ret_bool = false;
        
          // make sure we only kill files that match regex...
          if(preg_match($regex,$file->getFilename())){
            $ret_bool = unlink($file_path);
          }//if
        
        }else{
        
          $ret_bool = unlink($file_path);
          
        }//if/else
      
      }//if/else

    }//foreach
    
    return $ret_bool;
    
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
