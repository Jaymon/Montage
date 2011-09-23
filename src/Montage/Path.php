<?php
/**
 *  handles all path related issues
 *  
 *  @link http://us2.php.net/splfileinfo
 *  
 *  @version 0.4
 *  @author Jay Marcyes
 *  @since 12-6-10
 *  @package montage
 ******************************************************************************/
namespace Montage;

use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use CallbackFilterIterator;
use RegexIterator;
use Countable,IteratorAggregate;
use SplFileInfo;
 
class Path extends SplFileInfo implements Countable,IteratorAggregate {
  
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
    $path = $this->format($path);
    
    parent::__construct($path);
  
  }//method

  /**
   *  true if path exists or was built succesfully
   *  
   *  @param  string  $path
   *  @return string  the $path
   */
  public function assure(){
  
    // canary...
    if($this->exists()){ return true; }//if
  
    $path = $this->getPathname();
  
    // make sure path isn't empty...
    if(empty($path)){
      throw new \InvalidArgumentException('cannot verify that an empty $path exists');
    }//if
    
    $orig_umask = umask(0000);
    
    // make sure path is directory, try to create it if it isn't...
    
    if(!mkdir($path,0777,true)){
      throw new \UnexpectedValueException(
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
  public function exists(){ return file_exists($this->getPathname()); }//method
  
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
  
    $children = $this->getChildren($regex,$depth);
    $ret_count = $this->children_map[$regex][$depth]['count'];
    
    ///$ret_count = 0;
    ///$iterator = $this->createIterator($regex,$depth);
    ///foreach($iterator as $path => $file){ $ret_count++; }//foreach
    
    /// \out::p();
    
    return $ret_count;
  
  }//method
  
  /**
   *  find an adjacent/sibling file/folder from the internal path
   *
   *  @since  9-22-11
   */           
  public function getSibling($regex){
  
    $ret_path = null;
    $parent_path = $this->normalize((string)$this);
  
    // keep moving up a directory looking for a child that matches the $regex...
    while($parent_path = $parent_path->getParent()){
    
      if($path = $parent_path->getChild($regex)){
      
        $ret_path = $this->normalize($path);
        break;
      
      }//if

    }//while

    return $ret_path;  
  
  }//method
  
  /**
   *  move up either one directory or until the directory that matches $folder_regex is found
   *
   *  @example
   *    // internal path: /foo/bar/baz/che
   *    $this->isDir(); // true   
   *    $this->getParent('#bar#'); // /foo/bar
   *    $this->getParent(); // /foo/bar/baz
   *    
   *    // internal path: /foo/bar/baz.che
   *    $this->isFile(); // true
   *    $this->getParent('#foo#'); // /foo/
   *    $this->getParent(); // /foo/bar/
   *      
   *  @since  8-30-11
   *  @param  string  $folder_regex  a regex to match with a parent directory
   *  @return Path  null if moving up isn't possible, otherwise a new instance with the new path in it      
   */
  public function getParent($folder_regex = ''){
  
    $ret_path = null;
    $path_bits = explode(DIRECTORY_SEPARATOR,(string)$this);
  
    if(empty($folder_regex)){ // just move up one if not folder specified
      
      // slice off one if dir or 2 (filename and dirname if file)...
      ///$slice_length = $this->isFile() ? -2 : -1;
      $slice_length = -1;
      $path_bits = array_slice($path_bits,0,-1); // get rid of the last 
      
      if(!empty($path_bits)){
        
        $ret_path = $this->normalize($path_bits);
        
      }//if
    
    }else{
    
      // move up the path until the $folder_regex is found in a folder's name
      while(!empty($path_bits)){
      
        end($path_bits);
        $last_bit = current($path_bits);
        
        if(preg_match($folder_regex,$last_bit)){
          
          $class_name = get_class($this);
          $ret_path = new $class_name($path_bits);
          break;
          
        }//if
        
        $path_bits = array_slice($path_bits,0,-1); // remove the last bit
      
      }//while
    
    }//if/else
  
    return $ret_path;
  
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
   *  @param  callback  $callback a callback that takes ($this->getPathname(),$path)
   *  @param  mixed $break_on the return value of $callback will be compared with this to
   *                          decide if comparing is done                  
   *  @return mixed whatever $callback returns
   */
  protected function compare(array $path_list,$callback,$break_on = true){
  
    $ret_mixed = false;
  
    foreach($path_list as $path){
    
      if(!empty($path)){
      
        if(is_array($path)){
        
          $ret_mixed = call_user_func(array($this,__FUNCTION__),$path,$callback);
        
        }else{
        
          $path = $this->normalize($path);
          $ret_mixed = $callback($this->getPathname(),$path);
        
        }//if/else
      
        if($ret_mixed === $break_on){ break; }//if
        
      }//if
    
    }//foreach
  
    return $ret_mixed;
  
  }//method
  
  /**
   *  get the first child matching the $regex
   *  
   *  @since  7-28-11
   *  @param  string  $regex  the PCRE pattern the file needs to match
   *  @param  integer $depth  use 1 to get just immediate children, defaults to all depths 
   *  @return string  the matching child path
   */
  public function getChild($regex,$depth = -1){
  
    $ret_path = '';
  
    $map = $this->getChildren($regex,$depth);
    if(!empty($map['files'])){
    
      $ret_path = reset($map['files']);
      
    }else if(!empty($map['folders'])){
      
      $ret_path = reset($map['folders']);
      
    }//if/else if
  
    return $ret_path;
  
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
      return $this->children_map[$regex][$depth]['children'];
    }//if
  
    $count = 0;
    $ret_map = array('files' => array(),'folders' => array());
    $iterator = $this->createIterator($regex,$depth);
  
    foreach($iterator as $key => $val){
    
      if($val->isFile()){
      
        $ret_map['files'][] = $key;
      
      }else{
      
        $ret_map['folders'][] = $key;
      
      }//if/else
      
      $count++;
    
    }//foreach
  
    // cache the result in memory...
    $this->children_map[$regex] = array();
    $this->children_map[$regex][$depth] = array(
      'children' => $ret_map,
      'count' => $count
    );
  
    return $ret_map;
  
  }//method

  /**
   *  recursively clear an entire directory, files, folders, everything
   *  
   *  @since  8-25-10   
   *  @param  string  $regex  if a PCRE regex is passed in then only files matching it will be removed
   *  @return integer how many files/folders were cleared    
   */
  public function clear($regex = ''){
  
    // canary, just empty the file contents if it is a file...
    if($this->isFile()){
      $file_instance = $this->openFile('r+');
      $file_instance->ftruncate(0);
      return 1;
    }//if
    
    $ret_count = 0;
    $last_path = '';
    $iterator = $this->createIterator($regex);
    foreach($iterator as $path => $file){
    
      if($file->isFile()){
      
        if(unlink($path)){
          $ret_count++;
        }//if
      
      }else{
      
        if(!empty($last_path)){
        
          // the directory should be empty now...
          if(rmdir($last_path)){
            $ret_count++;
          }//if
        
        }//if
        
        $last_path = $path;
      
      }//if/else
    
    }//foreach
    
    if(!empty($last_path) && rmdir($last_path)){ $ret_count++; }//if
    
    return $ret_count;
    
  }//method
  
  /**
   *  completely remove the given path and any children
   *  
   *  @since  8-25-10   
   *  @param  string  $path the path to completely remove
   *  @return boolean
   */
  public function kill(){
  
    $ret_count = $this->clear();
    if(rmdir($this->getPathname())){ $ret_count++; }//if
    
    return $ret_count;
  
  }//method

  /**
   *  append $data to a file
   *  
   *  @since  9-22-11
   *  @see  write()
   */
  public function append($data){ return $this->write($data,LOCK_EX | FILE_APPEND); }//method
  
  /**
   *  write $data to a file
   *  
   *  @since  9-22-11
   *  @see  write()
   */
  public function set($data){ return $this->write($data,LOCK_EX); }//method

  /**
   *  get the contents of file
   *  
   *  @since  9-22-11
   *  @return string  the contents of the file
   */
  public function get(){
    
    // canary...
    if($this->isDir()){
      throw new \UnexpectedValueException(
        sprintf('Use getChildren() to get the contents of a directory. Path: %s',$this)
      );
    }//if
    
    return file_get_contents($this);
    
  }//method

  /**
   *  split the path by the directory separators
   *
   *  @example
   *    echo $this; // /foo/bar/baz/che
   *    $bits = $this->split(); // $bits equals array('foo','bar','baz','che');
   *      
   *  @since  9-21-11
   *  @return array a list of directory separators      
   */
  public function split(){ return preg_split('#\\/#',(string)$this); }//method

  /**
   *  gets a unique fingerprint for the instance
   *  
   *  @since  9-21-11
   *  @return string
   */
  public function getFingerprint(){
  
    // canary...
    if(!$this->isFile()){
      throw new \UnexpectedValueException(
        sprintf('You can only get the fingerprint of a valid file that exists. Path: %s',$this)
      );
    }//if
    
    return md5_file((string)$this);
  
  }//method
  
  /**
   *  copy the contents from $path into this instance
   *
   *  @since  9-21-11
   *  @param  string  $path
   *  @return integer how many bytes were copied over      
   */
  public function copyFrom($path){
  
    // canary...
    if($this->isDir()){
      throw new \UnexpectedValueException(
        sprintf('Cannot copy a file into a directory, path: %s',$this)
      );
    }//if
  
    $path = $this->normalize($path);
    if(!$path->isFile()){
      throw new \UnexpectedValueException(
        sprintf('src path %s does not actually exist',$path)
      );
    }//if
    
    // make sure the full path of $this exists...
    $basepath = $this->normalize($this->getPath());
    $basepath->assure();
    
    return copy((string)$path,(string)$this);
    
  }//method

  /**
   *  get the part of path that is relative to the internal path
   *  
   *  @example
   *    echo $this; // /foo/bar/
   *    echo $path; // /foo/bar/baz/che
   *    $this->getRelative($path); // baz/che               
   *
   *  @since  9-21-11
   *  @param  string|self $path
   *  @return string
   */
  public function getRelative($path){
  
    // canary...
    if(empty($path)){ return null; }//if
    
    $path = $this->normalize($path);
    $count = 0;
    
    $ret_str = str_replace((string)$this,'',(string)$path,$count);
    
    if($count > 0){
    
      if(($ret_str[0] === '\\') || ($ret_str[0] === '/')){
      
        $ret_str = mb_substr($ret_str,1);
      
      }//if
    
    }else{
    
      $ret_str = (string)$path;
    
    }//if/else
    
    return $ret_str;
  
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
  /* public function getIntersection($path){
  
    // canary...
    if(empty($path)){ return ''; }//if
    
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
   *  required for the IteratorAggregate interface
   *
   *  @return \Traversable
   */
  public function getIterator(){ return $this->createIterator(); }//method

  /**
   *  create an iterator to iterate through only the file children
   *  
   *  @since  9-20-11
   *  @param  string  $regex
   *  @param  integer $depth
   *  @return \Traversable
   */
  public function createFileIterator($regex = '',$depth = -1){
  
    $iterator = $this->createIterator($regex,$depth);
    return new CallbackFilterIterator(
      $iterator,
      function($current,$key,$iterator){ return $current->isFile(); }
    );
  
  
  }//method

  /**
   *  create an iterator to iterate through the children
   *  
   *  @since  6-23-11   
   *  @param  string  $regex
   *  @param  integer $depth
   *  @return \Traversable
   */
  public function createIterator($regex = '',$depth = -1){
  
    $depth = (int)$depth;
    $iterator = null;
  
    if(($depth < 0) || ($depth > 1)){
    
      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
          $this->getPathname(),
          FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
        ),
        RecursiveIteratorIterator::SELF_FIRST
      );
      
      $iterator->setMaxDepth($depth);
    
    }else{
    
      $iterator = new FilesystemIterator(
        $this->getPathname(),
        FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
      );
      
    }//if/else
      
    if(!empty($regex)){
    
      $iterator = new RegexIterator($iterator,$regex,RegexIterator::MATCH);
    
    }//if/else
  
    ///$iterator->setInfoClass(get_class($this)); // doing this doubles execution time from 70ms to ~170ms
  
    return $iterator;
  
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
        
        if($path_bit instanceof self){
        
          $ret_list[] = $path_bit->__toString();
        
        }else{
          
          $path_bit = preg_split('#[\\\\/]#',$path_bit); // split on dir separators
          $path_bit = array_map('trim',$path_bit); // trim all the individual bits
          $path_bit = array_filter($path_bit); // get rid of any empty values
          if(!empty($path_bit)){
            $ret_list = array_merge($ret_list,$path_bit); // merge the bits into the final list
          }//if
          
        }//if/else
        
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
  
    $path = $this->trimSlash($path);
    
    return $path;
  
  }//method
  
  /**
   *  make sure path doesn't end with a slash...
   *  
   *  @since  7-6-11
   *  @param  string  $path
   *  @return string
   */
  protected function trimSlash($path){
  
    if(mb_substr($path,-1) == DIRECTORY_SEPARATOR){
      $path = mb_substr($path,0,-1);
    }//if
    
    return $path;
  
  }//method
  
  /**
   *  make sure a string path is an instance of this class
   *
   *  @since  9-21-11
   *  @param  string|array|self $path
   *  @return self      
   */
  protected function normalize($path){
  
    // canary...
    if($path instanceof self){ return $path; }//if
  
    $class_name = get_class($this);
    return new $class_name($path);
  
  }//method
  
  /**
   *  append $data to the file
   *
   *  @since  9-22-11
   *  @param  string  $data
   *  @param  integer $flags   
   *  @return integer number of bytes written
   */
  protected function write($data,$flags = LOCK_EX){
  
    // canary...
    if($this->isDir()){
      throw new \UnexpectedValueException(
        sprintf('You cannot write to a directory. Path: %s',$this)
      );
    }//if
  
    $ret_int = file_put_contents($this,$data,$flags);
    if($ret_int === false){
    
      throw new \UnexpectedValueException(
        sprintf('writing to path "%s" failed',$this)
      );
    
    }//method
  
    return $ret_int;
  
  }//method

}//class     
