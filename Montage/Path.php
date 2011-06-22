<?php
/**
 *  handles all path related issues
 *  
 *  @version 0.3
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
use Countable;
 
class Path implements Countable {

  /**
   *  this will hold the actual path this object represents 
   *
   *  @var  string   
   */        
  protected $path = '';
  
  protected $children_map = array();

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
  
    $path = $this->path;
  
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
   *  count all the descendants of the path
   *  
   *  @since  6-20-11
   *  @return integer
   */
  public function count(){ return $this->countChildren(); }//method
  
  /**
   *  count all the descendants of the path that match $regex
   *  
   *  @since  6-20-11
   *  @param  string  $regex  a regex to match paths with
   *  @param  integer $depth  use 1 to get just immediate children, defaults to all depths      
   *  @return integer
   */
  public function countChildren($regex = '',$depth = -1){
  
    /// \out::p(sprintf('count sub paths %s with regex %s',$this,$regex));
  
    // I could think about doing something like: ls -R -l |wc -l on the command line...
    // http://linuxcommando.blogspot.com/2008/07/how-to-count-number-of-files-in.html
  
    $ret_count = 0;
    $map = $this->getChildren($regex,$depth);
    foreach($map as $list){ $ret_count += count($list); }//foreach
    
    /// \out::p();
    
    return $ret_count;
  
  }//method
  
  /**
   *  true if the internal path is an ancestor of all of the passed in paths
   *  
   *  @example
   *    internal path: /foo/
   *    $this->isParent('foo/bar'); // true
   *    $this->isParent('foo/bar','foo/bar/che/baz'); // true
   *    $this->isParent('foo/bar','che/baz'); // false   
   *
   *  @since  6-22-11
   *  @param  string  $path,... one or more passed in paths to check the internal path against
   *  @return boolean
   */
  public function isParent($path){
  
    $path_list = func_get_args();
    $callback = function($instance_path,$path){
    
      return (mb_stripos((string)$path,$instance_path) !== false);
    
    };
    
    return $this->compare($path_list,$callback,false);
    
  }//method
  
  /**
   *  true if the internal path is a descendant of all of the passed in paths
   *  
   *  @example
   *    internal path: /foo/bar/baz
   *    $this->isChild('foo/bar'); // true
   *    $this->isChild('foo/','foo/bar/'); // true
   *    $this->isChild('foo/bar','che/baz'); // false   
   *
   *  @since  6-22-11
   *  @param  string  $path,... one or more passed in paths to check the internal path against
   *  @return boolean
   */
  public function isChild($path){
  
    $path_list = func_get_args();
    $callback = function($instance_path,$path){
    
      /// string haystack, string needle
      return (mb_stripos($instance_path,(string)$path) !== false);
    
    };
    
    return $this->compare($path_list,$callback,false);
    
  }//method
  
  /**
   *  true if the internal path is a descendant/child of any of the passed in paths
   *  
   *  @example
   *    internal path: /foo/bar/che
   *    $this->inParents('foo/bar'); // true
   *    $this->inParents('foo/bar/che/baz'); // false
   *
   *  @since  6-20-11   
   *  @param  string  $path,... one or more passed in paths to check the internal path against, only one
   *                            needs to match to return true   
   *  @return boolean
   */
  public function inParents($path){
  
    $path_list = func_get_args();
    $callback = function($instance_path,$path){
    
      return (mb_stripos($instance_path,(string)$path) !== false);
    
    };
    
    return $this->compare($path_list,$callback);
    
  }//method
  
  /**
   *  true if the internal path is a ancestor/parent of any of the passed in paths
   *  
   *  @example
   *    internal path: /foo/bar
   *    $this->inChildren('foo/bar/che'); // true
   *    $this->inChildren('foo/bar/che','foo'); // true   
   *    $this->inChildren('foo/'); // false
   *
   *  @since  6-21-11   
   *  @param  string  $path,... one or more passed in paths to check the internal path against, only one
   *                            needs to match to return true
   *  @return boolean
   */
  public function inChildren($path){
  
    $path_list = func_get_args();
    $callback = function($instance_path,$path){
    
      return (mb_stripos((string)$path,$instance_path) !== false);
    
    };
    
    return $this->compare($path_list,$callback);
  
  }//method
  
  /**
   *  true if the internal path is a ancestor/parent of any of the passed in paths
   *  
   *  @example
   *    internal path: /foo/bar
   *    $this->inParents('foo/bar/che'); // true
   *    $this->inParents('foo/bar/che','foo'); // true   
   *    $this->inParents('foo/'); // false
   *
   *  @since  6-22-11   
   *  @param  string  $path,... one or more passed in paths to check the internal path against, only one
   *                            needs to match to return true
   *  @return boolean
   */
  public function inFamily($path){
  
    $path_list = func_get_args();
    $callback = function($instance_path,$path){
    
      return (mb_stripos((string)$path,$instance_path) !== false)
        || (mb_stripos($instance_path,(string)$path) !== false);
    
    };
    
    return $this->compare($path_list,$callback);
  
  }//method
  
  /**
   *  compare every path in $path_list with the internal path using $callback
   *  
   *  @since  6-22-11
   *  @param  array $path_list  an array of paths to compare against the internal path
   *  @param  callback  $callback a callback that takes ($this->path,$path)
   *  @param  mixed $break_on the return value of $callback will be compared with this to
   *                          decide if comparing is done                  
   *  @return mixed whatever $callback returns
   */
  protected function compare(array $path_list,$callback,$break_on = true){
  
    $ret_mixed = false;
    $self = get_class($this);
  
    foreach($path_list as $path){
    
      if(!empty($path)){
      
        if(is_array($path)){
        
          $ret_mixed = call_user_func(array($this,__FUNCTION__),$path,$callback);
        
        }else{
        
          if(!($path instanceof $self)){ $path = new $self($path); }//if
          $ret_mixed = $callback($this->path,$path);
        
        }//if/else
      
        if($ret_mixed === $break_on){ break; }//if
        
      }//if
    
    }//foreach
  
    return $ret_mixed;
  
  }//method
  
  /**
   *  get children in the given path
   *  
   *  children are all contents of the path N levels deep         
   *
   *  @since  1-17-11
   *  @param  string  $regex  if you only want certain files/folders to be returned, you can match on the regex
   *  @param  integer $depth  use 1 to get just immediate children, defaults to all depths   
   *  @return array array with files and folders keys set to found/matching contents      
   */
  public function getChildren($regex = '',$depth = -1){
  
    // canary...
    if(isset($this->children_map[$regex][$depth])){
      return $this->children_map[$regex][$depth];
    }//if
  
    $ret_map = array('files' => array(),'folders' => array());
    $depth = (int)$depth;
    $iterator = null;
  
    if(($depth < 0) || ($depth > 1)){
    
      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
          $this->path,
          FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
        ),
        RecursiveIteratorIterator::SELF_FIRST
      );
      
      $iterator->setMaxDepth($depth);
    
    }else{
    
      $iterator = new FilesystemIterator(
        $this->path,
        FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
      );
      
    }//if/else
      
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
  
    // cache the result in memory...
    $this->children_map[$regex] = array();
    $this->children_map[$regex][$depth] = $ret_map;
  
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
        
        $path_bit = preg_split('#[\\\\/]#',$path_bit); // split on dir separators
        $path_bit = array_map('trim',$path_bit); // trim all the individual bits
        $path_bit = array_filter($path_bit); // get rid of any empty values
        if(!empty($path_bit)){
          $ret_list = array_merge($ret_list,$path_bit); // merge the bits into the final list
        }//if
        
        ///$path_bit = trim((string)$path_bit,'\\/');
        ///if(!empty($path_bit) && !ctype_space($path_bit)){
        ///  $ret_list[] = $path_bit;
        ///}//if
        
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
    if($realpath = realpath($path)){
      $path = $realpath;
    }//if
  
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
