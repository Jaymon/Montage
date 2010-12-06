<?php

/**
 *  the montage_path type
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 12-6-10
 *  @package montage
 *  @subpackage type  
 ******************************************************************************/
class montage_path {

  /**
   *  this will hold the actual path this object represents 
   *
   *  @var  string   
   */        
  protected $path = '';

  public function __construct(){
  
    $path = func_get_args();
    $path = $this->build($path);
    $this->path = $this->assure($path);
  
  }//method
  
  public function __toString(){ return $this->path; }//method
  
  public function canWrite(){ return is_writable($this->path); }//method
  
  /**
   *  make sure a path exists and is writable, also make sure it doesn't end with
   *  a directory separator
   *  
   *  @param  string  $path
   *  @return string  the $path
   */
  protected function assure($path){
  
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
    
    return $this->format($path);
  
  }//method
  
  /**
   *  format $path to a standard format so we can guarrantee that all paths are formatted
   *  the same
   *  
   *  @since  4-20-10   
   *  @param  string  $path
   *  @return string  the $path, formatted for consistency
   */
  protected function format($path){
  
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
   *  @example  
   *    $this->build(array('foo','bar')); // -> foo/bar
   *    $this->build(array(array('foo','baz'),'bar')); // -> foo/baz/bar
   *    $this->build(array(array('foo','baz'),'/bar/che')); // -> foo/baz/bar/che
   *  
   *  @param  array $path_bits  the path bits that will be used to build the path
   *  @return string
   */
  protected function build(array $path_bits){
    
    $ret_list = array();
    
    foreach($path_bits as $path_bit){
      
      if(is_array($path_bit)){

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
   *  get all the subdirectories
   *  
   *  @param  boolean $go_deep  if true, then get all the sub directories recursively
   *  @return array an array of sub-directories, 1 level deep if $go_deep = false, otherwise
   *                all directories
   */
  public function getSubDirs($go_deep = true){
  
    // canary...
    if(!is_dir($this->path)){
      throw new RuntimeException(
        sprintf('"%s" is not a directory',$this->path)
      );
    }//if
    
    return $this->_getSubDirs($this->path,$go_deep);
      
  }//method
  
  /**
   *  recursively get all the child directories in a given directory
   *  
   *  @param  string  $path a valid directory path
   *  @param  boolean $go_deep  if true, then get all the directories recursively
   *  @return array an array of sub-directories, 1 level deep if $go_deep = false, otherwise
   *                all directories   
   */
  protected function _getSubDirs($path,$go_deep){
  
    $ret_list = array();
  
    $path_regex = join(DIRECTORY_SEPARATOR,array($path,'*'));
    $list = glob($path_regex,GLOB_ONLYDIR);
    
    if($go_deep){
    
      if(!empty($list)){
        
        foreach($list as $path){
          $sub_list = $this->_getSubDirs($path,$go_deep);
          array_unshift($sub_list,$path);
          $ret_list = array_merge($ret_list,$sub_list);
        }//foreach
        
      }//if
      
    }//if
    
    return $ret_list;
  
  }//method
  
  public function getFiles($regex = ''){

    $ret_list = array();

    $file_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->path));
    foreach($file_iterator as $file){
      
      $filepath = $file->getRealPath();
    
      if(empty($regex)){
      
        $ret_list[] = $filepath;
        
      }else{
      
        if(preg_match($regex,$filepath)){
      
          $ret_list[] = $filepath;
          
        }//if
          
      }//if/else
      
    }//foreach
    
    return $ret_list;

  }//method

  /**
   *  takes $path_1 and finds out where it starts in relation to $path_2, it then returns
   *  the rest of $path_1 that doesn't intersect with $path_2
   *  
   *  @example
   *    $path_1 = '/root/bar/baz';
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
  public function kill(){
  
    $ret_bool = $this->clear($this->path);
    
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
   *  @param  string  $path the starting path, all sub things will be removed
   *  @param  string  $regex  if a PCRE regex is passed in then only files matching it will be removed 
   */
  public function clear($path,$regex = ''){
  
    // canary...
    if(!is_dir($this->path)){ return unlink($this->path); }//if
    
    $ret_bool = true;
    $path_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->path));
    foreach($path_iterator as $file){
      
      $file_path = $file->getRealPath();
      
      if($file->isDir()){
        
        $ret_bool = $this->_clear($file_path,$regex);
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

}//class     
