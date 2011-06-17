<?php
/**
 *  handles all path related issues
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 12-6-10
 *  @package montage
 ******************************************************************************/
namespace Montage;

use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use RegexIterator;
use InvalidArgumentException;
use UnexpectedValueException;
 
class Path {

  /**
   *  this will hold the actual path this object represents 
   *
   *  @var  string   
   */        
  protected $path = '';

  /**
   *  create this class using one or more path bits
   *
   *  a path bit is the part of the path between the DIRECTORY_SEPARATOR (eg, /).
   *  
   *  @param  mixed $path,... one path or one or more path bits to be built into a path
   */
  public function __construct($path){
  
    $path = func_get_args();
    $path = $this->build($path);
    $this->path = $this->format($path);
  
  }//method
  
  public function __toString(){ return $this->path; }//method
  
  /**
   *  true if path exists or was built succesfully
   *  
   *  @param  string  $path
   *  @return string  the $path
   */
  public function assure(){
  
    // canary...
    if($this->exists()){ return true; }//if
  
    $path = $this->path
  
    // make sure path isn't empty...
    if(empty($path)){
      throw new InvalidArgumentException('cannot verify that an empty $path exists');
    }//if
    
    $orig_umask = umask(0000);
    
    // make sure path is directory, try to create it if it isn't...
    
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
    
    umask($orig_umask); // restore
    
    return true;
  
  }//method
  
  /**
   *  true if a path exists
   *  
   *  @return boolean
   */
  public function exists(){ return file_exists($this->path); }//method
  
  public function canWrite(){ return is_writable($this->path); }//method
  public function canRead(){ return is_readable($this->path); }//method
  
  /**
   *  get immediate children in the given path
   *  
   *  children are defined as all the contents in the given path 1 level deep (ie, the contents
   *  of folders inside the path won't be returned, just the folder names)         
   *
   *  @since  1-17-11
   *  @param  string  $regex  if you only want certain files/folders to be returned, you can match on the regex
   *  @return array array with files and folders keys set to found/matching contents      
   */
  public function getChildren($regex = ''){
  
    $ret_map = array('files' => array(),'folders' => array());
  
    $iterator = new FilesystemIterator(
      $this->path,
      FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
    );
    if(!empty($regex)){
    
      $iterator = new RegexIterator($iterator,$regex,RegexIterator::MATCH);
    
    }//if/else
  
    foreach($iterator as $key => $val){
    
      if($val->isFile()){
      
        $ret_map['files'][] = $key;
      
      }else{
      
        $ret_map['folders'][] = $key;
      
      }//if/else
    
    }//foreach
  
    return $ret_map;
  
  }//method
  
  /**
   *  get all contents inside the given path
   *  
   *  descendants are the contents of all folders and files found under the path recursively         
   *
   *  @since  1-17-11
   *  @param  string  $regex  if you only want certain files/folders to be returned, you can match on the regex
   *                          but be careful because regex matches on the full path   
   *  @return array array with files and folders keys set to found/matching contents      
   */
  public function getDescendants($regex = ''){
  
    $ret_map = array('files' => array(),'folders' => array());
  
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator(
        $this->path,
        FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
      ),
      RecursiveIteratorIterator::SELF_FIRST
    );
    if(!empty($regex)){
    
      $iterator = new RegexIterator($iterator,$regex,RegexIterator::MATCH);
    
    }//if/else
  
    foreach($iterator as $key => $val){

      if($val->isFile()){
      
        $ret_map['files'][] = $key;
      
      }else{
      
        $ret_map['folders'][] = $key;
      
      }//if/else
    
    }//foreach
    
    return $ret_map;
  
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

        $ret_list[] = $this->build($path_bit);
        
      }else{
        
        $path_bit = trim((string)$path_bit,'\\/');
        if(!empty($path_bit) && !ctype_space($path_bit)){
          $ret_list[] = $path_bit;
        }//if
        
      }//if/else
      
    }//foreach
    
    return join(DIRECTORY_SEPARATOR,$ret_list);
    
  }//method
  
  /**
   *  format $path to a standard format so we can guarantee that all paths are formatted
   *  the same
   *  
   *  @since  4-20-10   
   *  @param  string  $path
   *  @return string  the $path, formatted for consistency
   */
  protected function format($path){
  
    // canary...
    if(empty($path)){ return ''; }//if
  
    // make sure the path is a full valid path, none of this ../../ type stuff...
    $path = realpath($path);
  
    // make sure path doesn't end with a slash...
    if(mb_substr($path,-1) == DIRECTORY_SEPARATOR){
      $path = mb_substr($path,0,-1);
    }//if
    
    return $path;
  
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
  /* public static function getIntersection($path_1,$path_2){
  
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
  
  }//method */
  
  /**
   *  completely remove the given path and any children
   *  
   *  @since  8-25-10   
   *  @param  string  $path the path to completely remove
   *  @return boolean
   */
  /* public function kill(){
  
    $ret_bool = $this->clear($this->path);
    
    // if we cleared all the contents then get rid of the base folder also...
    if($ret_bool){
      $ret_bool = rmdir($path);
    }//if
  
    return $ret_bool;
  
  }//method */
  
  /**
   *  recursively clear an entire directory, files, folders, everything
   *  
   *  @since  8-25-10   
   *  @param  string  $path the starting path, all sub things will be removed
   *  @param  string  $regex  if a PCRE regex is passed in then only files matching it will be removed 
   */
  /* public function clear($path,$regex = ''){
  
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
    
  }//method */

}//class     
